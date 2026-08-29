<?php

namespace Tests\Feature;

use App\Exceptions\MembershipLifecycleException;
use App\Models\DangKyGoiTap;
use App\Models\KyHanHoiVien;
use App\Services\MembershipActivationService;
use App\Services\MembershipLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class MembershipActivationTest extends TestCase
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

    public function test_first_valid_paid_usage_activates_once_from_accepted_microsecond(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 13:14:15.654321', 'UTC');
        CarbonImmutable::setTestNow($moc);
        [$fixture, $ky] = $this->taoKyDau($moc);
        $usage = $this->taoUsageMembership($ky, $fixture['user']->getKey(), 'YEU_CAU_TRO_LY', $moc);

        $lanDau = app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        $lapLai = app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        $chuoi = DangKyGoiTap::query()->findOrFail($lanDau['registration_id']);

        $this->assertSame('MOI_KICH_HOAT', $lanDau['result']);
        $this->assertSame('DA_KICH_HOAT', $lapLai['result']);
        $this->assertSame($usage->getKey(), $chuoi->lan_su_dung_dau_tien_id);
        $this->assertTrue($chuoi->ngay_bat_dau->equalTo($moc));
        $this->assertTrue($ky->refresh()->ngay_ket_thuc->equalTo($moc->addDays(30)));
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('id', $usage->getKey())->count());
    }

    public function test_later_valid_usage_never_resets_activation_source_or_dates(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 14:00:00.000001', 'UTC');
        CarbonImmutable::setTestNow($moc);
        [$fixture, $ky] = $this->taoKyDau($moc);
        $usageDau = $this->taoUsageMembership($ky, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);
        $service = app(MembershipActivationService::class);
        $service->kichHoatNeuCan($usageDau->getKey());
        $usageSau = $this->taoUsageMembership(
            $ky,
            $fixture['user']->getKey(),
            'TRO_CHUYEN_HUAN_LUYEN',
            $moc->addDay(),
        );

        $ketQua = $service->kichHoatNeuCan($usageSau->getKey());
        $chuoi = DangKyGoiTap::query()->where('hoi_vien_id', $ky->hoi_vien_id)->firstOrFail();

        $this->assertSame('DA_KICH_HOAT', $ketQua['result']);
        $this->assertSame($usageDau->getKey(), $chuoi->lan_su_dung_dau_tien_id);
        $this->assertTrue($chuoi->ngay_bat_dau->equalTo($moc));
        $this->assertTrue($ky->refresh()->ngay_ket_thuc->equalTo($moc->addDays(30)));
    }

    public function test_usage_without_snapshot_entitlement_cannot_activate(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 15:00:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        [$fixture, $ky] = $this->taoKyDau($moc, [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $usage = $this->taoUsageMembership($ky, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);

        try {
            app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
            $this->fail('Usage không có quyền phải bị từ chối.');
        } catch (MembershipLifecycleException $exception) {
            $this->assertStringContainsString('không cấp quyền', $exception->getMessage());
        }

        $this->assertDatabaseHas('dang_ky_goi_tap', [
            'hoi_vien_id' => $ky->hoi_vien_id,
            'trang_thai' => 'CHO_KICH_HOAT',
            'lan_su_dung_dau_tien_id' => null,
        ]);
        $this->assertNull($ky->refresh()->ngay_bat_dau);
    }

    public function test_tail_usage_and_missing_usage_are_rejected_without_partial_activation(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 16:00:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        [$fixture, $kyDau] = $this->taoKyDau($moc);
        $goiSau = $this->taoGoiTapMembership($fixture);
        $kySau = $this->taoDonVaSnapshotMembership($kyDau->hoi_vien_id, $goiSau['package'], $moc->addMinute());
        $this->xacNhanVaCapMembership($kySau, $moc->addMinute());
        $usageSau = $this->taoUsageMembership($kySau, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);
        $service = app(MembershipActivationService::class);

        foreach ([999999999, $usageSau->getKey()] as $usageId) {
            try {
                $service->kichHoatNeuCan($usageId);
                $this->fail('Usage không hợp lệ phải bị từ chối.');
            } catch (MembershipLifecycleException) {
                $this->assertTrue(true);
            }
        }

        $this->assertNull($kyDau->refresh()->ngay_bat_dau);
        $this->assertNull($kySau->refresh()->ngay_bat_dau);
        $this->assertSame(1, DB::table('dang_ky_goi_tap')
            ->where('hoi_vien_id', $kyDau->hoi_vien_id)
            ->where('trang_thai', 'CHO_KICH_HOAT')
            ->count());
    }

    public function test_unpaid_cancelled_and_expired_terms_cannot_activate_or_reactivate(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 16:30:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $service = app(MembershipActivationService::class);

        $chuaThanhToan = $this->taoNguoiDungAuth(['MEMBER']);
        $chuaThanhToanId = $this->taoHoSoHoiVien($chuaThanhToan);
        $goiCho = $this->taoGoiTapMembership($chuaThanhToan);
        $kyCho = $this->taoDonVaSnapshotMembership($chuaThanhToanId, $goiCho['package'], $moc);
        $usageCho = $this->taoUsageMembership($kyCho, $chuaThanhToan['user']->getKey(), 'VAO_PHONG_TAP', $moc);

        [$daHuy, $kyHuy] = $this->taoKyDau($moc);
        DB::table('ky_han_hoi_vien')->where('id', $kyHuy->getKey())->update(['trang_thai' => 'HUY']);
        DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $kyHuy->hoi_vien_id)->update(['trang_thai' => 'HUY']);
        $usageHuy = $this->taoUsageMembership($kyHuy, $daHuy['user']->getKey(), 'VAO_PHONG_TAP', $moc);

        [$daHetHan, $kyHetHan] = $this->taoKyDau($moc);
        $usageHetHan = $this->taoUsageMembership($kyHetHan, $daHetHan['user']->getKey(), 'VAO_PHONG_TAP', $moc);
        $service->kichHoatNeuCan($usageHetHan->getKey());
        app(MembershipLifecycleService::class)->doiChieuHoiVien(
            $kyHetHan->hoi_vien_id,
            $moc->addDays(31),
        );

        foreach ([$usageCho->getKey(), $usageHuy->getKey(), $usageHetHan->getKey()] as $usageId) {
            try {
                $service->kichHoatNeuCan($usageId);
                $this->fail('Trạng thái terminal/chưa thanh toán phải bị từ chối.');
            } catch (MembershipLifecycleException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame('CHO_THANH_TOAN', $kyCho->refresh()->trang_thai);
        $this->assertSame('HUY', $kyHuy->refresh()->trang_thai);
        $this->assertSame('HET_HAN', $kyHetHan->refresh()->trang_thai);
    }

    /** @return array{0: array<string, mixed>, 1: KyHanHoiVien} */
    private function taoKyDau(CarbonImmutable $moc, array $ghiDeQuyen = []): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, [], $ghiDeQuyen);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);

        return [$fixture, $ky->refresh()];
    }
}
