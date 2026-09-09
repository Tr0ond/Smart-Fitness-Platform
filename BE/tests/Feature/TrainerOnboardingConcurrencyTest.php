<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class TrainerOnboardingConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
    }

    public function test_two_actual_processes_with_same_key_keep_one_onboarding_and_one_invitation(): void
    {
        $suffix = strtolower(bin2hex(random_bytes(5)));
        $email = 'concurrent.trainer.'.$suffix.'@example.com';
        $key = (string) Str::uuid();
        $fixture = $this->createFixture($suffix);

        try {
            $results = $this->runProcesses('run_trainer_onboarding.php', [
                [$fixture['admin_id'], $email, $key],
                [$fixture['admin_id'], $email, $key],
            ]);

            $this->assertCount(2, $results);
            $this->assertNotEmpty(collect($results)->where('status', 'success'));
            foreach (collect($results)->where('status', 'error') as $result) {
                $this->assertSame('IDEMPOTENCY_IN_PROGRESS', $result['code'] ?? null);
            }

            $accountId = (int) DB::table('nguoi_dung')->where('thu_dien_tu', $email)->sole()->id;
            $profileId = (int) DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $accountId)->sole()->id;
            $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
                ->where('nguoi_dung_id', $accountId)
                ->where('vai_tro_id', $fixture['pt_role_id'])
                ->count());
            $this->assertSame(3, DB::table('nhat_ky_he_thong')
                ->where('nguoi_thuc_hien_id', $fixture['admin_id'])
                ->whereIn('hanh_dong', [
                    'TAO_TAI_KHOAN_PT',
                    'TAO_HO_SO_HUAN_LUYEN_VIEN',
                    'CAP_VAI_TRO_PT',
                ])
                ->count());
            $request = DB::table('yeu_cau_chong_lap')
                ->where('nguoi_dung_id', $fixture['admin_id'])
                ->where('pham_vi', 'ADMIN_TRAINER_ONBOARDING')
                ->where('khoa_yeu_cau', $key)
                ->sole();
            $this->assertSame('DA_HOAN_TAT', $request->trang_thai);
            $this->assertSame('QUEUED', json_decode((string) $request->ket_qua_da_loc, true, 512, JSON_THROW_ON_ERROR)['invitation']);
            $this->assertSame(1, DB::table('yeu_cau_dat_lai_mat_khau')->where('nguoi_dung_id', $accountId)->count());
            $this->assertGreaterThan(0, $profileId);
        } finally {
            $this->cleanupFixture($fixture, $email, $key);
        }
    }

    /** @param array<int, array<int, int|string>> $argumentSets @return array<int, array<string, mixed>> */
    private function runProcesses(string $scriptName, array $argumentSets): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten_database')->ten_database;
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'trainer_onboarding_start_'.bin2hex(random_bytes(8));
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
    private function createFixture(string $suffix): array
    {
        $now = CarbonImmutable::now('UTC');
        $branchId = DB::table('chi_nhanh')->insertGetId([
            'ma_chi_nhanh' => 'ONBOARD_CONC_'.strtoupper($suffix),
            'ten_chi_nhanh' => 'Trainer onboarding concurrency '.$suffix,
            'dia_chi' => 'Test only',
            'so_dien_thoai' => null,
            'mui_gio' => 'Asia/Ho_Chi_Minh',
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $adminRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        $ptRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'PT')->value('id');
        $adminId = DB::table('nguoi_dung')->insertGetId([
            'chi_nhanh_id' => $branchId,
            'ho_ten' => 'Concurrent onboarding admin',
            'thu_dien_tu' => 'concurrent.onboarding.admin.'.$suffix.'@example.com',
            'so_dien_thoai' => null,
            'mat_khau_bam' => Hash::make('Concurrent!Password123'),
            'anh_dai_dien' => null,
            'xac_minh_thu_luc' => null,
            'trang_thai' => 'HOAT_DONG',
            'dang_nhap_gan_nhat_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $adminAssignmentId = DB::table('phan_quyen_nguoi_dung')->insertGetId([
            'nguoi_dung_id' => $adminId,
            'vai_tro_id' => $adminRoleId,
            'nguoi_cap_id' => null,
            'cap_luc' => $now,
            'thu_hoi_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);

        return [
            'branch_id' => (int) $branchId,
            'admin_id' => (int) $adminId,
            'admin_assignment_id' => (int) $adminAssignmentId,
            'pt_role_id' => $ptRoleId,
        ];
    }

    /** @param array<string, int> $fixture */
    private function cleanupFixture(array $fixture, string $email, string $key): void
    {
        $accountId = DB::table('nguoi_dung')->where('thu_dien_tu', $email)->value('id');
        $userIds = array_values(array_filter([$fixture['admin_id'], $accountId], static fn ($id): bool => $id !== null));
        $assignmentIds = DB::table('phan_quyen_nguoi_dung')->whereIn('nguoi_dung_id', $userIds)->pluck('id');

        DB::table('yeu_cau_dat_lai_mat_khau')->whereIn('nguoi_dung_id', $userIds)->delete();
        DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $fixture['admin_id'])
            ->where('pham_vi', 'ADMIN_TRAINER_ONBOARDING')
            ->where('khoa_yeu_cau', $key)
            ->delete();
        DB::table('nhat_ky_he_thong')
            ->where(function ($query) use ($fixture, $assignmentIds, $accountId): void {
                $query->where('nguoi_thuc_hien_id', $fixture['admin_id'])
                    ->orWhereIn('dinh_danh_doi_tuong', $assignmentIds);
                if ($accountId !== null) {
                    $query->orWhere('dinh_danh_doi_tuong', $accountId);
                }
            })
            ->delete();
        if ($accountId !== null) {
            DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $accountId)->delete();
        }
        DB::table('phan_quyen_nguoi_dung')->whereIn('nguoi_dung_id', $userIds)->delete();
        DB::table('nguoi_dung')->whereIn('id', $userIds)->delete();
        DB::table('chi_nhanh')->where('id', $fixture['branch_id'])->delete();
    }
}
