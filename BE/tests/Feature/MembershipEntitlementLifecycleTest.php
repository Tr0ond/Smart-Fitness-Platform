<?php

namespace Tests\Feature;

use App\Models\KyHanHoiVien;
use App\Services\MembershipActivationService;
use App\Services\MembershipEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class MembershipEntitlementLifecycleTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

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

    public function test_entitlement_uses_start_inclusive_end_exclusive_boundary(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 17:00:00.123456', 'UTC');
        [$fixture, $ky] = $this->taoVaKichHoat($moc, 1);
        $service = app(MembershipEntitlementService::class);

        $truocStart = $service->kiemTra(
            $ky->hoi_vien_id,
            MembershipEntitlementService::VAO_PHONG_TAP,
            $moc->subMicrosecond(),
        );
        $taiStart = $service->kiemTra($ky->hoi_vien_id, MembershipEntitlementService::VAO_PHONG_TAP, $moc);
        $truocEnd = $service->kiemTra(
            $ky->hoi_vien_id,
            MembershipEntitlementService::VAO_PHONG_TAP,
            $moc->addDay()->subMicrosecond(),
        );
        $taiEnd = $service->kiemTra(
            $ky->hoi_vien_id,
            MembershipEntitlementService::VAO_PHONG_TAP,
            $moc->addDay(),
        );
        $sauEnd = $service->kiemTra(
            $ky->hoi_vien_id,
            MembershipEntitlementService::VAO_PHONG_TAP,
            $moc->addDay()->addMicrosecond(),
        );

        $this->assertFalse($truocStart['allowed']);
        $this->assertTrue($taiStart['allowed']);
        $this->assertTrue($truocEnd['allowed']);
        $this->assertFalse($taiEnd['allowed']);
        $this->assertFalse($sauEnd['allowed']);
        $this->assertSame('KHONG_CO_MEMBERSHIP', $taiEnd['reason']);
        $this->assertDatabaseHas('dang_ky_goi_tap', ['hoi_vien_id' => $ky->hoi_vien_id, 'trang_thai' => 'HET_HAN']);
        $this->assertDatabaseHas('ky_han_hoi_vien', ['id' => $ky->getKey(), 'trang_thai' => 'HET_HAN']);
        $this->assertNotNull($fixture['user']->getKey());
    }

    public function test_queue_switches_at_exact_boundary_without_borrowing_future_term(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 18:00:00.000001', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goiDau = $this->taoGoiTapMembership($fixture, ['thoi_han_ngay' => 1], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $kyDau = $this->taoDonVaSnapshotMembership($memberId, $goiDau['package'], $moc);
        $this->xacNhanVaCapMembership($kyDau, $moc);
        $goiSau = $this->taoGoiTapMembership($fixture, ['thoi_han_ngay' => 2], [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $kySau = $this->taoDonVaSnapshotMembership($memberId, $goiSau['package'], $moc->addSecond());
        $this->xacNhanVaCapMembership($kySau, $moc->addSecond());
        $usage = $this->taoUsageMembership($kyDau, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        $quyen = app(MembershipEntitlementService::class);

        $this->assertFalse($quyen->kiemTra(
            $memberId,
            MembershipEntitlementService::TRO_CHUYEN_HUAN_LUYEN,
            $moc->addHour(),
        )['allowed']);
        $this->assertTrue($quyen->kiemTra(
            $memberId,
            MembershipEntitlementService::TRO_CHUYEN_HUAN_LUYEN,
            $moc->addDay(),
        )['allowed']);
        $this->assertFalse($quyen->kiemTra(
            $memberId,
            MembershipEntitlementService::VAO_PHONG_TAP,
            $moc->addDay(),
        )['allowed']);
        $this->assertSame('HET_HAN', $kyDau->refresh()->trang_thai);
        $this->assertSame('DANG_HOAT_DONG', $kySau->refresh()->trang_thai);
    }

    public function test_waiting_head_can_be_checked_for_activation_but_is_not_active_entitlement(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 19:00:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);
        $service = app(MembershipEntitlementService::class);

        $this->assertFalse($service->kiemTra($memberId, MembershipEntitlementService::VAO_PHONG_TAP, $moc)['allowed']);
        $coKichHoat = $service->kiemTra($memberId, MembershipEntitlementService::VAO_PHONG_TAP, $moc, true);
        $this->assertTrue($coKichHoat['allowed']);
        $this->assertTrue($coKichHoat['can_activate']);
        $this->assertSame($ky->getKey(), $coKichHoat['term_id']);
    }

    public function test_trainer_chat_and_direct_session_quota_are_independent(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 20:00:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, [], [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);
        $usage = $this->taoUsageMembership($ky, $fixture['user']->getKey(), 'TRO_CHUYEN_HUAN_LUYEN', $moc);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        $service = app(MembershipEntitlementService::class);

        $this->assertTrue($service->kiemTra($memberId, MembershipEntitlementService::TRO_CHUYEN_HUAN_LUYEN, $moc)['allowed']);
        $this->assertFalse($service->kiemTra($memberId, MembershipEntitlementService::BUOI_HUAN_LUYEN, $moc)['allowed']);

        $fixtureNghichDao = $this->taoNguoiDungAuth(['MEMBER']);
        $memberNghichDaoId = $this->taoHoSoHoiVien($fixtureNghichDao);
        $goiNghichDao = $this->taoGoiTapMembership($fixtureNghichDao, [], [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 2,
        ]);
        $kyNghichDao = $this->taoDonVaSnapshotMembership($memberNghichDaoId, $goiNghichDao['package'], $moc);
        $this->xacNhanVaCapMembership($kyNghichDao, $moc);
        $usageNghichDao = $this->taoUsageMembership(
            $kyNghichDao,
            $fixtureNghichDao['user']->getKey(),
            'BUOI_HUAN_LUYEN',
            $moc,
        );
        app(MembershipActivationService::class)->kichHoatNeuCan($usageNghichDao->getKey());

        $this->assertFalse($service->kiemTra($memberNghichDaoId, MembershipEntitlementService::TRO_CHUYEN_HUAN_LUYEN, $moc)['allowed']);
        $this->assertTrue($service->kiemTra($memberNghichDaoId, MembershipEntitlementService::BUOI_HUAN_LUYEN, $moc)['allowed']);
    }

    public function test_ai_limit_uses_only_current_term_counters_without_consuming_them(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 21:00:00', 'UTC');
        [$fixture, $ky] = $this->taoVaKichHoat($moc, 30);
        $ky->forceFill(['gioi_han_luot_tro_ly' => 3, 'so_luot_tro_ly_da_dung' => 2, 'so_luot_tro_ly_giu_cho' => 1])->save();
        $service = app(MembershipEntitlementService::class);

        $ketQua = $service->kiemTra($ky->hoi_vien_id, MembershipEntitlementService::YEU_CAU_TRO_LY, $moc);
        $this->assertFalse($ketQua['allowed']);
        $this->assertSame(2, $ky->refresh()->so_luot_tro_ly_da_dung);
        $this->assertSame(1, $ky->so_luot_tro_ly_giu_cho);
        $this->assertNotNull($fixture['user']->getKey());
    }

    public function test_cancelled_chain_and_term_never_grant_entitlement(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 21:30:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);
        DB::table('ky_han_hoi_vien')->where('id', $ky->getKey())->update(['trang_thai' => 'HUY']);
        DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->update(['trang_thai' => 'HUY']);

        $ketQua = app(MembershipEntitlementService::class)->kiemTra(
            $memberId,
            MembershipEntitlementService::VAO_PHONG_TAP,
            $moc,
            true,
        );

        $this->assertFalse($ketQua['allowed']);
        $this->assertSame('KHONG_CO_MEMBERSHIP', $ketQua['reason']);
        $this->assertNull($ky->refresh()->ngay_bat_dau);
    }

    /** @return array{0: array<string, mixed>, 1: KyHanHoiVien} */
    private function taoVaKichHoat(CarbonImmutable $moc, int $soNgay): array
    {
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['thoi_han_ngay' => $soNgay]);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);
        $usage = $this->taoUsageMembership($ky, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());

        return [$fixture, $ky->refresh()];
    }
}
