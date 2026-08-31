<?php

namespace App\Services\Progress;

use App\Exceptions\Progress\ProgressWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProgressAuthorizationService
{
    /** Member principal chỉ có thể lấy chính hồ sơ của mình. */
    public function hoiVienCuaNguoiDung(NguoiDung $nguoiDung): HoSoHoiVien
    {
        $this->damBaoTaiKhoanVaVaiTro($nguoiDung, 'MEMBER', 'MEMBER_ROLE_REQUIRED');
        $hoiVien = HoSoHoiVien::query()
            ->with('nguoiDung.chiNhanh')
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->first();
        if (! $hoiVien instanceof HoSoHoiVien) {
            throw new ProgressWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
        }

        return $hoiVien;
    }

    /** PT chỉ đọc Member thuộc đúng phân công đang hiệu lực tại thời điểm hiện tại. */
    public function hoiVienDuocPhanCong(NguoiDung $nguoiDung, int $hoiVienId): HoSoHoiVien
    {
        $this->damBaoTaiKhoanVaVaiTro($nguoiDung, 'PT', 'TRAINER_ROLE_REQUIRED');
        $hoSoPt = HoSoHuanLuyenVien::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->where('trang_thai', 'HOAT_DONG')
            ->first();
        if (! $hoSoPt instanceof HoSoHuanLuyenVien) {
            throw new ProgressWorkflowException('Hồ sơ PT không hoạt động.', 403, 'TRAINER_NOT_AVAILABLE');
        }

        $hoiVien = HoSoHoiVien::query()->with('nguoiDung.chiNhanh')->find($hoiVienId);
        if (! $hoiVien instanceof HoSoHoiVien
            || ! $hoiVien->nguoiDung instanceof NguoiDung
            || $hoiVien->nguoiDung->trang_thai !== 'HOAT_DONG'
            || ! $this->coVaiTroHieuLuc((int) $hoiVien->nguoi_dung_id, 'MEMBER')) {
            throw new ProgressWorkflowException('Không tìm thấy hội viên được phân công.', 404, 'ASSIGNED_MEMBER_NOT_FOUND');
        }

        $hienTai = CarbonImmutable::now('UTC');
        $coPhanCong = DB::table('phan_cong_huan_luyen_vien')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('huan_luyen_vien_id', $hoSoPt->getKey())
            ->where('ngay_bat_dau', '<=', $hienTai)
            ->where(function ($query) use ($hienTai): void {
                $query->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>', $hienTai);
            })
            ->exists();
        if (! $coPhanCong) {
            throw new ProgressWorkflowException('Không tìm thấy hội viên được phân công.', 404, 'ASSIGNED_MEMBER_NOT_FOUND');
        }

        return $hoiVien;
    }

    private function damBaoTaiKhoanVaVaiTro(NguoiDung $nguoiDung, string $vaiTro, string $maLoi): void
    {
        if ($nguoiDung->trang_thai !== 'HOAT_DONG' || ! $this->coVaiTroHieuLuc((int) $nguoiDung->getKey(), $vaiTro)) {
            throw new ProgressWorkflowException('Tài khoản hoặc vai trò không còn hiệu lực.', 403, $maLoi);
        }
    }

    private function coVaiTroHieuLuc(int $nguoiDungId, string $maVaiTro): bool
    {
        return DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $nguoiDungId)
            ->where('vai_tro.ma_vai_tro', $maVaiTro)
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc')
            ->exists();
    }
}
