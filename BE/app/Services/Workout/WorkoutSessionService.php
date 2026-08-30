<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\BaiTapTrongPhien;
use App\Models\BuoiTapDuKien;
use App\Models\HiepTap;
use App\Models\NguoiDung;
use App\Models\PhienTap;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkoutSessionService
{
    public function __construct(
        private readonly WorkoutMemberService $members,
        private readonly WorkoutSessionQueryService $query,
    ) {}

    /**
     * Bắt đầu đúng buổi đã lên lịch trong ngày địa phương; materialize snapshot
     * bài tập trong cùng transaction. Không kiểm tra hoặc kích hoạt Membership.
     */
    public function batDau(NguoiDung $nguoiDung, int $buoiId, string $maLanBatDau): array
    {
        $this->damBaoUuid($maLanBatDau, 'INVALID_SESSION_START_KEY');
        try {
            return DB::transaction(function () use ($nguoiDung, $buoiId, $maLanBatDau): array {
                $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
                $buoi = BuoiTapDuKien::query()->with('ngayTrongKeHoach.baiTapTrongKeHoachs')->where('hoi_vien_id', $hoiVien->getKey())->lockForUpdate()->find($buoiId);
                if (! $buoi instanceof BuoiTapDuKien) {
                    throw new WorkoutWorkflowException('Không tìm thấy buổi tập dự kiến.', 404, 'SCHEDULED_WORKOUT_NOT_FOUND');
                }

                $phienCu = PhienTap::query()->where('buoi_tap_du_kien_id', $buoiId)->lockForUpdate()->first();
                if ($phienCu instanceof PhienTap) {
                    if (hash_equals($phienCu->ma_lan_bat_dau, $maLanBatDau)) {
                        return array_merge($this->query->duLieuPhien($phienCu), ['replayed' => true]);
                    }
                    throw new WorkoutWorkflowException('Buổi dự kiến đã có phiên tập.', 409, 'WORKOUT_SESSION_ALREADY_EXISTS');
                }

                if ($buoi->trang_thai !== 'CHUA_TAP') {
                    throw new WorkoutWorkflowException('Trạng thái lịch không cho phép bắt đầu.', 409, 'SCHEDULED_WORKOUT_NOT_STARTABLE');
                }
                $ngayDiaPhuong = CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString();
                if ($buoi->ngay_tap?->toDateString() !== $ngayDiaPhuong) {
                    throw new WorkoutWorkflowException('Chỉ được bắt đầu vào đúng ngày đã lên lịch.', 409, 'SCHEDULED_DATE_MISMATCH');
                }
                if ($buoi->ngayTrongKeHoach === null || $buoi->ngayTrongKeHoach->baiTapTrongKeHoachs->isEmpty()) {
                    throw new WorkoutWorkflowException('Buổi tập chưa có nội dung hợp lệ.', 409, 'SCHEDULED_WORKOUT_CONTENT_MISSING');
                }

                $hienTai = CarbonImmutable::now('UTC');
                $phien = PhienTap::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'buoi_tap_du_kien_id' => $buoi->getKey(),
                    'ma_lan_bat_dau' => $maLanBatDau,
                    'ten_buoi_tap' => $buoi->ngayTrongKeHoach->ten_ngay,
                    'bat_dau_luc' => $hienTai,
                    'ket_thuc_luc' => null,
                    'trang_thai' => 'DANG_TAP',
                    'ghi_chu' => null,
                    'phien_ban_du_lieu' => 1,
                    'ma_lan_hoan_thanh' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                foreach ($buoi->ngayTrongKeHoach->baiTapTrongKeHoachs->sortBy(['so_thu_tu', 'id']) as $muc) {
                    BaiTapTrongPhien::query()->create([
                        'phien_tap_id' => $phien->getKey(),
                        'bai_tap_id' => $muc->bai_tap_id,
                        'bai_tap_trong_ke_hoach_id' => $muc->getKey(),
                        'ma_bai_thuc_hien' => $muc->ma_bai_logic,
                        'so_thu_tu' => $muc->so_thu_tu,
                        'ten_bai_tap' => $muc->ten_bai_tap,
                        'huong_dan' => $muc->huong_dan,
                        'dung_cu_su_dung' => $muc->dung_cu_yeu_cau,
                        'so_hiep_du_kien' => $muc->so_hiep_muc_tieu,
                        'so_lan_lap_du_kien_toi_thieu' => $muc->so_lan_lap_toi_thieu,
                        'so_lan_lap_du_kien_toi_da' => $muc->so_lan_lap_toi_da,
                        'khoi_luong_du_kien_kg' => $muc->khoi_luong_muc_tieu_kg,
                        'thoi_gian_nghi_du_kien_giay' => $muc->thoi_gian_nghi_giay,
                        'ngay_tao' => $hienTai,
                        'ngay_cap_nhat' => $hienTai,
                    ]);
                }
                $buoi->forceFill(['trang_thai' => 'DANG_TAP', 'ngay_cap_nhat' => $hienTai])->save();

                return array_merge($this->query->duLieuPhien($phien->fresh()), ['replayed' => false]);
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new WorkoutWorkflowException('Buổi dự kiến đã được bắt đầu đồng thời.', 409, 'WORKOUT_SESSION_ALREADY_EXISTS');
            }
            throw $exception;
        }
    }

    /** Ghi một set idempotent vào đúng bài thuộc Session đang DANG_TAP. */
    public function ghiHiep(NguoiDung $nguoiDung, int $phienId, int $baiTrongPhienId, array $duLieu, string $maHiep): array
    {
        $this->damBaoUuid($maHiep, 'INVALID_SET_KEY');

        return DB::transaction(function () use ($nguoiDung, $phienId, $baiTrongPhienId, $duLieu, $maHiep): array {
            $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
            $phien = PhienTap::query()->where('hoi_vien_id', $hoiVien->getKey())->lockForUpdate()->find($phienId);
            if (! $phien instanceof PhienTap) {
                throw new WorkoutWorkflowException('Không tìm thấy phiên tập.', 404, 'WORKOUT_SESSION_NOT_FOUND');
            }
            if ($phien->trang_thai !== 'DANG_TAP') {
                throw new WorkoutWorkflowException('Phiên đã khóa, không thể ghi set.', 409, 'WORKOUT_SESSION_IMMUTABLE');
            }
            $bai = BaiTapTrongPhien::query()->where('phien_tap_id', $phienId)->lockForUpdate()->find($baiTrongPhienId);
            if (! $bai instanceof BaiTapTrongPhien) {
                throw new WorkoutWorkflowException('Bài tập không thuộc phiên này.', 404, 'SESSION_EXERCISE_NOT_FOUND');
            }
            $cacHiep = HiepTap::query()->where('bai_tap_trong_phien_id', $bai->getKey())->orderBy('id')->lockForUpdate()->get();
            $hiepCu = $cacHiep->firstWhere('ma_hiep_thuc_hien', $maHiep);
            if ($hiepCu instanceof HiepTap) {
                $cungDuLieu = (int) $hiepCu->so_thu_tu === (int) $duLieu['order']
                    && (int) $hiepCu->so_lan_lap === (int) $duLieu['reps']
                    && (string) $hiepCu->khoi_luong_kg === ($duLieu['weight_kg'] === null ? '' : number_format((float) $duLieu['weight_kg'], 2, '.', ''))
                    && $hiepCu->thoi_gian_nghi_thuc_te_giay === ($duLieu['actual_rest_seconds'] ?? null);
                if (! $cungDuLieu) {
                    throw new WorkoutWorkflowException('Mã set đã được dùng cho dữ liệu khác.', 409, 'SET_IDEMPOTENCY_CONFLICT');
                }

                return array_merge($this->duLieuHiep($hiepCu), ['replayed' => true]);
            }
            if ($cacHiep->contains(fn (HiepTap $hiep): bool => (int) $hiep->so_thu_tu === (int) $duLieu['order'])) {
                throw new WorkoutWorkflowException('Thứ tự set đã tồn tại.', 409, 'SET_ORDER_CONFLICT');
            }

            $hienTai = CarbonImmutable::now('UTC');
            $hiep = HiepTap::query()->create([
                'bai_tap_trong_phien_id' => $bai->getKey(),
                'so_thu_tu' => $duLieu['order'],
                'ma_hiep_thuc_hien' => $maHiep,
                'so_lan_lap' => $duLieu['reps'],
                'khoi_luong_kg' => $duLieu['weight_kg'] ?? null,
                'thoi_gian_nghi_thuc_te_giay' => $duLieu['actual_rest_seconds'] ?? null,
                'hoan_thanh_luc' => $hienTai,
                'phien_ban_du_lieu' => 1,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);

            return array_merge($this->duLieuHiep($hiep), ['replayed' => false]);
        }, 3);
    }

    /** Hoàn thành atomically Session và scheduled workout; retry cùng key trả cùng kết quả. */
    public function hoanThanh(NguoiDung $nguoiDung, int $phienId, string $maHoanThanh, ?string $ghiChu = null): array
    {
        $this->damBaoUuid($maHoanThanh, 'INVALID_SESSION_COMPLETE_KEY');

        return DB::transaction(function () use ($nguoiDung, $phienId, $maHoanThanh, $ghiChu): array {
            $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
            $phien = PhienTap::query()->where('hoi_vien_id', $hoiVien->getKey())->lockForUpdate()->find($phienId);
            if (! $phien instanceof PhienTap) {
                throw new WorkoutWorkflowException('Không tìm thấy phiên tập.', 404, 'WORKOUT_SESSION_NOT_FOUND');
            }
            if ($phien->trang_thai === 'HOAN_THANH') {
                if ($phien->ma_lan_hoan_thanh !== null && hash_equals($phien->ma_lan_hoan_thanh, $maHoanThanh)) {
                    return array_merge($this->query->duLieuPhien($phien), ['replayed' => true]);
                }
                throw new WorkoutWorkflowException('Phiên đã hoàn thành và bất biến.', 409, 'WORKOUT_SESSION_IMMUTABLE');
            }
            if ($phien->trang_thai !== 'DANG_TAP') {
                throw new WorkoutWorkflowException('Phiên không thể hoàn thành.', 409, 'WORKOUT_SESSION_NOT_COMPLETABLE');
            }

            $buoi = BuoiTapDuKien::query()->where('hoi_vien_id', $hoiVien->getKey())->lockForUpdate()->find($phien->buoi_tap_du_kien_id);
            if (! $buoi instanceof BuoiTapDuKien || $buoi->trang_thai !== 'DANG_TAP') {
                throw new WorkoutWorkflowException('Lịch và phiên không còn nhất quán.', 409, 'WORKOUT_SCHEDULE_STATE_CONFLICT');
            }
            $hienTai = CarbonImmutable::now('UTC');
            $phien->forceFill([
                'ket_thuc_luc' => $hienTai,
                'trang_thai' => 'HOAN_THANH',
                'ghi_chu' => $ghiChu,
                'phien_ban_du_lieu' => (int) $phien->phien_ban_du_lieu + 1,
                'ma_lan_hoan_thanh' => $maHoanThanh,
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            $buoi->forceFill(['trang_thai' => 'HOAN_THANH', 'ngay_cap_nhat' => $hienTai])->save();

            return array_merge($this->query->duLieuPhien($phien->fresh()), ['replayed' => false]);
        }, 3);
    }

    private function duLieuHiep(HiepTap $hiep): array
    {
        return [
            'id' => (int) $hiep->getKey(),
            'set_id' => $hiep->ma_hiep_thuc_hien,
            'order' => (int) $hiep->so_thu_tu,
            'reps' => (int) $hiep->so_lan_lap,
            'weight_kg' => $hiep->khoi_luong_kg,
            'actual_rest_seconds' => $hiep->thoi_gian_nghi_thuc_te_giay === null ? null : (int) $hiep->thoi_gian_nghi_thuc_te_giay,
            'completed_at' => $hiep->hoan_thanh_luc?->toISOString(),
            'revision' => (int) $hiep->phien_ban_du_lieu,
        ];
    }

    private function damBaoUuid(string $giaTri, string $maLoi): void
    {
        if (! Str::isUuid($giaTri)) {
            throw new WorkoutWorkflowException('Idempotency-Key phải là UUID hợp lệ.', 422, $maLoi);
        }
    }
}
