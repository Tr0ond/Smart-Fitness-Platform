<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesGymFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class GymQrApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesGymFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        config(['gym.qr_ttl_seconds' => 90]);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_member_issues_hashed_90_second_qr_without_activation(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 08:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienId = $this->taoHoSoHoiVien($fixture);
        $membership = $this->taoMembershipGym($fixture, $hoiVienId, $moc);
        $tokenDangNhap = $this->layTokenProfile($fixture);

        $phanHoi = $this->withHeaders($this->bearer($tokenDangNhap))->postJson('/api/gym/qr');

        $phanHoi->assertCreated()
            ->assertJsonPath('data.ttl_seconds', 90);
        $this->assertStringContainsString('no-store', (string) $phanHoi->headers->get('Cache-Control'));
        $rawQr = (string) $phanHoi->json('data.qr_token');
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', $rawQr);
        $this->assertSame($moc->addSeconds(90)->toISOString(), $phanHoi->json('data.expires_at'));

        $ma = DB::table('ma_vao_phong_tap')->where('hoi_vien_id', $hoiVienId)->sole();
        $this->assertSame(hash('sha256', $rawQr), $ma->ma_bam_bi_mat);
        $this->assertNotSame($rawQr, $ma->ma_bam_bi_mat);
        $this->assertSame('2026-08-29 08:01:30.123456', $ma->het_han_luc);
        $this->assertNull($ma->da_su_dung_luc);
        $this->assertNull($ma->thu_hoi_luc);
        $this->assertDatabaseHas('dang_ky_goi_tap', [
            'hoi_vien_id' => $hoiVienId,
            'trang_thai' => 'CHO_KICH_HOAT',
            'ngay_bat_dau' => null,
            'lan_su_dung_dau_tien_id' => null,
        ]);
        $this->assertDatabaseHas('ky_han_hoi_vien', [
            'id' => $membership['term']->getKey(),
            'trang_thai' => 'CHO_KICH_HOAT',
            'ngay_bat_dau' => null,
            'ngay_ket_thuc' => null,
        ]);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $hoiVienId)->count());
        $this->assertSame(0, DB::table('lich_su_vao_phong_tap')->where('hoi_vien_id', $hoiVienId)->count());
    }

    public function test_new_qr_revokes_previous_unconsumed_qr(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 08:10:00.000001', 'UTC');
        CarbonImmutable::setTestNow($moc);
        [$fixture, $hoiVienId, $token] = $this->memberCoGym($moc);

        $qrCu = $this->withHeaders($this->bearer($token))->postJson('/api/gym/qr')->json('data.qr_token');
        CarbonImmutable::setTestNow($moc->addSecond());
        $qrMoi = $this->withHeaders($this->bearer($token))->postJson('/api/gym/qr')->json('data.qr_token');

        $this->assertNotSame($qrCu, $qrMoi);
        $this->assertDatabaseHas('ma_vao_phong_tap', [
            'ma_bam_bi_mat' => hash('sha256', $qrCu),
            'thu_hoi_luc' => '2026-08-29 08:10:01.000001',
        ]);
        $this->assertDatabaseHas('ma_vao_phong_tap', [
            'ma_bam_bi_mat' => hash('sha256', $qrMoi),
            'thu_hoi_luc' => null,
            'da_su_dung_luc' => null,
        ]);
        $this->assertSame(1, DB::table('ma_vao_phong_tap')
            ->where('hoi_vien_id', $hoiVienId)
            ->whereNull('thu_hoi_luc')
            ->whereNull('da_su_dung_luc')
            ->where('het_han_luc', '>', CarbonImmutable::now('UTC'))
            ->count());
    }

    public function test_qr_generation_rejects_missing_or_disallowed_membership_and_client_authority(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 08:20:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $khongGoi = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($khongGoi);
        $tokenKhongGoi = $this->layTokenProfile($khongGoi);
        $this->withHeaders($this->bearer($tokenKhongGoi))
            ->postJson('/api/gym/qr')
            ->assertForbidden()
            ->assertJsonPath('code', 'GYM_ACCESS_DENIED');

        $khongGym = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienKhongGym = $this->taoHoSoHoiVien($khongGym);
        $this->taoMembershipGym($khongGym, $hoiVienKhongGym, $moc, false);
        $tokenKhongGym = $this->layTokenProfile($khongGym);
        $this->withHeaders($this->bearer($tokenKhongGym))
            ->postJson('/api/gym/qr')
            ->assertForbidden()
            ->assertJsonPath('code', 'GYM_ACCESS_DENIED');

        $duocPhep = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienDuocPhep = $this->taoHoSoHoiVien($duocPhep);
        $this->taoMembershipGym($duocPhep, $hoiVienDuocPhep, $moc);
        $tokenDuocPhep = $this->layTokenProfile($duocPhep);
        $this->withHeaders($this->bearer($tokenDuocPhep))->postJson('/api/gym/qr', [
            'member_id' => $hoiVienKhongGym,
            'ttl_seconds' => 3600,
            'branch_id' => $khongGym['branch_id'],
        ])->assertUnprocessable();
        $this->assertSame(0, DB::table('ma_vao_phong_tap')->where('hoi_vien_id', $hoiVienDuocPhep)->count());
    }

    public function test_qr_generation_requires_auth_active_member_role_and_profile(): void
    {
        $this->postJson('/api/gym/qr')->assertUnauthorized();

        $pt = $this->taoNguoiDungAuth(['PT']);
        $tokenPt = $this->layTokenProfile($pt);
        $this->withHeaders($this->bearer($tokenPt))->postJson('/api/gym/qr')->assertForbidden();

        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $tokenMember = $this->layTokenProfile($member);
        $this->withHeaders($this->bearer($tokenMember))
            ->postJson('/api/gym/qr')
            ->assertNotFound()
            ->assertJsonPath('code', 'MEMBER_PROFILE_REQUIRED');
    }

    /** @return array{0: array<string, mixed>, 1: int, 2: string} */
    private function memberCoGym(CarbonImmutable $moc): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienId = $this->taoHoSoHoiVien($fixture);
        $this->taoMembershipGym($fixture, $hoiVienId, $moc);

        return [$fixture, $hoiVienId, $this->layTokenProfile($fixture)];
    }
}
