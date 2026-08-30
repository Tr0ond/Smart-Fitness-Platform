<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;

class WorkoutMemberService
{
    /** Lấy hồ sơ Member đang hoạt động từ principal, không nhận member ID từ client. */
    public function hoiVienCuaNguoiDung(NguoiDung $nguoiDung, bool $lock = false): HoSoHoiVien
    {
        if ($nguoiDung->trang_thai !== 'HOAT_DONG' || ! $this->coVaiTroMember($nguoiDung)) {
            throw new WorkoutWorkflowException('Chỉ hội viên đang hoạt động được sử dụng Workout.', 403, 'MEMBER_ROLE_REQUIRED');
        }

        $truyVan = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey());
        if ($lock) {
            $truyVan->lockForUpdate();
        }

        $hoiVien = $truyVan->first();
        if (! $hoiVien instanceof HoSoHoiVien) {
            throw new WorkoutWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
        }

        return $hoiVien;
    }

    /** Kiểm tra role MEMBER còn hiệu lực tại thời điểm nghiệp vụ. */
    private function coVaiTroMember(NguoiDung $nguoiDung): bool
    {
        return $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->where('ma_vai_tro', 'MEMBER'))
            ->exists();
    }
}
