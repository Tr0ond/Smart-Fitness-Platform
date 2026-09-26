<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\KeHoachTap;
use App\Models\NguoiDung;

class WorkoutPlanQueryService
{
    public function __construct(private readonly WorkoutMemberService $members) {}

    /** Trả Plan đang dùng của chính Member hoặc null. */
    public function hienTai(NguoiDung $nguoiDung): ?array
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);
        $keHoach = $this->truyVanChiTiet()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('trang_thai', 'DANG_SU_DUNG')
            ->first();

        return $keHoach instanceof KeHoachTap ? $this->duLieuKeHoach($keHoach) : null;
    }

    /** Trả toàn bộ định danh Plan của chính Member, bao gồm Plan lưu trữ. */
    public function danhSach(NguoiDung $nguoiDung): array
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);

        return KeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderByRaw("CASE WHEN trang_thai = 'DANG_SU_DUNG' THEN 0 ELSE 1 END")
            ->orderByDesc('ngay_tao')
            ->orderByDesc('id')
            ->get()
            ->map(fn (KeHoachTap $keHoach): array => [
                'id' => (int) $keHoach->getKey(),
                'name' => $keHoach->ten_ke_hoach,
                'status' => $keHoach->trang_thai,
                'current_version_id' => $keHoach->phien_ban_hien_tai_id === null ? null : (int) $keHoach->phien_ban_hien_tai_id,
                'created_at' => $keHoach->ngay_tao?->toISOString(),
            ])->values()->all();
    }

    /** Trả Plan theo self ownership; ID của Member khác được che bằng 404. */
    public function chiTiet(NguoiDung $nguoiDung, int $keHoachId): array
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);
        $keHoach = $this->truyVanChiTiet()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->find($keHoachId);
        if (! $keHoach instanceof KeHoachTap) {
            throw new WorkoutWorkflowException('Không tìm thấy kế hoạch tập.', 404, 'WORKOUT_PLAN_NOT_FOUND');
        }

        return $this->duLieuKeHoach($keHoach);
    }

    private function truyVanChiTiet()
    {
        return KeHoachTap::query()->with([
            'phienBanHienTai.ngayTrongKeHoachs' => fn ($q) => $q->orderBy('so_thu_tu')->orderBy('id'),
            'phienBanHienTai.ngayTrongKeHoachs.baiTapTrongKeHoachs' => fn ($q) => $q->orderBy('so_thu_tu')->orderBy('id'),
            'phienBanHienTai.ngayTrongKeHoachs.baiTapTrongKeHoachs.baiTap',
        ]);
    }

    private function duLieuKeHoach(KeHoachTap $keHoach): array
    {
        $phienBan = $keHoach->phienBanHienTai;

        return [
            'id' => (int) $keHoach->getKey(),
            'name' => $keHoach->ten_ke_hoach,
            'status' => $keHoach->trang_thai,
            'current_version' => $phienBan === null ? null : [
                'id' => (int) $phienBan->getKey(),
                'number' => (int) $phienBan->so_phien_ban,
                'previous_version_id' => $phienBan->phien_ban_truoc_id === null ? null : (int) $phienBan->phien_ban_truoc_id,
                'source' => $phienBan->nguon_tao,
                'template_id' => $phienBan->giao_an_mau_id === null ? null : (int) $phienBan->giao_an_mau_id,
                'template_name_snapshot' => $phienBan->ten_giao_an_da_chon,
                'goal' => $phienBan->muc_tieu,
                'effective_from' => $phienBan->ap_dung_tu_ngay?->toDateString(),
                'content_hash' => $phienBan->ma_bam_noi_dung,
                'days' => $phienBan->ngayTrongKeHoachs->map(fn ($ngay): array => [
                    'id' => (int) $ngay->getKey(),
                    'logical_id' => $ngay->ma_ngay_logic,
                    'order' => (int) $ngay->so_thu_tu,
                    'weekday' => (int) $ngay->thu_trong_tuan,
                    'name' => $ngay->ten_ngay,
                    'estimated_minutes' => (int) $ngay->thoi_luong_du_kien_phut,
                    'exercises' => $ngay->baiTapTrongKeHoachs->map(fn ($muc): array => [
                        'id' => (int) $muc->getKey(),
                        'exercise_id' => (int) $muc->bai_tap_id,
                        'logical_id' => $muc->ma_bai_logic,
                        'order' => (int) $muc->so_thu_tu,
                        'name' => $muc->ten_bai_tap,
                        'instructions' => $muc->huong_dan,
                        'equipment' => $muc->dung_cu_yeu_cau,
                        'target_sets' => (int) $muc->so_hiep_muc_tieu,
                        'min_reps' => (int) $muc->so_lan_lap_toi_thieu,
                        'max_reps' => (int) $muc->so_lan_lap_toi_da,
                        'target_weight_kg' => $muc->khoi_luong_muc_tieu_kg,
                        'rest_seconds' => (int) $muc->thoi_gian_nghi_giay,
                        'notes' => $muc->ghi_chu,
                        'image_path' => $muc->baiTap?->duong_dan_hinh_anh,
                        'video_path' => $muc->baiTap?->duong_dan_video,
                    ])->values()->all(),
                ])->values()->all(),
            ],
            'created_at' => $keHoach->ngay_tao?->toISOString(),
            'updated_at' => $keHoach->ngay_cap_nhat?->toISOString(),
        ];
    }
}
