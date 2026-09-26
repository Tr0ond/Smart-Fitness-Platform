<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use App\Models\PhienTap;

class WorkoutSessionQueryService
{
    public function __construct(private readonly WorkoutMemberService $members) {}

    /** Trả history self-owned, giới hạn 1..100 và cursor ID giảm dần. */
    public function danhSach(NguoiDung $nguoiDung, int $limit = 20, ?int $truocId = null): array
    {
        return $this->danhSachChoHoiVien($this->members->hoiVienCuaNguoiDung($nguoiDung), $limit, $truocId);
    }

    /** Trả history theo hồ sơ Member đã được xác thực phạm vi. */
    public function danhSachChoHoiVien(HoSoHoiVien $hoiVien, int $limit = 20, ?int $truocId = null): array
    {
        $limit = max(1, min(100, $limit));
        $truyVan = $this->truyVanChiTiet()->where('hoi_vien_id', $hoiVien->getKey());
        if ($truocId !== null) {
            $truyVan->where('id', '<', $truocId);
        }

        return $truyVan->orderByDesc('id')->limit($limit)->get()
            ->map(fn (PhienTap $phien): array => $this->duLieuPhien($phien))->values()->all();
    }

    /** Trả Session theo self ownership; Session của Member khác được che bằng 404. */
    public function chiTiet(NguoiDung $nguoiDung, int $phienId): array
    {
        return $this->chiTietChoHoiVien($this->members->hoiVienCuaNguoiDung($nguoiDung), $phienId);
    }

    /** Trả immutable session snapshot theo hồ sơ Member đã được xác thực phạm vi. */
    public function chiTietChoHoiVien(HoSoHoiVien $hoiVien, int $phienId): array
    {
        $phien = $this->truyVanChiTiet()->where('hoi_vien_id', $hoiVien->getKey())->find($phienId);
        if (! $phien instanceof PhienTap) {
            throw new WorkoutWorkflowException('Không tìm thấy phiên tập.', 404, 'WORKOUT_SESSION_NOT_FOUND');
        }

        return $this->duLieuPhien($phien);
    }

    public function duLieuPhien(PhienTap $phien): array
    {
        if (! $phien->relationLoaded('buoiTapDuKien') || ! $phien->relationLoaded('baiTapTrongPhiens')) {
            $phien->load($this->quanHe());
        }

        return [
            'id' => (int) $phien->getKey(),
            'scheduled_workout_id' => (int) $phien->buoi_tap_du_kien_id,
            'scheduled_date' => $phien->buoiTapDuKien?->ngay_tap?->toDateString(),
            'plan_id' => $phien->buoiTapDuKien === null ? null : (int) $phien->buoiTapDuKien->ke_hoach_tap_id,
            'version_id' => $phien->buoiTapDuKien === null ? null : (int) $phien->buoiTapDuKien->phien_ban_ke_hoach_tap_id,
            'name' => $phien->ten_buoi_tap,
            'status' => $phien->trang_thai,
            'started_at' => $phien->bat_dau_luc?->toISOString(),
            'ended_at' => $phien->ket_thuc_luc?->toISOString(),
            'notes' => $phien->ghi_chu,
            'revision' => (int) $phien->phien_ban_du_lieu,
            'exercises' => $phien->baiTapTrongPhiens->sortBy(['so_thu_tu', 'id'])->values()->map(fn ($bai): array => [
                'id' => (int) $bai->getKey(),
                'exercise_id' => (int) $bai->bai_tap_id,
                'plan_exercise_id' => $bai->bai_tap_trong_ke_hoach_id === null ? null : (int) $bai->bai_tap_trong_ke_hoach_id,
                'execution_id' => $bai->ma_bai_thuc_hien,
                'order' => (int) $bai->so_thu_tu,
                'name' => $bai->ten_bai_tap,
                'instructions' => $bai->huong_dan,
                'equipment' => $bai->dung_cu_su_dung,
                'target_sets' => (int) $bai->so_hiep_du_kien,
                'min_reps' => (int) $bai->so_lan_lap_du_kien_toi_thieu,
                'max_reps' => (int) $bai->so_lan_lap_du_kien_toi_da,
                'target_weight_kg' => $bai->khoi_luong_du_kien_kg,
                'rest_seconds' => (int) $bai->thoi_gian_nghi_du_kien_giay,
                'sets' => $bai->hiepTaps->sortBy(['so_thu_tu', 'id'])->values()->map(fn ($hiep): array => [
                    'id' => (int) $hiep->getKey(),
                    'set_id' => $hiep->ma_hiep_thuc_hien,
                    'order' => (int) $hiep->so_thu_tu,
                    'reps' => (int) $hiep->so_lan_lap,
                    'weight_kg' => $hiep->khoi_luong_kg,
                    'actual_rest_seconds' => $hiep->thoi_gian_nghi_thuc_te_giay === null ? null : (int) $hiep->thoi_gian_nghi_thuc_te_giay,
                    'completed_at' => $hiep->hoan_thanh_luc?->toISOString(),
                    'revision' => (int) $hiep->phien_ban_du_lieu,
                ])->all(),
            ])->all(),
        ];
    }

    private function truyVanChiTiet()
    {
        return PhienTap::query()->with($this->quanHe());
    }

    private function quanHe(): array
    {
        return ['buoiTapDuKien', 'baiTapTrongPhiens' => fn ($q) => $q->orderBy('so_thu_tu')->orderBy('id'), 'baiTapTrongPhiens.hiepTaps' => fn ($q) => $q->orderBy('so_thu_tu')->orderBy('id')];
    }
}
