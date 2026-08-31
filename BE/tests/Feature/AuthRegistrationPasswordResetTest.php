<?php

namespace Tests\Feature;

use App\Jobs\ProcessPasswordResetRequest;
use App\Models\YeuCauDatLaiMatKhau;
use App\Notifications\PasswordResetNotification;
use App\Services\Auth\PasswordResetService;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class AuthRegistrationPasswordResetTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_public_registration_atomically_creates_only_member_account_profile_and_audit(): void
    {
        $email = "  TU\u{0301}NG.REGISTER@EXAMPLE.COM  ";
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nguyễn Văn Tùng',
            'email' => $email,
            'phone' => '0901234567',
            'password' => 'Register!Password123',
            'password_confirmation' => 'Register!Password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'túng.register@example.com')
            ->assertJsonPath('data.roles', ['MEMBER'])
            ->assertJsonMissingPath('data.access_token')
            ->assertJsonMissingPath('data.mat_khau_bam');
        $nguoiDungId = (int) $response->json('data.id');
        $this->assertDatabaseHas('nguoi_dung', [
            'id' => $nguoiDungId,
            'thu_dien_tu' => 'túng.register@example.com',
            'trang_thai' => 'HOAT_DONG',
        ]);
        $this->assertDatabaseHas('ho_so_hoi_vien', [
            'nguoi_dung_id' => $nguoiDungId,
            'phien_ban_ho_so' => 1,
            'moc_thay_doi_ke_hoach' => 0,
        ]);
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('nguoi_dung_id', $nguoiDungId)
            ->where('ma_vai_tro', 'MEMBER')
            ->whereNull('thu_hoi_luc')
            ->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'DANG_KY_VAI_TRO_MEMBER')
            ->where('loai_tac_nhan', 'HE_THONG')
            ->count());
        $this->assertSame(0, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', DB::table('ho_so_hoi_vien')
            ->where('nguoi_dung_id', $nguoiDungId)->value('id'))->count());
        $this->assertStringNotContainsString('Register!Password123', (string) $response->getContent());
    }

    public static function authorityFields(): array
    {
        return [
            'admin role' => ['role', 'ADMIN'],
            'role list' => ['roles', ['ADMIN']],
            'official role code' => ['ma_vai_tro', 'PT'],
            'status' => ['trang_thai', 'HOAT_DONG'],
            'branch' => ['chi_nhanh_id', 1],
            'member code' => ['ma_hoi_vien', 'HV_TUY_CHON'],
            'password hash' => ['mat_khau_bam', 'plaintext'],
        ];
    }

    #[DataProvider('authorityFields')]
    public function test_public_registration_blocks_authority_fields(string $field, mixed $value): void
    {
        $payload = [
            'name' => 'Escalation Attempt',
            'email' => 'escalation.'.bin2hex(random_bytes(3)).'@example.com',
            'password' => 'Register!Password123',
            'password_confirmation' => 'Register!Password123',
            $field => $value,
        ];

        $this->postJson('/api/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
        $this->assertDatabaseMissing('nguoi_dung', ['thu_dien_tu' => $payload['email']]);
    }

    public function test_duplicate_canonical_email_is_rejected_without_sqlstate_leak(): void
    {
        $this->taoNguoiDungAuth(thuDienTu: 'trùng@example.com');

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Trùng Email',
            'email' => "TRU\u{0300}NG@EXAMPLE.COM",
            'password' => 'Register!Password123',
            'password_confirmation' => 'Register!Password123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->getContent());
    }

    public function test_forgot_password_is_enumeration_safe_and_stores_only_hash(): void
    {
        Notification::fake();
        Queue::fake();
        $fixture = $this->taoNguoiDungAuth(thuDienTu: 'forgot@example.com');
        $safeResponse = [
            'data' => null,
            'message' => 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
        ];

        $known = $this->postJson('/api/auth/forgot-password', ['email' => ' FORGOT@EXAMPLE.COM ']);
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@example.com']);

        $known->assertOk()->assertExactJson($safeResponse);
        $unknown->assertOk()->assertExactJson($safeResponse);
        // The HTTP request only queues work; account lookup and mail run in worker.
        $this->assertSame(0, DB::table('yeu_cau_dat_lai_mat_khau')->count());
        Notification::assertNothingSent();
        Queue::assertPushedTimes(ProcessPasswordResetRequest::class, 2);
        $jobs = [];
        Queue::assertPushed(ProcessPasswordResetRequest::class, function (ProcessPasswordResetRequest $job) use (&$jobs): bool {
            $jobs[] = $job;

            return true;
        });
        $this->assertSame(['forgot@example.com', 'unknown@example.com'], collect($jobs)->pluck('email')->sort()->values()->all());
        foreach ($jobs as $job) {
            $this->assertObjectNotHasProperty('rawToken', $job);
            $job->handle(app(PasswordResetService::class));
        }
        $this->assertSame(1, DB::table('yeu_cau_dat_lai_mat_khau')->count());
        $notification = $this->passwordResetNotificationFor($fixture['user']->thu_dien_tu);
        $stored = DB::table('yeu_cau_dat_lai_mat_khau')->sole();
        $this->assertSame(hash('sha256', $notification->rawToken), $stored->ma_bam_xac_nhan);
        $this->assertNotSame($notification->rawToken, $stored->ma_bam_xac_nhan);
        $this->assertStringNotContainsString($notification->rawToken, (string) $known->getContent());
    }

    public function test_valid_reset_changes_password_consumes_token_and_revokes_existing_access_tokens(): void
    {
        Notification::fake();
        $fixture = $this->taoNguoiDungAuth(thuDienTu: 'reset@example.com');
        $oldToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu, diaChiIp: '192.0.2.10')
            ->json('data.access_token');
        $this->postJson('/api/auth/forgot-password', ['email' => $fixture['user']->thu_dien_tu])->assertOk();
        $notification = $this->passwordResetNotificationFor($fixture['user']->thu_dien_tu);

        $this->postJson('/api/auth/reset-password', [
            'token' => $notification->rawToken,
            'password' => 'NewPassword!456',
            'password_confirmation' => 'NewPassword!456',
        ])->assertOk();

        $this->assertNotNull(DB::table('yeu_cau_dat_lai_mat_khau')->value('da_su_dung_luc'));
        $this->assertNotNull(DB::table('the_truy_cap')->where('ma_bam_the', hash('sha256', $oldToken))->value('thu_hoi_luc'));
        $this->getJson('/api/auth/me', $this->bearer($oldToken))->assertUnauthorized();
        $this->dangNhapApi($fixture['user']->thu_dien_tu, $fixture['password'], diaChiIp: '192.0.2.11')
            ->assertUnauthorized();
        $this->dangNhapApi($fixture['user']->thu_dien_tu, 'NewPassword!456', diaChiIp: '192.0.2.12')
            ->assertOk();
        $this->postJson('/api/auth/reset-password', [
            'token' => $notification->rawToken,
            'password' => 'AnotherPassword!789',
            'password_confirmation' => 'AnotherPassword!789',
        ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_PASSWORD_RESET_TOKEN');
    }

    public function test_invalid_and_expired_reset_tokens_are_blocked_at_exact_boundary(): void
    {
        $hienTai = CarbonImmutable::parse('2026-08-31 10:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $fixture = $this->taoNguoiDungAuth();
        $rawToken = bin2hex(random_bytes(32));
        YeuCauDatLaiMatKhau::query()->create([
            'nguoi_dung_id' => $fixture['user']->getKey(),
            'ma_bam_xac_nhan' => hash('sha256', $rawToken),
            'het_han_luc' => $hienTai,
            'da_su_dung_luc' => null,
            'thu_hoi_luc' => null,
            'ngay_tao' => $hienTai->subMinutes(30),
            'ngay_cap_nhat' => $hienTai->subMinutes(30),
        ]);

        foreach ([$rawToken, str_repeat('a', 64)] as $token) {
            $this->postJson('/api/auth/reset-password', [
                'token' => $token,
                'password' => 'NewPassword!456',
                'password_confirmation' => 'NewPassword!456',
            ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_PASSWORD_RESET_TOKEN');
        }
        $this->dangNhapApi($fixture['user']->thu_dien_tu, $fixture['password'], diaChiIp: '192.0.2.20')->assertOk();
    }

    private function passwordResetNotificationFor(string $email): PasswordResetNotification
    {
        $found = null;
        Notification::assertSentOnDemand(
            PasswordResetNotification::class,
            function (PasswordResetNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$found): bool {
                if ($notifiable->routeNotificationFor('mail') !== $email) {
                    return false;
                }
                $found = $notification;

                return $channels === ['mail'];
            },
        );
        $this->assertInstanceOf(PasswordResetNotification::class, $found);

        return $found;
    }
}
