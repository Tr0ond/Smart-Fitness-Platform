<?php

namespace App\Services\Pt;

use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Services\Workout\WorkoutPlanQueryService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PtMemberWorkspaceService
{
    private const BUSINESS_TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly PtAssignmentScopeService $scope,
        private readonly WorkoutPlanQueryService $plans,
        private readonly WorkoutScheduleService $schedules,
        private readonly WorkoutSessionQueryService $sessions,
    ) {}

    /**
     * Trả coaching profile an toàn của Member mà PT đang phụ trách.
     * Input là PT đã xác thực và Member ID từ route; process khóa Member, PT và
     * assignment hiện tại bằng server UTC. Output chỉ chứa trường profile được
     * phép; không có side effect ngoài transaction đọc ngắn.
     */
    public function chiTietHoiVien(NguoiDung $pt, int $hoiVienId): array
    {
        return $this->trongPhamVi($pt, $hoiVienId, function (HoSoHoiVien $hoiVien, PhanCongHuanLuyenVien $phanCong): array {
            return [
                'member' => [
                    'id' => (int) $hoiVien->getKey(),
                    'code' => $hoiVien->ma_hoi_vien,
                    'name' => $hoiVien->nguoiDung?->ho_ten,
                    'training_goal' => $hoiVien->muc_tieu_tap_luyen,
                    'training_experience' => $hoiVien->kinh_nghiem_tap_luyen,
                    'desired_training_days' => $hoiVien->so_ngay_tap_mong_muon === null
                        ? null
                        : (int) $hoiVien->so_ngay_tap_mong_muon,
                    'session_duration_minutes' => $hoiVien->thoi_luong_moi_buoi_phut === null
                        ? null
                        : (int) $hoiVien->thoi_luong_moi_buoi_phut,
                    'profile_version' => (int) $hoiVien->phien_ban_ho_so,
                    'updated_at' => $hoiVien->ngay_cap_nhat?->toISOString(),
                ],
                'assignment' => $this->duLieuPhanCong($phanCong),
            ];
        });
    }

    /**
     * Đọc Plan chính thức hiện tại và lịch tương lai của Member trong assignment.
     * Query service dùng cùng mapper với Member-self; cửa sổ lịch được tính từ
     * ngày hiện tại theo timezone nghiệp vụ và không kiểm tra Membership hay Proposal.
     */
    public function keHoachHienTai(NguoiDung $pt, int $hoiVienId): array
    {
        return $this->trongPhamVi($pt, $hoiVienId, function (HoSoHoiVien $hoiVien): array {
            [$tuNgay, $denNgay] = $this->cuaSoLichTuongLai();

            return [
                'plan' => $this->plans->hienTaiChoHoiVien($hoiVien),
                'future_schedule' => $this->schedules->danhSachChoHoiVien($hoiVien, $tuNgay, $denNgay),
                'schedule_window' => [
                    'from' => $tuNgay,
                    'to' => $denNgay,
                    'timezone' => self::BUSINESS_TIMEZONE,
                ],
            ];
        });
    }

    /** Trả history session dạng cursor bounded theo Member đã được xác thực assignment. */
    public function danhSachPhien(NguoiDung $pt, int $hoiVienId, int $limit = 20, ?int $truocId = null): array
    {
        return $this->trongPhamVi(
            $pt,
            $hoiVienId,
            fn (HoSoHoiVien $hoiVien): array => $this->sessions->danhSachChoHoiVien($hoiVien, $limit, $truocId),
        );
    }

    /** Trả immutable session snapshot, che giấu session của Member khác bằng 404. */
    public function chiTietPhien(NguoiDung $pt, int $hoiVienId, int $phienId): array
    {
        return $this->trongPhamVi(
            $pt,
            $hoiVienId,
            fn (HoSoHoiVien $hoiVien): array => $this->sessions->chiTietChoHoiVien($hoiVien, $phienId),
        );
    }

    /**
     * Chốt exact-assignment scope cho mọi PT workspace GET trong một transaction đọc.
     * Scope service là nguồn duy nhất của predicate [start,end), nhờ đó PT cũ,
     * PT khác, assignment tương lai và assignment đã kết thúc đều bị che 404.
     *
     * @param  callable(HoSoHoiVien, PhanCongHuanLuyenVien): mixed  $hanhDong
     */
    private function trongPhamVi(NguoiDung $pt, int $hoiVienId, callable $hanhDong): mixed
    {
        return DB::transaction(function () use ($pt, $hoiVienId, $hanhDong): mixed {
            $hienTai = CarbonImmutable::now('UTC');
            $hoiVien = $this->scope->khoaHoiVien($hoiVienId);
            $cacPhanCong = $this->scope->khoaCacPhanCong($hoiVienId);
            $hoSoPt = $this->scope->khoaHuanLuyenVien($pt);
            $phanCong = $this->scope->phanCongHienTai($cacPhanCong, (int) $hoSoPt->getKey(), $hienTai);
            $hoiVien->loadMissing('nguoiDung');

            return $hanhDong($hoiVien, $phanCong);
        }, 3);
    }

    /** @return array{0: string, 1: string} */
    private function cuaSoLichTuongLai(): array
    {
        $tuNgay = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfDay();
        $denNgay = $tuNgay->addDays(WorkoutScheduleService::SO_NGAY_TOI_DA - 1);

        return [$tuNgay->toDateString(), $denNgay->toDateString()];
    }

    /** @return array{id: int, start_at: string, end_at: string|null} */
    private function duLieuPhanCong(PhanCongHuanLuyenVien $phanCong): array
    {
        return [
            'id' => (int) $phanCong->getKey(),
            'start_at' => CarbonImmutable::instance($phanCong->ngay_bat_dau)->toISOString(),
            'end_at' => $phanCong->ngay_ket_thuc === null
                ? null
                : CarbonImmutable::instance($phanCong->ngay_ket_thuc)->toISOString(),
        ];
    }
}
