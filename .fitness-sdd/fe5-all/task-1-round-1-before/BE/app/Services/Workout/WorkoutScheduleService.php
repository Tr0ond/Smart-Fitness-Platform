<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\BuoiTapDuKien;
use App\Models\HoSoHoiVien;
use App\Models\KeHoachTap;
use App\Models\NgayTrongKeHoach;
use App\Models\NguoiDung;
use App\Models\PhienBanKeHoachTap;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkoutScheduleService
{
    public const SO_NGAY_TOI_DA = 92;

    public function __construct(private readonly WorkoutMemberService $members) {}

    /** Đọc lịch self-owned trong khoảng ngày hữu hạn, theo thứ tự ổn định. */
    public function danhSach(NguoiDung $nguoiDung, string $tuNgay, string $denNgay): array
    {
        return $this->danhSachChoHoiVien($this->members->hoiVienCuaNguoiDung($nguoiDung), $tuNgay, $denNgay);
    }

    /** Trả lịch chính thức theo hồ sơ Member đã được xác thực phạm vi. */
    public function danhSachChoHoiVien(HoSoHoiVien $hoiVien, string $tuNgay, string $denNgay): array
    {
        [$tu, $den] = $this->khoangNgay($tuNgay, $denNgay);

        return BuoiTapDuKien::query()
            ->with(['ngayTrongKeHoach', 'phienTap'])
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->whereBetween('ngay_tap', [$tu->toDateString(), $den->toDateString()])
            ->orderBy('ngay_tap')->orderBy('gio_bat_dau_du_kien')->orderBy('id')
            ->get()->map(fn (BuoiTapDuKien $buoi): array => $this->duLieuBuoi($buoi))->values()->all();
    }

    /**
     * Sinh các buổi cụ thể theo weekday của version; lịch cũ CHUA_TAP tương lai
     * được giữ hàng và chuyển DA_THAY_THE trước khi hàng version mới chiếm slot.
     */
    public function lapLich(int $hoiVienId, int $keHoachId, int $phienBanId, string $tuNgay, string $denNgay): array
    {
        [$tu, $den] = $this->khoangNgay($tuNgay, $denNgay);

        return DB::transaction(function () use ($hoiVienId, $keHoachId, $phienBanId, $tu, $den): array {
            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->find($hoiVienId);
            if ($hoiVien === null) {
                throw new WorkoutWorkflowException('Không tìm thấy hội viên.', 404, 'MEMBER_NOT_FOUND');
            }
            $keHoach = KeHoachTap::query()->where('hoi_vien_id', $hoiVienId)->lockForUpdate()->find($keHoachId);
            $phienBan = PhienBanKeHoachTap::query()->where('ke_hoach_tap_id', $keHoachId)->lockForUpdate()->find($phienBanId);
            if (! $keHoach instanceof KeHoachTap || ! $phienBan instanceof PhienBanKeHoachTap
                || (int) $keHoach->phien_ban_hien_tai_id !== $phienBanId) {
                throw new WorkoutWorkflowException('Plan/version không hợp lệ để lập lịch.', 409, 'WORKOUT_PLAN_VERSION_CONFLICT');
            }

            $cacNgay = NgayTrongKeHoach::query()->where('phien_ban_ke_hoach_tap_id', $phienBanId)->orderBy('id')->lockForUpdate()->get();
            $cacLich = BuoiTapDuKien::query()->where('hoi_vien_id', $hoiVienId)->whereBetween('ngay_tap', [$tu->toDateString(), $den->toDateString()])->orderBy('id')->lockForUpdate()->get();
            $ketQua = [];
            for ($ngay = $tu; $ngay->lessThanOrEqualTo($den); $ngay = $ngay->addDay()) {
                $thu = $ngay->dayOfWeekIso === 7 ? 8 : $ngay->dayOfWeekIso + 1;
                $ngayKeHoach = $cacNgay->firstWhere('thu_trong_tuan', $thu);
                if (! $ngayKeHoach instanceof NgayTrongKeHoach) {
                    continue;
                }

                $ngayChuoi = $ngay->toDateString();
                $dangGiuSlot = $cacLich->first(fn (BuoiTapDuKien $muc): bool => $muc->ngay_tap->toDateString() === $ngayChuoi
                    && in_array($muc->trang_thai, ['CHUA_TAP', 'DANG_TAP', 'HOAN_THANH', 'BO_QUA'], true));
                if ($dangGiuSlot instanceof BuoiTapDuKien && (int) $dangGiuSlot->phien_ban_ke_hoach_tap_id === $phienBanId) {
                    $ketQua[] = $dangGiuSlot;

                    continue;
                }
                if ($dangGiuSlot instanceof BuoiTapDuKien && $dangGiuSlot->trang_thai !== 'CHUA_TAP') {
                    continue;
                }

                $maBuoi = (string) Str::uuid();
                $thayTheId = null;
                if ($dangGiuSlot instanceof BuoiTapDuKien) {
                    if ($dangGiuSlot->phienTap()->exists()) {
                        continue;
                    }
                    $maBuoi = $dangGiuSlot->ma_buoi_logic;
                    $thayTheId = (int) $dangGiuSlot->getKey();
                    $dangGiuSlot->forceFill(['trang_thai' => 'DA_THAY_THE', 'ngay_cap_nhat' => CarbonImmutable::now('UTC')])->save();
                } else {
                    $lichDaNhuong = $cacLich->last(fn (BuoiTapDuKien $muc): bool => $muc->ngay_tap->toDateString() === $ngayChuoi
                        && in_array($muc->trang_thai, ['HUY', 'DA_THAY_THE'], true));
                    if ($lichDaNhuong instanceof BuoiTapDuKien) {
                        $maBuoi = $lichDaNhuong->ma_buoi_logic;
                        $thayTheId = (int) $lichDaNhuong->getKey();
                    }
                }

                $ketQua[] = $this->taoDaKhoa($hoiVienId, $keHoachId, $phienBanId, (int) $ngayKeHoach->getKey(), $ngayChuoi, $maBuoi, $thayTheId);
            }

            return collect($ketQua)->map(fn (BuoiTapDuKien $buoi): array => $this->duLieuBuoi($buoi))->values()->all();
        }, 3);
    }

    /** Tạo một slot lịch nội bộ; dùng cho workflow Apply và probe concurrency. */
    public function taoMotBuoi(int $hoiVienId, int $keHoachId, int $phienBanId, int $ngayKeHoachId, string $ngayTap, ?string $maBuoi = null): BuoiTapDuKien
    {
        try {
            return DB::transaction(function () use ($hoiVienId, $keHoachId, $phienBanId, $ngayKeHoachId, $ngayTap, $maBuoi): BuoiTapDuKien {
                HoSoHoiVien::query()->lockForUpdate()->findOrFail($hoiVienId);
                KeHoachTap::query()->where('hoi_vien_id', $hoiVienId)->lockForUpdate()->findOrFail($keHoachId);
                PhienBanKeHoachTap::query()->where('ke_hoach_tap_id', $keHoachId)->lockForUpdate()->findOrFail($phienBanId);
                NgayTrongKeHoach::query()->where('phien_ban_ke_hoach_tap_id', $phienBanId)->lockForUpdate()->findOrFail($ngayKeHoachId);

                return $this->taoDaKhoa($hoiVienId, $keHoachId, $phienBanId, $ngayKeHoachId, $ngayTap, $maBuoi ?? (string) Str::uuid(), null);
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new WorkoutWorkflowException('Ngày này đã có buổi tập còn hiệu lực.', 409, 'WORKOUT_DATE_SLOT_CONFLICT');
            }
            throw $exception;
        }
    }

    /** Member đánh dấu bỏ qua; BO_QUA vẫn giữ slot và không tạo Session. */
    public function boQua(NguoiDung $nguoiDung, int $buoiId): array
    {
        return DB::transaction(function () use ($nguoiDung, $buoiId): array {
            $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
            $buoi = BuoiTapDuKien::query()->where('hoi_vien_id', $hoiVien->getKey())->lockForUpdate()->find($buoiId);
            if (! $buoi instanceof BuoiTapDuKien) {
                throw new WorkoutWorkflowException('Không tìm thấy buổi tập dự kiến.', 404, 'SCHEDULED_WORKOUT_NOT_FOUND');
            }
            if ($buoi->trang_thai === 'BO_QUA') {
                return $this->duLieuBuoi($buoi);
            }
            if ($buoi->trang_thai !== 'CHUA_TAP' || $buoi->phienTap()->exists()) {
                throw new WorkoutWorkflowException('Buổi tập không thể đánh dấu bỏ qua.', 409, 'SCHEDULED_WORKOUT_NOT_SKIPPABLE');
            }
            $buoi->forceFill(['trang_thai' => 'BO_QUA', 'ngay_cap_nhat' => CarbonImmutable::now('UTC')])->save();

            return $this->duLieuBuoi($buoi->fresh());
        }, 3);
    }

    /** Hủy lịch nội bộ khi workflow regeneration cho phép; HUY trả generated slot về NULL. */
    public function huyNoiBo(int $hoiVienId, int $buoiId): BuoiTapDuKien
    {
        return DB::transaction(function () use ($hoiVienId, $buoiId): BuoiTapDuKien {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($hoiVienId);
            $buoi = BuoiTapDuKien::query()->where('hoi_vien_id', $hoiVienId)->lockForUpdate()->find($buoiId);
            if (! $buoi instanceof BuoiTapDuKien || $buoi->trang_thai !== 'CHUA_TAP' || $buoi->phienTap()->exists()) {
                throw new WorkoutWorkflowException('Buổi tập không thể hủy.', 409, 'SCHEDULED_WORKOUT_NOT_CANCELLABLE');
            }
            $buoi->forceFill(['trang_thai' => 'HUY', 'ngay_cap_nhat' => CarbonImmutable::now('UTC')])->save();

            return $buoi->fresh();
        }, 3);
    }

    private function taoDaKhoa(int $hoiVienId, int $keHoachId, int $phienBanId, int $ngayKeHoachId, string $ngayTap, string $maBuoi, ?int $thayTheId): BuoiTapDuKien
    {
        return BuoiTapDuKien::query()->create([
            'hoi_vien_id' => $hoiVienId,
            'ke_hoach_tap_id' => $keHoachId,
            'phien_ban_ke_hoach_tap_id' => $phienBanId,
            'ngay_trong_ke_hoach_id' => $ngayKeHoachId,
            'ma_buoi_logic' => $maBuoi,
            'ngay_tap' => $ngayTap,
            'gio_bat_dau_du_kien' => null,
            'gio_ket_thuc_du_kien' => null,
            'trang_thai' => 'CHUA_TAP',
            'thay_the_buoi_tap_id' => $thayTheId,
            'ngay_tao' => CarbonImmutable::now('UTC'),
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);
    }

    private function khoangNgay(string $tuNgay, string $denNgay): array
    {
        try {
            $tu = CarbonImmutable::createFromFormat('!Y-m-d', $tuNgay, 'Asia/Ho_Chi_Minh');
            $den = CarbonImmutable::createFromFormat('!Y-m-d', $denNgay, 'Asia/Ho_Chi_Minh');
        } catch (\Throwable) {
            throw new WorkoutWorkflowException('Khoảng ngày không hợp lệ.', 422, 'INVALID_SCHEDULE_RANGE');
        }
        if ($tu === false || $den === false || $den->lessThan($tu) || $tu->diffInDays($den) > self::SO_NGAY_TOI_DA - 1) {
            throw new WorkoutWorkflowException('Khoảng lịch tối đa 92 ngày.', 422, 'INVALID_SCHEDULE_RANGE');
        }

        return [$tu, $den];
    }

    private function duLieuBuoi(BuoiTapDuKien $buoi): array
    {
        return [
            'id' => (int) $buoi->getKey(),
            'plan_id' => (int) $buoi->ke_hoach_tap_id,
            'version_id' => (int) $buoi->phien_ban_ke_hoach_tap_id,
            'plan_day_id' => (int) $buoi->ngay_trong_ke_hoach_id,
            'logical_id' => $buoi->ma_buoi_logic,
            'date' => $buoi->ngay_tap?->toDateString(),
            'planned_start' => $buoi->gio_bat_dau_du_kien,
            'planned_end' => $buoi->gio_ket_thuc_du_kien,
            'status' => $buoi->trang_thai,
            'replaces_scheduled_workout_id' => $buoi->thay_the_buoi_tap_id === null ? null : (int) $buoi->thay_the_buoi_tap_id,
            'name' => $buoi->ngayTrongKeHoach?->ten_ngay,
            'session_id' => $buoi->phienTap?->getKey() === null ? null : (int) $buoi->phienTap->getKey(),
        ];
    }
}
