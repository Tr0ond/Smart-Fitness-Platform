<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class AuthRoleConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
    }

    public function test_two_actual_processes_registering_same_email_create_at_most_one_complete_member(): void
    {
        $email = 'concurrent.register.'.bin2hex(random_bytes(5)).'@example.com';

        try {
            $results = $this->runProcesses('run_auth_registration.php', [
                [$email],
                [$email],
            ]);
            $this->assertSame(['error', 'success'], collect($results)->pluck('status')->sort()->values()->all());
            $this->assertSame(['ACCOUNT_ALREADY_EXISTS'], collect($results)->where('status', 'error')->pluck('code')->all());
            $userId = (int) DB::table('nguoi_dung')->where('thu_dien_tu', $email)->sole()->id;
            $this->assertSame(1, DB::table('ho_so_hoi_vien')->where('nguoi_dung_id', $userId)->count());
            $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
                ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
                ->where('nguoi_dung_id', $userId)
                ->where('vai_tro.ma_vai_tro', 'MEMBER')
                ->count());
            $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'DANG_KY_VAI_TRO_MEMBER')->where(
                'dinh_danh_doi_tuong',
                DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $userId)->value('id'),
            )->count());
        } finally {
            $this->cleanupRegisteredEmail($email);
        }
    }

    public function test_two_actual_processes_granting_same_role_create_one_row_and_one_transition_audit(): void
    {
        $fixture = $this->createRoleFixture(false);

        try {
            $results = $this->runProcesses('run_auth_role_action.php', [
                [$fixture['admin_id'], $fixture['target_id'], 'RECEPTIONIST', 'grant'],
                [$fixture['admin_id'], $fixture['target_id'], 'RECEPTIONIST', 'grant'],
            ]);
            $this->assertSame(['GRANTED', 'UNCHANGED'], collect($results)->pluck('transition')->sort()->values()->all());
            $assignmentId = (int) DB::table('phan_quyen_nguoi_dung')
                ->where('nguoi_dung_id', $fixture['target_id'])
                ->where('vai_tro_id', $fixture['pt_role_id'])
                ->sole()->id;
            $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
                ->where('nguoi_dung_id', $fixture['target_id'])
                ->where('vai_tro_id', $fixture['pt_role_id'])->count());
            $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('dinh_danh_doi_tuong', $assignmentId)->count());
        } finally {
            $this->cleanupRoleFixture($fixture);
        }
    }

    public function test_two_actual_processes_revoking_same_role_make_one_logical_transition(): void
    {
        $fixture = $this->createRoleFixture(true);

        try {
            $results = $this->runProcesses('run_auth_role_action.php', [
                [$fixture['admin_id'], $fixture['target_id'], 'RECEPTIONIST', 'revoke'],
                [$fixture['admin_id'], $fixture['target_id'], 'RECEPTIONIST', 'revoke'],
            ]);
            $this->assertSame(['REVOKED', 'UNCHANGED'], collect($results)->pluck('transition')->sort()->values()->all());
            $row = DB::table('phan_quyen_nguoi_dung')->where('id', $fixture['target_pt_assignment_id'])->sole();
            $this->assertNotNull($row->thu_hoi_luc);
            $this->assertSame(1, DB::table('nhat_ky_he_thong')
                ->where('dinh_danh_doi_tuong', $fixture['target_pt_assignment_id'])
                ->where('hanh_dong', 'THU_HOI_VAI_TRO')->count());
        } finally {
            $this->cleanupRoleFixture($fixture);
        }
    }

    public function test_actual_revoke_regrant_race_has_a_linearizable_state_and_consistent_audit(): void
    {
        $fixture = $this->createRoleFixture(true);

        try {
            $results = $this->runProcesses('run_auth_role_action.php', [
                [$fixture['admin_id'], $fixture['target_id'], 'RECEPTIONIST', 'revoke'],
                [$fixture['admin_id'], $fixture['target_id'], 'RECEPTIONIST', 'grant'],
            ]);
            $transitions = collect($results)->pluck('transition')->all();
            $this->assertContains('REVOKED', $transitions);
            $row = DB::table('phan_quyen_nguoi_dung')->where('id', $fixture['target_pt_assignment_id'])->sole();
            $audits = DB::table('nhat_ky_he_thong')
                ->where('dinh_danh_doi_tuong', $fixture['target_pt_assignment_id'])
                ->orderBy('id')
                ->pluck('hanh_dong')
                ->all();

            if ($row->thu_hoi_luc === null) {
                $this->assertSame(['THU_HOI_VAI_TRO', 'CAP_LAI_VAI_TRO'], $audits);
                $this->assertContains('REGRANTED', $transitions);
            } else {
                $this->assertSame(['THU_HOI_VAI_TRO'], $audits);
                $this->assertContains('UNCHANGED', $transitions);
            }
            $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
                ->where('nguoi_dung_id', $fixture['target_id'])
                ->where('vai_tro_id', $fixture['pt_role_id'])->count());
        } finally {
            $this->cleanupRoleFixture($fixture);
        }
    }

    /** @param array<int, array<int, int|string>> $argumentSets @return array<int, array<string, mixed>> */
    private function runProcesses(string $scriptName, array $argumentSets): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'auth_role_start_'.bin2hex(random_bytes(8));
        $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];

        foreach ($argumentSets as $arguments) {
            $pipes = [];
            $process = proc_open([
                PHP_BINARY,
                base_path('tests/Support/'.$scriptName),
                $database,
                ...array_map('strval', $arguments),
                $barrier,
            ], $descriptor, $pipes, base_path(), TestDatabaseGuard::moiTruongTienTrinhCon());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        touch($barrier);
        try {
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->assertSame(0, $exit, trim((string) $stderr));
                $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }

    /** @return array<string, int> */
    private function createRoleFixture(bool $withPt): array
    {
        $suffix = strtoupper(bin2hex(random_bytes(5)));
        $now = CarbonImmutable::now('UTC');
        $branchId = DB::table('chi_nhanh')->insertGetId([
            'ma_chi_nhanh' => 'AUTH_CONC_'.$suffix,
            'ten_chi_nhanh' => 'Auth concurrency '.$suffix,
            'dia_chi' => 'Test only',
            'so_dien_thoai' => null,
            'mui_gio' => 'Asia/Ho_Chi_Minh',
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $adminRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        $memberRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'MEMBER')->value('id');
        $ptRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'RECEPTIONIST')->value('id');
        $adminId = $this->insertUser($branchId, 'admin.'.$suffix.'@example.com', $now);
        $targetId = $this->insertUser($branchId, 'target.'.$suffix.'@example.com', $now);
        $adminAssignmentId = $this->insertRole($adminId, $adminRoleId, null, $now);
        $targetMemberAssignmentId = $this->insertRole($targetId, $memberRoleId, $adminId, $now);
        $targetPtAssignmentId = $withPt ? $this->insertRole($targetId, $ptRoleId, $adminId, $now) : 0;

        return [
            'branch_id' => $branchId,
            'admin_id' => $adminId,
            'target_id' => $targetId,
            'admin_assignment_id' => $adminAssignmentId,
            'target_member_assignment_id' => $targetMemberAssignmentId,
            'target_pt_assignment_id' => $targetPtAssignmentId,
            'pt_role_id' => $ptRoleId,
        ];
    }

    private function insertUser(int $branchId, string $email, CarbonImmutable $now): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'chi_nhanh_id' => $branchId,
            'ho_ten' => 'Auth concurrency',
            'thu_dien_tu' => strtolower($email),
            'so_dien_thoai' => null,
            'mat_khau_bam' => Hash::make('Concurrent!Password123'),
            'anh_dai_dien' => null,
            'xac_minh_thu_luc' => null,
            'trang_thai' => 'HOAT_DONG',
            'dang_nhap_gan_nhat_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
    }

    private function insertRole(int $userId, int $roleId, ?int $grantedById, CarbonImmutable $now): int
    {
        return DB::table('phan_quyen_nguoi_dung')->insertGetId([
            'nguoi_dung_id' => $userId,
            'vai_tro_id' => $roleId,
            'nguoi_cap_id' => $grantedById,
            'cap_luc' => $now,
            'thu_hoi_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
    }

    /** @param array<string, int> $fixture */
    private function cleanupRoleFixture(array $fixture): void
    {
        $assignmentIds = DB::table('phan_quyen_nguoi_dung')
            ->whereIn('nguoi_dung_id', [$fixture['admin_id'], $fixture['target_id']])
            ->pluck('id');
        DB::table('nhat_ky_he_thong')->whereIn('dinh_danh_doi_tuong', $assignmentIds)->delete();
        DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $fixture['admin_id'])->delete();
        DB::table('phan_quyen_nguoi_dung')->whereIn('nguoi_dung_id', [$fixture['admin_id'], $fixture['target_id']])->delete();
        DB::table('nguoi_dung')->whereIn('id', [$fixture['admin_id'], $fixture['target_id']])->delete();
        DB::table('chi_nhanh')->where('id', $fixture['branch_id'])->delete();
    }

    private function cleanupRegisteredEmail(string $email): void
    {
        $userId = DB::table('nguoi_dung')->where('thu_dien_tu', $email)->value('id');
        if ($userId === null) {
            return;
        }
        $assignmentIds = DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $userId)->pluck('id');
        DB::table('nhat_ky_he_thong')->whereIn('dinh_danh_doi_tuong', $assignmentIds)->delete();
        DB::table('ho_so_hoi_vien')->where('nguoi_dung_id', $userId)->delete();
        DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $userId)->delete();
        DB::table('nguoi_dung')->where('id', $userId)->delete();
    }
}
