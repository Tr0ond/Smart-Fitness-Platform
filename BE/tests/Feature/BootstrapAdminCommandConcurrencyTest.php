<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class BootstrapAdminCommandConcurrencyTest extends TestCase
{
    /** @var array<int, array{thu_hoi_luc: mixed, ngay_cap_nhat: mixed}> */
    private array $phanQuyenAdminBanDau = [];

    private ?int $taiKhoanBootstrapId = null;

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
        $vaiTroAdminId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        $this->phanQuyenAdminBanDau = DB::table('phan_quyen_nguoi_dung')
            ->where('vai_tro_id', $vaiTroAdminId)
            ->get(['id', 'thu_hoi_luc', 'ngay_cap_nhat'])
            ->mapWithKeys(fn (object $phanQuyen): array => [(int) $phanQuyen->id => [
                'thu_hoi_luc' => $phanQuyen->thu_hoi_luc,
                'ngay_cap_nhat' => $phanQuyen->ngay_cap_nhat,
            ]])
            ->all();
        $hienTai = CarbonImmutable::now('UTC');
        DB::table('phan_quyen_nguoi_dung')
            ->where('vai_tro_id', $vaiTroAdminId)
            ->whereNull('thu_hoi_luc')
            ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);
    }

    protected function tearDown(): void
    {
        if ($this->taiKhoanBootstrapId !== null) {
            DB::table('yeu_cau_dat_lai_mat_khau')->where('nguoi_dung_id', $this->taiKhoanBootstrapId)->delete();
            DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'NGUOI_DUNG')
                ->where('dinh_danh_doi_tuong', $this->taiKhoanBootstrapId)
                ->delete();
            DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $this->taiKhoanBootstrapId)->delete();
            DB::table('nguoi_dung')->where('id', $this->taiKhoanBootstrapId)->delete();
        }
        foreach ($this->phanQuyenAdminBanDau as $id => $duLieu) {
            DB::table('phan_quyen_nguoi_dung')->where('id', $id)->update($duLieu);
        }
        parent::tearDown();
    }

    public function test_two_actual_bootstrap_commands_create_exactly_one_admin(): void
    {
        $email = 'bootstrap.concurrent.'.bin2hex(random_bytes(5)).'@example.com';
        $commands = array_fill(0, 2, [
            PHP_BINARY,
            base_path('artisan'),
            'smart-fitness:bootstrap-admin',
            '--name=Concurrent Bootstrap',
            '--email='.$email,
            '--branch=CHI_NHANH_MVP',
            '--confirm',
            '--no-interaction',
        ]);
        $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        foreach ($commands as $command) {
            $pipes = [];
            $process = proc_open(
                $command,
                $descriptor,
                $pipes,
                base_path(),
                TestDatabaseGuard::moiTruongTienTrinhCon(),
            );
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        $ketQua = [];
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            $this->assertSame(0, $exit, trim((string) $stderr));
            $ketQua[] = (string) $stdout;
        }

        sort($ketQua);
        $this->assertStringContainsString('BOOTSTRAP_ADMIN_CREATED', $ketQua[0]);
        $this->assertStringContainsString('BOOTSTRAP_ADMIN_UNCHANGED', $ketQua[1]);
        $taiKhoan = DB::table('nguoi_dung')->where('thu_dien_tu', $email)->sole();
        $this->taiKhoanBootstrapId = (int) $taiKhoan->id;
        $this->assertSame(1, DB::table('nguoi_dung')->where('thu_dien_tu', $email)->count());
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('nguoi_dung_id', $taiKhoan->id)
            ->where('vai_tro.ma_vai_tro', 'ADMIN')
            ->whereNull('thu_hoi_luc')
            ->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'KHOI_TAO_ADMIN_DAU_TIEN')
            ->where('dinh_danh_doi_tuong', $taiKhoan->id)
            ->count());
    }
}
