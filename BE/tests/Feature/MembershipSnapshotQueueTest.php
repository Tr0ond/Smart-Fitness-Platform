<?php

namespace Tests\Feature;

use App\Models\KyHanHoiVien;
use App\Services\MembershipActivationService;
use App\Services\MembershipLifecycleService;
use App\Services\MembershipProvisioningService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class MembershipSnapshotQueueTest extends TestCase
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

    public function test_catalog_changes_do_not_mutate_old_snapshot_and_new_order_gets_new_version(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'V1', 'gia' => 200000]);
        $kyCu = $this->taoDonVaSnapshotMembership($memberId, $goi['package']);

        DB::table('goi_tap')->where('id', $goi['package']->getKey())->update([
            'ten_goi' => 'V2', 'gia' => 400000, 'phien_ban_cau_hinh' => 2,
        ]);
        DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $goi['package']->getKey())->update([
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 1,
        ]);
        $goiMoi = $goi['package']->refresh();
        $kyMoi = $this->taoDonVaSnapshotMembership($memberId, $goiMoi);

        $this->assertSame('V1', $kyCu->refresh()->ten_goi);
        $this->assertSame('200000', $kyCu->gia_da_mua);
        $this->assertTrue($kyCu->cho_phep_tro_chuyen_huan_luyen_vien);
        $this->assertSame(4, $kyCu->so_buoi_huan_luyen_vien);
        $this->assertSame('V2', $kyMoi->ten_goi);
        $this->assertSame(2, $kyMoi->phien_ban_goi);
        $this->assertFalse($kyMoi->cho_phep_tro_chuyen_huan_luyen_vien);
        $this->assertSame(1, $kyMoi->so_buoi_huan_luyen_vien);
    }

    public function test_three_paid_orders_form_one_ordered_queue_without_merging_or_dates_before_activation(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 10:00:00.100000', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $cacKy = collect();
        foreach ([
            ['ONE', 10, true, 1],
            ['TWO', 20, false, 2],
            ['THREE', 15, true, 0],
        ] as $viTri => [$ten, $soNgay, $chat, $soBuoi]) {
            $goi = $this->taoGoiTapMembership($fixture, [
                'ten_goi' => $ten,
                'thoi_han_ngay' => $soNgay,
            ], [
                'cho_phep_tro_chuyen_huan_luyen_vien' => $chat,
                'so_buoi_huan_luyen_vien' => $soBuoi,
            ]);
            $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc->addMinutes($viTri));
            $this->xacNhanVaCapMembership($ky, $moc->addMinutes($viTri));
            $cacKy->push($ky->refresh());
        }

        $this->assertSame([1, 2, 3], $cacKy->pluck('so_thu_tu')->all());
        $this->assertSame(['CHO_KICH_HOAT', 'CHO_DEN_LUOT', 'CHO_DEN_LUOT'], $cacKy->pluck('trang_thai')->all());
        foreach ($cacKy as $ky) {
            $this->assertNull($ky->ngay_bat_dau);
            $this->assertNull($ky->ngay_ket_thuc);
        }
        $this->assertSame(1, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame(3, DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame([true, false, true], $cacKy->pluck('cho_phep_tro_chuyen_huan_luyen_vien')->all());
        $this->assertSame([1, 2, 0], $cacKy->pluck('so_buoi_huan_luyen_vien')->all());
    }

    public function test_activation_materializes_continuous_non_overlapping_intervals_for_entire_queue(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 11:22:33.123456', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $cacKy = [];
        foreach ([7, 30, 90] as $viTri => $soNgay) {
            $goi = $this->taoGoiTapMembership($fixture, [
                'ten_goi' => 'Q'.($viTri + 1),
                'thoi_han_ngay' => $soNgay,
            ]);
            $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc->addMinutes($viTri));
            $this->xacNhanVaCapMembership($ky, $moc->addMinutes($viTri));
            $cacKy[] = $ky;
        }
        $usage = $this->taoUsageMembership($cacKy[0], $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);

        $ketQua = app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        $cacKySau = KyHanHoiVien::query()
            ->where('hoi_vien_id', $memberId)
            ->orderBy('so_thu_tu')
            ->get();

        $this->assertSame('MOI_KICH_HOAT', $ketQua['result']);
        $this->assertSame(['DANG_HOAT_DONG', 'CHO_DEN_LUOT', 'CHO_DEN_LUOT'], $cacKySau->pluck('trang_thai')->all());
        $this->assertTrue($cacKySau[0]->ngay_bat_dau->equalTo($moc));
        $this->assertTrue($cacKySau[0]->ngay_ket_thuc->equalTo($cacKySau[1]->ngay_bat_dau));
        $this->assertTrue($cacKySau[1]->ngay_ket_thuc->equalTo($cacKySau[2]->ngay_bat_dau));
        $this->assertTrue($cacKySau[2]->ngay_ket_thuc->equalTo($moc->addDays(127)));

        app(MembershipLifecycleService::class)->doiChieuHoiVien($memberId, $moc->addDay());
        $sauDoiChieu = KyHanHoiVien::query()
            ->where('hoi_vien_id', $memberId)
            ->orderBy('so_thu_tu')
            ->get();
        $this->assertTrue($sauDoiChieu[0]->ngay_bat_dau->equalTo($moc));
        $this->assertTrue($sauDoiChieu[0]->ngay_ket_thuc->equalTo($moc->addDays(7)));
        $this->assertTrue($sauDoiChieu[2]->ngay_ket_thuc->equalTo($moc->addDays(127)));
    }

    public function test_renewal_after_activation_appends_from_tail_and_provisioning_retry_is_idempotent(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 12:00:00.000001', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi1 = $this->taoGoiTapMembership($fixture, ['thoi_han_ngay' => 10]);
        $ky1 = $this->taoDonVaSnapshotMembership($memberId, $goi1['package']);
        $cap1 = $this->xacNhanVaCapMembership($ky1, $moc);
        $usage = $this->taoUsageMembership($ky1, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());

        $goi2 = $this->taoGoiTapMembership($fixture, ['thoi_han_ngay' => 20]);
        $ky2 = $this->taoDonVaSnapshotMembership($memberId, $goi2['package'], $moc->addSecond());
        $cap2 = $this->xacNhanVaCapMembership($ky2, $moc->addSecond());
        $lapLai = app(MembershipProvisioningService::class)->capTuThanhToanDaXacNhan(
            $ky2->don_mua_goi_id,
            $cap2['payment']->getKey(),
        );

        $this->assertSame('DA_CAP_TRUOC', $lapLai['result']);
        $this->assertSame(1, $cap1['provisioning']['sequence']);
        $this->assertSame(2, $cap2['provisioning']['sequence']);
        $this->assertTrue($ky2->refresh()->ngay_bat_dau->equalTo($ky1->refresh()->ngay_ket_thuc));
        $this->assertTrue($ky2->ngay_ket_thuc->equalTo($moc->addDays(30)));
        $this->assertSame(2, DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $memberId)->count());
    }
}
