<?php

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\ChiNhanh;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminDashboardService
{
    public function __construct(private readonly AdminActorGuard $guard) {}

    /** Dashboard cơ bản, chỉ aggregate có scope branch và không mutation. */
    public function tongQuan(NguoiDung $actor, array $boLoc): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $actor->loadMissing('chiNhanh');
        $chiNhanh = $actor->chiNhanh;
        if (! $chiNhanh instanceof ChiNhanh) {
            throw new AuthWorkflowException('Không xác định được chi nhánh quản trị.', 409, 'ADMIN_BRANCH_REQUIRED');
        }
        $muiGio = $this->muiGio((string) $chiNhanh->mui_gio);
        $hienTai = CarbonImmutable::now('UTC');
        $homNay = $hienTai->setTimezone($muiGio)->startOfDay();
        $denNgay = isset($boLoc['to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $boLoc['to'], $muiGio)
            : $homNay;
        $tuNgay = isset($boLoc['from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $boLoc['from'], $muiGio)
            : $denNgay?->subDays(29);
        if ($tuNgay === false || $denNgay === false || $tuNgay === null || $denNgay === null) {
            throw new AuthWorkflowException('Khoảng Dashboard không hợp lệ.', 422, 'INVALID_DASHBOARD_PERIOD');
        }
        $tuUtc = $tuNgay->startOfDay()->utc();
        $denUtc = $denNgay->addDay()->startOfDay()->utc();
        $homNayUtc = $homNay->utc();
        $ngayMaiUtc = $homNay->addDay()->utc();
        $chiNhanhId = (int) $chiNhanh->getKey();

        $workoutTheoChiNhanh = DB::table('phien_tap')
            ->join('ho_so_hoi_vien', 'ho_so_hoi_vien.id', '=', 'phien_tap.hoi_vien_id')
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'ho_so_hoi_vien.nguoi_dung_id')
            ->where('nguoi_dung.chi_nhanh_id', $chiNhanhId)
            ->where('phien_tap.trang_thai', 'HOAN_THANH');
        $thanhToanTheoChiNhanh = DB::table('lan_thanh_toan')
            ->join('don_mua_goi', 'don_mua_goi.id', '=', 'lan_thanh_toan.don_mua_goi_id')
            ->join('ho_so_hoi_vien', 'ho_so_hoi_vien.id', '=', 'don_mua_goi.hoi_vien_id')
            ->join('nguoi_dung', 'nguoi_dung.id', '=', 'ho_so_hoi_vien.nguoi_dung_id')
            ->where('nguoi_dung.chi_nhanh_id', $chiNhanhId);

        return [
            'branch' => [
                'id' => $chiNhanhId,
                'timezone' => $muiGio,
            ],
            'period' => [
                'from' => $tuNgay->toDateString(),
                'to' => $denNgay->toDateString(),
                'inclusive_days' => $tuNgay->diffInDays($denNgay) + 1,
            ],
            'accounts' => [
                'active_members_count' => $this->demTaiKhoan($chiNhanhId, 'MEMBER', 'ho_so_hoi_vien'),
                'active_trainers_count' => $this->demTaiKhoan($chiNhanhId, 'PT', 'ho_so_huan_luyen_vien', true),
            ],
            'memberships' => [
                'active_terms_count' => $this->demKy($chiNhanhId, 'DANG_HOAT_DONG'),
                'awaiting_activation_terms_count' => $this->demKy($chiNhanhId, 'CHO_KICH_HOAT'),
            ],
            'activity' => [
                'check_ins_today_count' => DB::table('lich_su_vao_phong_tap')
                    ->where('chi_nhanh_id', $chiNhanhId)
                    ->where('vao_phong_luc', '>=', $homNayUtc)
                    ->where('vao_phong_luc', '<', $ngayMaiUtc)
                    ->count(),
                'completed_workouts_today_count' => (clone $workoutTheoChiNhanh)
                    ->where('phien_tap.ket_thuc_luc', '>=', $homNayUtc)
                    ->where('phien_tap.ket_thuc_luc', '<', $ngayMaiUtc)
                    ->count(),
                'completed_workouts_in_period_count' => (clone $workoutTheoChiNhanh)
                    ->where('phien_tap.ket_thuc_luc', '>=', $tuUtc)
                    ->where('phien_tap.ket_thuc_luc', '<', $denUtc)
                    ->count(),
            ],
            'payments' => [
                'successful_in_period_count' => (clone $thanhToanTheoChiNhanh)
                    ->where('lan_thanh_toan.trang_thai', 'THANH_CONG')
                    ->where('lan_thanh_toan.xac_nhan_luc', '>=', $tuUtc)
                    ->where('lan_thanh_toan.xac_nhan_luc', '<', $denUtc)
                    ->count(),
                'reconciliation_required_count' => (clone $thanhToanTheoChiNhanh)
                    ->where('lan_thanh_toan.trang_thai', 'CAN_DOI_SOAT')
                    ->count(),
            ],
            'pt' => [
                'active_assignments_count' => DB::table('phan_cong_huan_luyen_vien')
                    ->join('ho_so_hoi_vien', 'ho_so_hoi_vien.id', '=', 'phan_cong_huan_luyen_vien.hoi_vien_id')
                    ->join('nguoi_dung', 'nguoi_dung.id', '=', 'ho_so_hoi_vien.nguoi_dung_id')
                    ->where('nguoi_dung.chi_nhanh_id', $chiNhanhId)
                    ->where('phan_cong_huan_luyen_vien.ngay_bat_dau', '<=', $hienTai)
                    ->where(function ($query) use ($hienTai): void {
                        $query->whereNull('phan_cong_huan_luyen_vien.ngay_ket_thuc')
                            ->orWhere('phan_cong_huan_luyen_vien.ngay_ket_thuc', '>', $hienTai);
                    })->count(),
            ],
            'generated_at' => $hienTai->toISOString(),
        ];
    }

    private function demTaiKhoan(int $chiNhanhId, string $vaiTro, string $bangHoSo, bool $ptHoatDong = false): int
    {
        $query = DB::table('nguoi_dung')
            ->join('phan_quyen_nguoi_dung', 'phan_quyen_nguoi_dung.nguoi_dung_id', '=', 'nguoi_dung.id')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->join($bangHoSo, $bangHoSo.'.nguoi_dung_id', '=', 'nguoi_dung.id')
            ->where('nguoi_dung.chi_nhanh_id', $chiNhanhId)
            ->where('nguoi_dung.trang_thai', 'HOAT_DONG')
            ->where('vai_tro.ma_vai_tro', $vaiTro)
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc');
        if ($ptHoatDong) {
            $query->where($bangHoSo.'.trang_thai', 'HOAT_DONG');
        }

        return (int) $query->distinct()->count('nguoi_dung.id');
    }

    private function demKy(int $chiNhanhId, string $trangThai): int
    {
        return DB::table('ky_han_hoi_vien')
            ->join('dang_ky_goi_tap', 'dang_ky_goi_tap.id', '=', 'ky_han_hoi_vien.dang_ky_goi_tap_id')
            ->where('dang_ky_goi_tap.chi_nhanh_id', $chiNhanhId)
            ->where('ky_han_hoi_vien.trang_thai', $trangThai)
            ->count();
    }

    private function muiGio(string $muiGio): string
    {
        try {
            return (new DateTimeZone($muiGio))->getName();
        } catch (Throwable) {
            throw new AuthWorkflowException('Múi giờ chi nhánh không hợp lệ.', 409, 'INVALID_BRANCH_TIMEZONE');
        }
    }
}
