<?php

namespace App\Services;

use App\Exceptions\MembershipLifecycleException;
use App\Models\DonMuaGoi;
use App\Models\GoiTap;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MembershipSnapshotService
{
    /**
     * Chụp giá, thời hạn và quyền catalog vào một kỳ CHO_THANH_TOAN của đơn.
     *
     * Input chỉ là ID đơn đã được order workflow tạo. Hàm khóa Member → đơn →
     * catalog, kiểm tra trạng thái/giá/hạn và tạo tối đa một kỳ nhờ transaction
     * cùng UNIQUE don_mua_goi_id. Retry trả lại snapshot cũ, không đọc catalog để
     * sửa snapshot đã có. Hàm không xác nhận thanh toán và không tạo chuỗi.
     */
    public function taoChoDon(int $donMuaGoiId): KyHanHoiVien
    {
        return DB::transaction(function () use ($donMuaGoiId): KyHanHoiVien {
            $donBanDau = DonMuaGoi::query()->find($donMuaGoiId);
            if ($donBanDau === null) {
                throw new MembershipLifecycleException('Không tìm thấy đơn mua gói.');
            }

            HoSoHoiVien::query()->lockForUpdate()->findOrFail($donBanDau->hoi_vien_id);
            $don = DonMuaGoi::query()->lockForUpdate()->findOrFail($donMuaGoiId);
            $snapshotCu = KyHanHoiVien::query()
                ->where('don_mua_goi_id', $don->getKey())
                ->lockForUpdate()
                ->first();
            if ($snapshotCu !== null) {
                return $snapshotCu;
            }

            if ($don->trang_thai !== 'CHO_THANH_TOAN') {
                throw new MembershipLifecycleException('Đơn không ở trạng thái cho phép tạo snapshot.');
            }
            if (CarbonImmutable::now('UTC')->greaterThanOrEqualTo($don->het_han_thanh_toan_luc)) {
                throw new MembershipLifecycleException('Đơn đã hết hạn giữ snapshot.');
            }

            $goiTap = GoiTap::query()->with('quyenLoiGoiTap')->lockForUpdate()->find($don->goi_tap_id);
            if ($goiTap === null || $goiTap->trang_thai !== 'DANG_BAN' || $goiTap->quyenLoiGoiTap === null) {
                throw new MembershipLifecycleException('Gói không đủ điều kiện tạo đơn mới.');
            }
            if ((string) $don->so_tien_phai_thu !== (string) $goiTap->gia || $don->don_vi_tien !== 'VND') {
                throw new MembershipLifecycleException('Giá đơn không khớp catalog tại mốc chốt.');
            }

            $quyenLoi = $goiTap->quyenLoiGoiTap;

            return KyHanHoiVien::query()->create([
                'hoi_vien_id' => $don->hoi_vien_id,
                'don_mua_goi_id' => $don->getKey(),
                'lan_thanh_toan_id' => null,
                'dang_ky_goi_tap_id' => null,
                'so_thu_tu' => null,
                'trang_thai' => 'CHO_THANH_TOAN',
                'ten_goi' => $goiTap->ten_goi,
                'phien_ban_goi' => $goiTap->phien_ban_cau_hinh,
                'gia_da_mua' => $goiTap->gia,
                'thoi_han_ngay' => $goiTap->thoi_han_ngay,
                'cho_phep_vao_phong_tap' => $quyenLoi->cho_phep_vao_phong_tap,
                'cho_phep_tro_ly_tap_luyen' => $quyenLoi->cho_phep_tro_ly_tap_luyen,
                'gioi_han_luot_tro_ly' => $quyenLoi->gioi_han_luot_tro_ly,
                'cho_phep_tro_chuyen_huan_luyen_vien' => $quyenLoi->cho_phep_tro_chuyen_huan_luyen_vien,
                'so_buoi_huan_luyen_vien' => $quyenLoi->so_buoi_huan_luyen_vien,
                'so_buoi_huan_luyen_vien_da_dung' => 0,
                'so_luot_tro_ly_da_dung' => 0,
                'so_luot_tro_ly_giu_cho' => 0,
                'mua_luc' => null,
                'ngay_bat_dau' => null,
                'ngay_ket_thuc' => null,
            ]);
        });
    }
}
