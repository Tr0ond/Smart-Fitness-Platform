<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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

    public function test_valid_login_issues_only_raw_token_once_and_stores_sha256_hash(): void
    {
        $hienTai = CarbonImmutable::parse('2026-08-29 05:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $fixture = $this->taoNguoiDungAuth();

        $response = $this->dangNhapApi($fixture['user']->thu_dien_tu, $fixture['password']);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $fixture['user']->getKey())
            ->assertJsonPath('data.user.roles.0', 'MEMBER')
            ->assertJsonMissingPath('data.user.mat_khau_bam')
            ->assertJsonMissingPath('data.token_hash');
        $rawToken = (string) $response->json('data.access_token');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $rawToken);

        $banGhiThe = DB::table('the_truy_cap')->where('nguoi_dung_id', $fixture['user']->getKey())->sole();
        $this->assertSame(hash('sha256', $rawToken), $banGhiThe->ma_bam_the);
        $this->assertNotSame($rawToken, $banGhiThe->ma_bam_the);
        $this->assertSame('2026-09-28 05:00:00.123456', $banGhiThe->het_han_luc);
        $this->assertStringNotContainsString($fixture['password'], (string) $response->getContent());
        $this->assertSame(
            '2026-08-29 05:00:00.123456',
            DB::table('nguoi_dung')->where('id', $fixture['user']->getKey())->value('dang_nhap_gan_nhat_luc'),
        );
    }

    public function test_email_is_trimmed_nfc_normalized_and_lowercased_before_lookup(): void
    {
        $fixture = $this->taoNguoiDungAuth(thuDienTu: 'túng@example.com');
        $emailPhanRa = "  TU\u{0301}NG@EXAMPLE.COM  ";

        $this->dangNhapApi($emailPhanRa, $fixture['password'])->assertOk();
    }

    public function test_unknown_email_and_wrong_password_return_same_generic_error(): void
    {
        $fixture = $this->taoNguoiDungAuth();
        $emailSai = $this->dangNhapApi('khong-ton-tai@example.com', $fixture['password']);
        $matKhauSai = $this->dangNhapApi($fixture['user']->thu_dien_tu, 'SaiMatKhau!123');

        $emailSai->assertUnauthorized()->assertExactJson(['message' => 'Thông tin đăng nhập không hợp lệ.']);
        $matKhauSai->assertUnauthorized()->assertExactJson(['message' => 'Thông tin đăng nhập không hợp lệ.']);
        $this->assertSame(0, DB::table('the_truy_cap')->count());
    }

    public function test_malformed_login_payload_returns_validation_error(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'khong-phai-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public static function trangThaiTaiKhoanBiTuChoi(): array
    {
        return [['BI_KHOA'], ['NGUNG_HOAT_DONG']];
    }

    #[DataProvider('trangThaiTaiKhoanBiTuChoi')]
    public function test_disallowed_account_states_cannot_issue_token(string $trangThai): void
    {
        $fixture = $this->taoNguoiDungAuth(trangThai: $trangThai);

        $this->dangNhapApi($fixture['user']->thu_dien_tu, $fixture['password'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Thông tin đăng nhập không hợp lệ.']);
        $this->assertSame(0, DB::table('the_truy_cap')->count());
    }

    public function test_user_without_active_role_cannot_login(): void
    {
        $fixture = $this->taoNguoiDungAuth([]);

        $this->dangNhapApi($fixture['user']->thu_dien_tu, $fixture['password'])->assertUnauthorized();
        $this->assertSame(0, DB::table('the_truy_cap')->count());
    }

    public function test_valid_bearer_token_returns_safe_current_user_and_updates_last_used(): void
    {
        $hienTai = CarbonImmutable::parse('2026-08-29 06:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $fixture = $this->taoNguoiDungAuth(['MEMBER', 'PT']);
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
        $maBamThe = hash('sha256', $rawToken);
        $this->assertNull(DB::table('the_truy_cap')->where('ma_bam_the', $maBamThe)->value('su_dung_gan_nhat_luc'));
        $this->assertSame(
            $hienTai->format('Y-m-d H:i:s.u'),
            DB::table('the_truy_cap')->where('ma_bam_the', $maBamThe)->value('ngay_cap_nhat'),
        );

        $thoiDiemSuDung = $hienTai->addMinutes(9);
        CarbonImmutable::setTestNow($thoiDiemSuDung);
        $this->getJson('/api/auth/me', $this->bearer($rawToken))
            ->assertOk()
            ->assertJsonPath('data.id', $fixture['user']->getKey())
            ->assertJsonPath('data.email', $fixture['user']->thu_dien_tu)
            ->assertJsonPath('data.roles', ['MEMBER', 'PT'])
            ->assertJsonMissingPath('data.mat_khau_bam')
            ->assertJsonMissingPath('data.ma_bam_the');
        $this->assertSame(
            $thoiDiemSuDung->format('Y-m-d H:i:s.u'),
            DB::table('the_truy_cap')->where('ma_bam_the', $maBamThe)->value('su_dung_gan_nhat_luc'),
        );
        $this->assertSame(
            $thoiDiemSuDung->format('Y-m-d H:i:s.u'),
            DB::table('the_truy_cap')->where('ma_bam_the', $maBamThe)->value('ngay_cap_nhat'),
        );
    }

    public function test_missing_malformed_and_unknown_bearer_tokens_are_rejected(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer'])->assertUnauthorized();
        $this->getJson('/api/auth/me', $this->bearer(str_repeat('a', 64)))->assertUnauthorized();
    }

    public function test_expired_token_is_rejected_at_and_after_expiry(): void
    {
        $hienTai = CarbonImmutable::parse('2026-08-29 12:00:00.000000', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $fixture = $this->taoNguoiDungAuth();
        $rawToken = $this->taoTheTruyCapThuCong(
            $fixture['user'],
            $hienTai->subHours(2),
            $hienTai,
        );

        $this->getJson('/api/auth/me', $this->bearer($rawToken))->assertUnauthorized();
    }

    public function test_revoked_token_is_rejected(): void
    {
        $hienTai = CarbonImmutable::now('UTC');
        $fixture = $this->taoNguoiDungAuth();
        $rawToken = $this->taoTheTruyCapThuCong(
            $fixture['user'],
            $hienTai->subMinute(),
            $hienTai->addHour(),
            $hienTai,
        );

        $this->getJson('/api/auth/me', $this->bearer($rawToken))->assertUnauthorized();
    }

    public function test_account_blocked_after_issuance_invalidates_existing_token(): void
    {
        $fixture = $this->taoNguoiDungAuth();
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
        NguoiDung::query()->whereKey($fixture['user']->getKey())->update(['trang_thai' => 'BI_KHOA']);

        $this->getJson('/api/auth/me', $this->bearer($rawToken))->assertUnauthorized();
    }

    public function test_logout_revokes_only_current_token_and_preserves_other_device_token(): void
    {
        $fixture = $this->taoNguoiDungAuth();
        $tokenA = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu, tenThietBi: 'Thiet bi A')->json('data.access_token');
        $tokenB = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu, tenThietBi: 'Thiet bi B')->json('data.access_token');
        $this->assertNotSame($tokenA, $tokenB);

        $this->postJson('/api/auth/logout', [], $this->bearer($tokenA))->assertOk();
        $this->assertNotNull(DB::table('the_truy_cap')->where('ma_bam_the', hash('sha256', $tokenA))->value('thu_hoi_luc'));
        $this->assertNull(DB::table('the_truy_cap')->where('ma_bam_the', hash('sha256', $tokenB))->value('thu_hoi_luc'));
        $this->getJson('/api/auth/me', $this->bearer($tokenA))->assertUnauthorized();
        $this->getJson('/api/auth/me', $this->bearer($tokenB))->assertOk();
    }

    public function test_login_me_and_logout_do_not_activate_membership_or_create_usage(): void
    {
        $fixture = $this->taoNguoiDungAuth();
        $membership = $this->taoMembershipChoKichHoat($fixture);
        $usageCount = DB::table('su_dung_quyen_loi')->count();

        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
        $this->getJson('/api/auth/me', $this->bearer($rawToken))->assertOk();
        $this->postJson('/api/auth/logout', [], $this->bearer($rawToken))->assertOk();

        $dangKy = DB::table('dang_ky_goi_tap')->where('id', $membership['dang_ky_id'])->sole();
        $this->assertSame('CHO_KICH_HOAT', $dangKy->trang_thai);
        $this->assertNull($dangKy->lan_su_dung_dau_tien_id);
        $this->assertNull($dangKy->ngay_bat_dau);
        $this->assertSame($usageCount, DB::table('su_dung_quyen_loi')->count());
    }

    public function test_login_endpoint_is_throttled_per_ip_without_email_enumeration_key(): void
    {
        for ($lan = 1; $lan <= 5; $lan++) {
            $this->dangNhapApi('khong-ton-tai-'.$lan.'@example.com', diaChiIp: '198.51.100.10')
                ->assertUnauthorized();
        }

        $this->dangNhapApi('email-khac@example.com', diaChiIp: '198.51.100.10')->assertTooManyRequests();
    }
}
