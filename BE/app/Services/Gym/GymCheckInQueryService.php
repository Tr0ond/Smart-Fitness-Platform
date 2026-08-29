<?php

namespace App\Services\Gym;

use App\Models\HoSoHoiVien;
use App\Models\LichSuVaoPhongTap;
use App\Models\NguoiDung;

class GymCheckInQueryService
{
    /**
     * Trả lịch sử check-in của chính Member, dùng allow-list không chứa token/hash.
     *
     * @return array<int, array<string, mixed>>
     */
    public function layCuaNguoiDung(NguoiDung $nguoiDung): array
    {
        $hoiVien = HoSoHoiVien::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->first();
        abort_if($hoiVien === null, 404, 'Không tìm thấy hồ sơ hội viên.');

        return LichSuVaoPhongTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->with('chiNhanh:id,ma_chi_nhanh,ten_chi_nhanh')
            ->orderByDesc('vao_phong_luc')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LichSuVaoPhongTap $muc): array => [
                'id' => (int) $muc->getKey(),
                'checked_in_at' => $muc->vao_phong_luc->toISOString(),
                'membership_term_id' => (int) $muc->ky_han_hoi_vien_id,
                'branch' => [
                    'id' => (int) $muc->chi_nhanh_id,
                    'code' => $muc->chiNhanh->ma_chi_nhanh,
                    'name' => $muc->chiNhanh->ten_chi_nhanh,
                ],
            ])
            ->all();
    }
}
