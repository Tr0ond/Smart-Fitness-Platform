<?php

namespace Tests\Feature;

use Database\Seeders\ChiNhanhSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoNguoiDungSeeder;
use Database\Seeders\ExerciseDatasetSeeder;
use Database\Seeders\GoiTapSeeder;
use Database\Seeders\QuyenLoiGoiTapSeeder;
use Database\Seeders\VaiTroSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class DemoSeederSecurityTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    private string $moiTruongBanDau;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        $this->moiTruongBanDau = app()->environment();
    }

    protected function tearDown(): void
    {
        app()->detectEnvironment(fn (): string => $this->moiTruongBanDau);
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_production_database_seeder_excludes_demo_users_and_demo_dependent_dataset(): void
    {
        $seeder = new class extends DatabaseSeeder
        {
            /** @var array<int, class-string<Seeder>> */
            public array $cacSeeder = [];

            public function call($class, $silent = false, array $parameters = [])
            {
                foreach ((array) $class as $seeder) {
                    $this->cacSeeder[] = $seeder;
                }

                return $this;
            }
        };

        $this->trongMoiTruong('production', fn () => $seeder->run());

        $this->assertSame([
            ChiNhanhSeeder::class,
            VaiTroSeeder::class,
            GoiTapSeeder::class,
            QuyenLoiGoiTapSeeder::class,
        ], $seeder->cacSeeder);
        $this->assertNotContains(DemoNguoiDungSeeder::class, $seeder->cacSeeder);
        $this->assertNotContains(ExerciseDatasetSeeder::class, $seeder->cacSeeder);
    }

    public function test_direct_demo_seeder_fails_closed_in_production_before_any_mutation(): void
    {
        $truoc = $this->anhChupDemo();
        $loi = null;

        try {
            $this->trongMoiTruong('production', fn () => app(DemoNguoiDungSeeder::class)->run());
        } catch (RuntimeException $exception) {
            $loi = $exception;
        }

        $this->assertInstanceOf(RuntimeException::class, $loi);
        $this->assertSame('DemoNguoiDungSeeder chỉ được phép chạy trong môi trường local hoặc testing.', $loi->getMessage());
        $this->assertSame($truoc, $this->anhChupDemo());
    }

    public function test_testing_and_local_demo_seeding_remain_idempotent_for_active_roles(): void
    {
        foreach (['testing', 'local'] as $moiTruong) {
            $truoc = $this->anhChupDemo();
            $this->trongMoiTruong($moiTruong, fn () => app(DemoNguoiDungSeeder::class)->run());
            $this->assertSame($truoc, $this->anhChupDemo(), "Demo seed không idempotent trong {$moiTruong}.");
        }

        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->select('nguoi_dung_id', 'vai_tro_id')
            ->groupBy('nguoi_dung_id', 'vai_tro_id')
            ->havingRaw('COUNT(*) > 1')
            ->count());
    }

    public function test_revoked_demo_role_is_not_silently_regranted_or_duplicated(): void
    {
        $adminId = (int) DB::table('nguoi_dung')
            ->where('thu_dien_tu', 'dev.admin@smartfitness.local')
            ->value('id');
        $vaiTroId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        $phanQuyen = DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $adminId)
            ->where('vai_tro_id', $vaiTroId)
            ->first();
        $this->assertNotNull($phanQuyen);
        $mocThuHoi = now('UTC');
        DB::table('phan_quyen_nguoi_dung')->where('id', $phanQuyen->id)->update([
            'thu_hoi_luc' => $mocThuHoi,
            'ngay_cap_nhat' => $mocThuHoi,
        ]);
        $truoc = $this->anhChupDemo();
        $auditTruoc = DB::table('nhat_ky_he_thong')->count();
        $loi = null;

        try {
            $this->trongMoiTruong('testing', fn () => app(DemoNguoiDungSeeder::class)->run());
        } catch (RuntimeException $exception) {
            $loi = $exception;
        }

        $this->assertInstanceOf(RuntimeException::class, $loi);
        $this->assertStringContainsString('đã bị thu hồi', $loi->getMessage());
        $this->assertSame($truoc, $this->anhChupDemo());
        $this->assertNotNull(DB::table('phan_quyen_nguoi_dung')->where('id', $phanQuyen->id)->value('thu_hoi_luc'));
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $adminId)
            ->where('vai_tro_id', $vaiTroId)
            ->count());
        $this->assertSame($auditTruoc, DB::table('nhat_ky_he_thong')->count());
    }

    /** @return array<string, mixed> */
    private function anhChupDemo(): array
    {
        $admin = DB::table('nguoi_dung')
            ->where('thu_dien_tu', 'dev.admin@smartfitness.local')
            ->first();

        return [
            'environment' => app()->environment(),
            'users' => DB::table('nguoi_dung')->count(),
            'roles' => DB::table('phan_quyen_nguoi_dung')->count(),
            'member_profiles' => DB::table('ho_so_hoi_vien')->count(),
            'trainer_profiles' => DB::table('ho_so_huan_luyen_vien')->count(),
            'admin_exists' => $admin !== null,
            'admin_password_hash' => $admin?->mat_khau_bam,
            'admin_status' => $admin?->trang_thai,
            'role_rows' => DB::table('phan_quyen_nguoi_dung')
                ->orderBy('id')
                ->get(['id', 'nguoi_dung_id', 'vai_tro_id', 'nguoi_cap_id', 'cap_luc', 'thu_hoi_luc'])
                ->map(fn (object $row): array => (array) $row)
                ->all(),
        ];
    }

    private function trongMoiTruong(string $moiTruong, callable $callback): mixed
    {
        $cu = app()->environment();
        app()->detectEnvironment(fn (): string => $moiTruong);

        try {
            return $callback();
        } finally {
            app()->detectEnvironment(fn (): string => $cu);
        }
    }
}
