<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NhatKyHeThong;
use App\Notifications\PasswordResetNotification;
use App\Services\Admin\BootstrapAdminService;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class BootstrapAdminCommandTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        $this->thuHoiAdminSeedDeMoPhongProductionMoi();
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.creating: '.NhatKyHeThong::class);
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_command_bootstraps_exactly_one_admin_and_never_prints_credential(): void
    {
        Notification::fake();
        $thamSo = [
            '--name' => 'Admin Production',
            '--email' => '  ADMIN.BOOTSTRAP@EXAMPLE.COM ',
            '--phone' => '0901234567',
            '--branch' => 'CHI_NHANH_MVP',
            '--confirm' => true,
            '--no-interaction' => true,
        ];

        $this->assertSame(0, Artisan::call('smart-fitness:bootstrap-admin', $thamSo));
        $output = Artisan::output();
        $this->assertStringContainsString('BOOTSTRAP_ADMIN_CREATED', $output);
        $this->assertStringContainsString('password_setup_notification: QUEUED', $output);
        $this->assertStringNotContainsString('mat_khau_bam', $output);
        $this->assertStringNotContainsString('token=', $output);

        $taiKhoan = DB::table('nguoi_dung')->where('thu_dien_tu', 'admin.bootstrap@example.com')->sole();
        $this->assertSame('HOAT_DONG', $taiKhoan->trang_thai);
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('nguoi_dung_id', $taiKhoan->id)
            ->where('vai_tro.ma_vai_tro', 'ADMIN')
            ->whereNull('thu_hoi_luc')
            ->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('loai_tac_nhan', 'HE_THONG')
            ->where('hanh_dong', 'KHOI_TAO_ADMIN_DAU_TIEN')
            ->where('dinh_danh_doi_tuong', $taiKhoan->id)
            ->count());
        $this->assertSame(0, DB::table('ho_so_hoi_vien')->where('nguoi_dung_id', $taiKhoan->id)->count());
        $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $taiKhoan->id)->count());
        $passwordReset = null;
        Notification::assertSentOnDemand(
            PasswordResetNotification::class,
            function (PasswordResetNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$passwordReset): bool {
                $dungNguoiNhan = $channels === ['mail']
                    && $notifiable->routeNotificationFor('mail') === 'admin.bootstrap@example.com';
                if ($dungNguoiNhan) {
                    $passwordReset = $notification;
                }

                return $dungNguoiNhan;
            },
        );
        $this->assertInstanceOf(PasswordResetNotification::class, $passwordReset);
        $this->postJson('/api/auth/reset-password', [
            'token' => $passwordReset->rawToken,
            'password' => 'Bootstrap!Password123',
            'password_confirmation' => 'Bootstrap!Password123',
        ])->assertOk();
        $token = (string) $this->dangNhapApi(
            'admin.bootstrap@example.com',
            'Bootstrap!Password123',
            diaChiIp: '192.0.2.210',
        )->json('data.access_token');
        $this->getJson('/api/admin/dashboard', $this->bearer($token))->assertOk();

        $this->assertSame(0, Artisan::call('smart-fitness:bootstrap-admin', $thamSo));
        $this->assertSame('UNCHANGED', app(BootstrapAdminService::class)->khoiTao([
            'name' => 'Admin Production',
            'email' => 'admin.bootstrap@example.com',
            'phone' => '0901234567',
            'branch' => 'CHI_NHANH_MVP',
        ])['transition']);
        $this->assertSame(1, DB::table('nguoi_dung')->where('thu_dien_tu', 'admin.bootstrap@example.com')->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'KHOI_TAO_ADMIN_DAU_TIEN')->count());
        Notification::assertSentOnDemandTimes(PasswordResetNotification::class, 1);
    }

    public function test_command_refuses_second_different_active_admin(): void
    {
        Notification::fake();
        $this->taoAdmin('admin.one@example.com');

        $exit = Artisan::call('smart-fitness:bootstrap-admin', [
            '--name' => 'Admin Two',
            '--email' => 'admin.two@example.com',
            '--branch' => 'CHI_NHANH_MVP',
            '--confirm' => true,
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('ADMIN_ALREADY_BOOTSTRAPPED', Artisan::output());
        $this->assertSame(0, DB::table('nguoi_dung')->where('thu_dien_tu', 'admin.two@example.com')->count());
    }

    public function test_bootstrap_transaction_rolls_back_account_role_and_audit_when_audit_fails(): void
    {
        Event::listen('eloquent.creating: '.NhatKyHeThong::class, static function (): never {
            throw new RuntimeException('Forced bootstrap audit failure');
        });

        $this->expectException(RuntimeException::class);
        try {
            app(BootstrapAdminService::class)->khoiTao([
                'name' => 'Rollback Admin',
                'email' => 'rollback.admin@example.com',
                'phone' => null,
                'branch' => 'CHI_NHANH_MVP',
            ]);
        } finally {
            $this->assertSame(0, DB::table('nguoi_dung')->where('thu_dien_tu', 'rollback.admin@example.com')->count());
            $this->assertSame(0, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'KHOI_TAO_ADMIN_DAU_TIEN')->count());
        }
    }

    private function taoAdmin(string $email): void
    {
        app(BootstrapAdminService::class)->khoiTao([
            'name' => 'Existing Admin',
            'email' => $email,
            'phone' => null,
            'branch' => 'CHI_NHANH_MVP',
        ]);
    }

    private function thuHoiAdminSeedDeMoPhongProductionMoi(): void
    {
        $hienTai = CarbonImmutable::now('UTC');
        $vaiTroAdminId = DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        DB::table('phan_quyen_nguoi_dung')
            ->where('vai_tro_id', $vaiTroAdminId)
            ->whereNull('thu_hoi_luc')
            ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);
    }
}
