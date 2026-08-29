<?php

namespace App\Services;

use App\Models\GoiTap;

class PackageCatalogService
{
    /**
     * Trả catalog gói đang bán theo thứ tự ổn định và chỉ dùng dữ liệu catalog hiện tại.
     * Hàm chỉ đọc, không tạo đơn, kỳ, quyền sử dụng hoặc kích hoạt Membership.
     *
     * @return array<int, array<string, mixed>>
     */
    public function layDanhSach(): array
    {
        return GoiTap::query()
            ->with('quyenLoiGoiTap')
            ->where('trang_thai', 'DANG_BAN')
            ->whereHas('quyenLoiGoiTap')
            ->orderBy('chi_nhanh_id')
            ->orderBy('ma_goi')
            ->orderBy('id')
            ->get()
            ->map(fn (GoiTap $goiTap): array => $this->duLieuGoiTap($goiTap))
            ->all();
    }

    /** Trả chi tiết một gói đang bán; gói không tồn tại/ngừng bán nhận 404 có kiểm soát. */
    public function layChiTiet(int $goiTapId): array
    {
        $goiTap = GoiTap::query()
            ->with('quyenLoiGoiTap')
            ->whereKey($goiTapId)
            ->where('trang_thai', 'DANG_BAN')
            ->whereHas('quyenLoiGoiTap')
            ->first();

        abort_if($goiTap === null, 404, 'Không tìm thấy gói tập đang bán.');

        return $this->duLieuGoiTap($goiTap);
    }

    /** @return array<string, mixed> */
    private function duLieuGoiTap(GoiTap $goiTap): array
    {
        $quyenLoi = $goiTap->quyenLoiGoiTap;

        return [
            'id' => $goiTap->getKey(),
            'branch_id' => $goiTap->chi_nhanh_id,
            'code' => $goiTap->ma_goi,
            'name' => $goiTap->ten_goi,
            'price' => $goiTap->gia,
            'currency' => 'VND',
            'duration_days' => $goiTap->thoi_han_ngay,
            'description' => $goiTap->mo_ta,
            'status' => $goiTap->trang_thai,
            'benefits' => $quyenLoi === null ? null : [
                'gym_access' => $quyenLoi->cho_phep_vao_phong_tap,
                'fitness_assistant' => $quyenLoi->cho_phep_tro_ly_tap_luyen,
                'fitness_assistant_limit' => $quyenLoi->gioi_han_luot_tro_ly,
                'trainer_chat' => $quyenLoi->cho_phep_tro_chuyen_huan_luyen_vien,
                'direct_trainer_sessions' => $quyenLoi->so_buoi_huan_luyen_vien,
            ],
        ];
    }
}
