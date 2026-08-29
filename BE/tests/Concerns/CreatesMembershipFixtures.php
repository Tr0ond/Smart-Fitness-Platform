<?php

namespace Tests\Concerns;

use App\Models\DonMuaGoi;
use App\Models\GoiTap;
use App\Models\KyHanHoiVien;
use App\Models\LanThanhToan;
use App\Models\SuDungQuyenLoi;
use App\Services\MembershipProvisioningService;
use App\Services\MembershipSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

trait CreatesMembershipFixtures
{
    /**
     * @param  array<string, mixed>  $ghiDeGoi
     * @param  array<string, mixed>  $ghiDeQuyen
     * @return array{package: GoiTap, benefit_id: int}
     */
    protected function taoGoiTapMembership(
        array $fixture,
        array $ghiDeGoi = [],
        array $ghiDeQuyen = [],
    ): array {
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $hienTai = CarbonImmutable::now('UTC');
        $goi = GoiTap::query()->create(array_merge([
            'chi_nhanh_id' => $fixture['branch_id'],
            'ma_goi' => 'GOI_'.$hauTo,
            'ten_goi' => 'Gói Membership '.$hauTo,
            'gia' => 300000,
            'thoi_han_ngay' => 30,
            'mo_ta' => 'Fixture kiểm thử Membership',
            'trang_thai' => 'DANG_BAN',
            'phien_ban_cau_hinh' => 1,
            'nguoi_tao_id' => $fixture['user']->getKey(),
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDeGoi));
        $quyenId = DB::table('quyen_loi_goi_tap')->insertGetId(array_merge([
            'goi_tap_id' => $goi->getKey(),
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => true,
            'gioi_han_luot_tro_ly' => 10,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 4,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDeQuyen));

        return ['package' => $goi, 'benefit_id' => $quyenId];
    }

    protected function taoDonVaSnapshotMembership(
        int $hoiVienId,
        GoiTap $goi,
        ?CarbonImmutable $chotLuc = null,
    ): KyHanHoiVien {
        $chotLuc ??= CarbonImmutable::now('UTC');
        $hauTo = strtoupper(bin2hex(random_bytes(8)));
        $don = DonMuaGoi::query()->create([
            'hoi_vien_id' => $hoiVienId,
            'goi_tap_id' => $goi->getKey(),
            'ma_don' => 'DON_'.$hauTo,
            'ma_yeu_cau' => $this->uuidMembership(),
            'so_tien_phai_thu' => $goi->gia,
            'don_vi_tien' => 'VND',
            'trang_thai' => 'CHO_THANH_TOAN',
            'chot_gia_luc' => $chotLuc,
            'het_han_thanh_toan_luc' => $chotLuc->addHour(),
            'thanh_toan_luc' => null,
            'huy_luc' => null,
            'ly_do_huy' => null,
            'ngay_tao' => $chotLuc,
            'ngay_cap_nhat' => $chotLuc,
        ]);

        return app(MembershipSnapshotService::class)->taoChoDon($don->getKey());
    }

    protected function xacNhanVaCapMembership(
        KyHanHoiVien $ky,
        ?CarbonImmutable $xacNhanLuc = null,
    ): array {
        $lanThanhToan = $this->xacNhanThanhToanMembership($ky, $xacNhanLuc);
        $don = DonMuaGoi::query()->findOrFail($ky->don_mua_goi_id);

        $ketQua = app(MembershipProvisioningService::class)->capTuThanhToanDaXacNhan(
            $don->getKey(),
            $lanThanhToan->getKey(),
        );

        return ['payment' => $lanThanhToan, 'provisioning' => $ketQua];
    }

    protected function xacNhanThanhToanMembership(
        KyHanHoiVien $ky,
        ?CarbonImmutable $xacNhanLuc = null,
    ): LanThanhToan {
        $xacNhanLuc ??= CarbonImmutable::now('UTC');
        $don = DonMuaGoi::query()->findOrFail($ky->don_mua_goi_id);
        $hauToSo = random_int(100000000, 2000000000);
        $hauTo = strtoupper(bin2hex(random_bytes(6)));
        $lanThanhToan = LanThanhToan::query()->create([
            'don_mua_goi_id' => $don->getKey(),
            'so_lan' => 1,
            'ma_kenh_thanh_toan' => 'TEST_MEMBERSHIP',
            'ma_don_cong_thanh_toan' => $hauToSo,
            'ma_lien_ket_thanh_toan' => 'LINK_'.$hauTo,
            'duong_dan_thanh_toan' => null,
            'so_tien_yeu_cau' => $don->so_tien_phai_thu,
            'don_vi_tien' => 'VND',
            'trang_thai' => 'THANH_CONG',
            'ma_tham_chieu_duoc_chap_nhan' => 'REF_'.$hauTo,
            'so_tien_da_nhan' => $don->so_tien_phai_thu,
            'thanh_toan_luc' => $xacNhanLuc,
            'xac_nhan_luc' => $xacNhanLuc,
            'het_han_luc' => $xacNhanLuc->addHour(),
            'ma_loi' => null,
            'ngay_tao' => $xacNhanLuc,
            'ngay_cap_nhat' => $xacNhanLuc,
        ]);
        $don->forceFill([
            'trang_thai' => 'DA_THANH_TOAN',
            'thanh_toan_luc' => $xacNhanLuc,
        ])->save();

        return $lanThanhToan;
    }

    protected function taoUsageMembership(
        KyHanHoiVien $ky,
        int $nguoiThucHienId,
        string $loaiSuDung = 'VAO_PHONG_TAP',
        ?CarbonImmutable $chapNhanLuc = null,
    ): SuDungQuyenLoi {
        $chapNhanLuc ??= CarbonImmutable::now('UTC');

        return SuDungQuyenLoi::query()->create([
            'hoi_vien_id' => $ky->hoi_vien_id,
            'ky_han_hoi_vien_id' => $ky->getKey(),
            'nguoi_thuc_hien_id' => $nguoiThucHienId,
            'loai_su_dung' => $loaiSuDung,
            'ma_hanh_dong' => $this->uuidMembership(),
            'chap_nhan_luc' => $chapNhanLuc,
            'ngay_tao' => $chapNhanLuc,
        ]);
    }

    private function uuidMembership(): string
    {
        $hex = bin2hex(random_bytes(16));

        return sprintf(
            '%s-%s-4%s-%s%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 13, 3),
            dechex((hexdec($hex[16]) & 0x3) | 0x8),
            substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }
}
