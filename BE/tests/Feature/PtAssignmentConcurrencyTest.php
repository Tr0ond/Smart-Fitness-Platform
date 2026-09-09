<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class PtAssignmentConcurrencyTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesProfileFixtures;
    use CreatesPtFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
    }

    public function test_actual_process_create_reassign_and_end_preserve_q04_history_and_audit(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $assignmentIds = [];

        try {
            $start = CarbonImmutable::now('UTC')->subMinute()->toISOString();
            $createResults = $this->runProcesses($fixture, [
                [$fixture['pt_a_id'], $start, 'create'],
                [$fixture['pt_a_id'], $start, 'create'],
            ]);
            $this->assertCount(2, $createResults);
            $this->assertSame(1, collect($createResults)->where('status', 'success')->count(), json_encode($createResults));
            $this->assertSame(1, DB::table('phan_cong_huan_luyen_vien')
                ->where('hoi_vien_id', $fixture['member_a_id'])->count());
            $this->assertSame(1, DB::table('nhat_ky_he_thong')
                ->where('hanh_dong', 'TAO_PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('nguoi_thuc_hien_id', $fixture['admin']['user']->getKey())
                ->count());

            $assignmentId = (int) DB::table('phan_cong_huan_luyen_vien')
                ->where('hoi_vien_id', $fixture['member_a_id'])->value('id');
            $assignmentIds[] = $assignmentId;
            $boundary = CarbonImmutable::now('UTC')->subSecond()->toISOString();
            $reassignResults = $this->runProcesses($fixture, [
                [$fixture['pt_b_id'], $boundary, 'reassign', $assignmentId, 'Concurrent reassign A'],
                [$fixture['pt_b_id'], $boundary, 'reassign', $assignmentId, 'Concurrent reassign B'],
            ]);
            $this->assertSame(1, collect($reassignResults)->where('status', 'success')->count(), json_encode($reassignResults));
            $this->assertSame(2, DB::table('phan_cong_huan_luyen_vien')
                ->where('hoi_vien_id', $fixture['member_a_id'])->count());

            $newAssignmentId = (int) DB::table('phan_cong_huan_luyen_vien')
                ->where('hoi_vien_id', $fixture['member_a_id'])
                ->where('huan_luyen_vien_id', $fixture['pt_b_id'])
                ->value('id');
            $assignmentIds[] = $newAssignmentId;
            $this->assertSame(3, DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
                ->whereIn('dinh_danh_doi_tuong', [$assignmentId, $newAssignmentId])
                ->count());
            $endResults = $this->runProcesses($fixture, [
                [$fixture['pt_b_id'], $boundary, 'end', $newAssignmentId, 'Concurrent end A'],
                [$fixture['pt_b_id'], $boundary, 'end', $newAssignmentId, 'Concurrent end B'],
            ]);
            $this->assertSame(2, collect($endResults)->where('status', 'success')->count(), json_encode($endResults));
            $this->assertSame(1, DB::table('nhat_ky_he_thong')
                ->where('hanh_dong', 'KET_THUC_PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('dinh_danh_doi_tuong', $newAssignmentId)
                ->count());
            $this->assertSame(0, DB::table('phan_cong_huan_luyen_vien')
                ->where('hoi_vien_id', $fixture['member_a_id'])
                ->whereNull('ngay_ket_thuc')
                ->where('ngay_bat_dau', '<=', CarbonImmutable::now('UTC'))
                ->count());
        } finally {
            $this->donDep($fixture, $assignmentIds);
        }
    }

    /** @param array<string, mixed> $fixture @param array<int, array<int, mixed>> $actions */
    private function runProcesses(array $fixture, array $actions): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten_database')->ten_database;
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pt_assignment_start_'.bin2hex(random_bytes(8));
        $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];

        foreach ($actions as $action) {
            [$trainerId, $startAt, $mode, $assignmentId, $reason] = array_pad($action, 5, null);
            $output = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pt_assignment_output_'.bin2hex(random_bytes(8));
            $process = proc_open([
                PHP_BINARY,
                base_path('tests/Support/run_pt_assignment.php'),
                $database,
                (string) $fixture['admin']['user']->getKey(),
                (string) $fixture['member_a_id'],
                (string) $trainerId,
                (string) $startAt,
                $barrier,
                $output,
                (string) $mode,
                $assignmentId === null ? '' : (string) $assignmentId,
                $reason === null ? '' : (string) $reason,
            ], $descriptor, $pipes, base_path(), TestDatabaseGuard::moiTruongTienTrinhCon());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes, $output];
        }

        touch($barrier);
        try {
            $results = [];
            foreach ($processes as [$process, $pipes, $output]) {
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->assertSame(0, $exit, trim((string) $stderr));
                $lines = is_file($output) ? file($output, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                $this->assertCount(1, $lines, 'Assignment worker did not emit a result.');
                $results[] = json_decode((string) $lines[0], true, 512, JSON_THROW_ON_ERROR);
                if (is_file($output)) {
                    unlink($output);
                }
            }

            return $results;
        } finally {
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }

    /** @param array<string, mixed> $fixture */
    private function dongBoChiNhanh(array $fixture): void
    {
        DB::table('nguoi_dung')
            ->whereIn('id', [
                $fixture['member_a']['user']->getKey(),
                $fixture['pt_a']['user']->getKey(),
                $fixture['pt_b']['user']->getKey(),
            ])
            ->update(['chi_nhanh_id' => $fixture['admin']['branch_id']]);
    }

    /** @param array<string, mixed> $fixture @param array<int, int> $assignmentIds */
    private function donDep(array $fixture, array $assignmentIds): void
    {
        $userIds = [
            $fixture['admin']['user']->getKey(),
            $fixture['member_a']['user']->getKey(),
            $fixture['member_b']['user']->getKey(),
            $fixture['pt_a']['user']->getKey(),
            $fixture['pt_b']['user']->getKey(),
        ];
        if ($assignmentIds !== []) {
            DB::table('tin_nhan')->whereIn('phan_cong_huan_luyen_vien_id', $assignmentIds)->delete();
            DB::table('hoi_thoai')->whereIn('phan_cong_huan_luyen_vien_id', $assignmentIds)->delete();
            DB::table('nhat_ky_he_thong')->whereIn('dinh_danh_doi_tuong', $assignmentIds)->delete();
            DB::table('phan_cong_huan_luyen_vien')->whereIn('id', $assignmentIds)->delete();
        }
        DB::table('nhat_ky_he_thong')->whereIn('nguoi_thuc_hien_id', $userIds)->delete();
        DB::table('ho_so_hoi_vien')->whereIn('nguoi_dung_id', [
            $fixture['member_a']['user']->getKey(),
            $fixture['member_b']['user']->getKey(),
        ])->delete();
        DB::table('ho_so_huan_luyen_vien')->whereIn('nguoi_dung_id', [
            $fixture['pt_a']['user']->getKey(),
            $fixture['pt_b']['user']->getKey(),
        ])->delete();
        DB::table('phan_quyen_nguoi_dung')->whereIn('nguoi_dung_id', $userIds)->delete();
        DB::table('nguoi_dung')->whereIn('id', $userIds)->delete();
        DB::table('chi_nhanh')->whereIn('id', [
            $fixture['admin']['branch_id'],
            $fixture['member_a']['branch_id'],
            $fixture['member_b']['branch_id'],
            $fixture['pt_a']['branch_id'],
            $fixture['pt_b']['branch_id'],
        ])->delete();
    }
}
