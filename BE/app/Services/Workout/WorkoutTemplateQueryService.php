<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\GiaoAnMau;

class WorkoutTemplateQueryService
{
    /** Trả catalog giáo án hoạt động theo DTO allow-list và thứ tự ổn định. */
    public function danhSach(): array
    {
        return GiaoAnMau::query()
            ->where('trang_thai', 'HOAT_DONG')
            ->orderBy('ten_giao_an')
            ->orderBy('id')
            ->get()
            ->map(fn (GiaoAnMau $giaoAn): array => $this->duLieuTomTat($giaoAn))
            ->values()
            ->all();
    }

    /** Trả chi tiết một giáo án hoạt động; giáo án ngừng dùng được che như không tồn tại. */
    public function chiTiet(int $giaoAnId): array
    {
        $giaoAn = GiaoAnMau::query()
            ->with(['ngayTrongGiaoAns' => fn ($q) => $q->orderBy('so_thu_tu')->orderBy('id'),
                'ngayTrongGiaoAns.baiTapTrongGiaoAns' => fn ($q) => $q->orderBy('so_thu_tu')->orderBy('id'),
                'ngayTrongGiaoAns.baiTapTrongGiaoAns.baiTap'])
            ->where('trang_thai', 'HOAT_DONG')
            ->find($giaoAnId);
        if (! $giaoAn instanceof GiaoAnMau) {
            throw new WorkoutWorkflowException('Không tìm thấy giáo án mẫu.', 404, 'WORKOUT_TEMPLATE_NOT_FOUND');
        }

        return array_merge($this->duLieuTomTat($giaoAn), [
            'description' => $giaoAn->mo_ta,
            'days' => $giaoAn->ngayTrongGiaoAns->map(fn ($ngay): array => [
                'id' => (int) $ngay->getKey(),
                'order' => (int) $ngay->so_thu_tu,
                'name' => $ngay->ten_ngay,
                'estimated_minutes' => (int) $ngay->thoi_luong_du_kien_phut,
                'exercises' => $ngay->baiTapTrongGiaoAns->map(fn ($muc): array => [
                    'id' => (int) $muc->getKey(),
                    'exercise_id' => (int) $muc->bai_tap_id,
                    'order' => (int) $muc->so_thu_tu,
                    'name' => $muc->baiTap?->ten_bai_tap,
                    'image_path' => $muc->baiTap?->duong_dan_hinh_anh,
                    'video_path' => $muc->baiTap?->duong_dan_video,
                    'target_sets' => (int) $muc->so_hiep_muc_tieu,
                    'min_reps' => (int) $muc->so_lan_lap_toi_thieu,
                    'max_reps' => (int) $muc->so_lan_lap_toi_da,
                    'rest_seconds' => (int) $muc->thoi_gian_nghi_giay,
                    'notes' => $muc->ghi_chu,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    private function duLieuTomTat(GiaoAnMau $giaoAn): array
    {
        return [
            'id' => (int) $giaoAn->getKey(),
            'code' => $giaoAn->ma_giao_an,
            'name' => $giaoAn->ten_giao_an,
            'goal' => $giaoAn->muc_tieu,
            'level' => $giaoAn->trinh_do,
            'sessions_per_week' => (int) $giaoAn->so_buoi_moi_tuan,
            'content_version' => (int) $giaoAn->phien_ban_noi_dung,
        ];
    }
}
