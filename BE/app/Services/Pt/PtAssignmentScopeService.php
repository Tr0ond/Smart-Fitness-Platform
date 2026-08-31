<?php

namespace App\Services\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PtAssignmentScopeService
{
    /** Khóa Member theo ID route và kiểm tra account/Role MEMBER đang hiệu lực. */
    public function khoaHoiVien(int $hoiVienId): HoSoHoiVien
    {
        $hoiVien = HoSoHoiVien::query()->lockForUpdate()->find($hoiVienId);
        if (! $hoiVien instanceof HoSoHoiVien) {
            throw new PtWorkflowException('Không tìm thấy hội viên.', 404, 'MEMBER_NOT_FOUND');
        }
        $this->damBaoVaiTroHoatDong((int) $hoiVien->nguoi_dung_id, 'MEMBER', 'MEMBER_NOT_ACTIVE');

        return $hoiVien;
    }

    /** Khóa hồ sơ Member của principal; không nhận ownership từ payload. */
    public function khoaHoiVienCuaNguoiDung(NguoiDung $nguoiDung): HoSoHoiVien
    {
        $hoiVien = HoSoHoiVien::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->lockForUpdate()
            ->first();
        if (! $hoiVien instanceof HoSoHoiVien) {
            throw new PtWorkflowException('Không tìm thấy hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
        }
        $this->damBaoVaiTroHoatDong((int) $nguoiDung->getKey(), 'MEMBER', 'MEMBER_ROLE_REQUIRED');

        return $hoiVien;
    }

    /** Khóa account, Role và profile PT để chống thu hồi quyền giữa transaction. */
    public function khoaHuanLuyenVien(NguoiDung $nguoiDung): HoSoHuanLuyenVien
    {
        $this->damBaoVaiTroHoatDong((int) $nguoiDung->getKey(), 'PT', 'TRAINER_ROLE_REQUIRED');
        $hoSo = HoSoHuanLuyenVien::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->lockForUpdate()
            ->first();
        if (! $hoSo instanceof HoSoHuanLuyenVien || $hoSo->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Hồ sơ PT không hoạt động.', 403, 'TRAINER_NOT_AVAILABLE');
        }

        return $hoSo;
    }

    /** Khóa toàn bộ lịch sử phân công của Member theo thứ tự ID thống nhất. */
    public function khoaCacPhanCong(int $hoiVienId): Collection
    {
        return PhanCongHuanLuyenVien::query()
            ->where('hoi_vien_id', $hoiVienId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /** Chọn đúng hàng phân công đang hiệu lực của PT; không suy từ ID client. */
    public function phanCongHienTai(
        Collection $cacPhanCong,
        int $huanLuyenVienId,
        CarbonImmutable $thoiDiem,
    ): PhanCongHuanLuyenVien {
        $phanCong = $cacPhanCong->first(fn (PhanCongHuanLuyenVien $muc): bool => (int) $muc->huan_luyen_vien_id === $huanLuyenVienId
            && $this->dangHieuLuc($muc, $thoiDiem));
        if (! $phanCong instanceof PhanCongHuanLuyenVien) {
            throw new PtWorkflowException('Không tìm thấy phân công PT đang hiệu lực.', 404, 'ASSIGNMENT_NOT_FOUND');
        }

        return $phanCong;
    }

    /** Revalidate chính assignment đã sinh Proposal, không cho hàng phân công mới thay thế. */
    public function damBaoPhanCongNguon(
        Collection $cacPhanCong,
        int $phanCongId,
        int $hoiVienId,
        CarbonImmutable $thoiDiem,
    ): PhanCongHuanLuyenVien {
        $phanCong = $cacPhanCong->firstWhere('id', $phanCongId);
        if (! $phanCong instanceof PhanCongHuanLuyenVien
            || (int) $phanCong->hoi_vien_id !== $hoiVienId
            || ! $this->dangHieuLuc($phanCong, $thoiDiem)) {
            throw new PtWorkflowException('Phân công nguồn không còn hiệu lực.', 409, 'PROPOSAL_ASSIGNMENT_CONFLICT');
        }

        $taiKhoanId = HoSoHuanLuyenVien::query()
            ->whereKey($phanCong->huan_luyen_vien_id)
            ->where('trang_thai', 'HOAT_DONG')
            ->lockForUpdate()
            ->value('nguoi_dung_id');
        if ($taiKhoanId === null) {
            throw new PtWorkflowException('PT nguồn không còn hoạt động.', 409, 'PROPOSAL_ASSIGNMENT_CONFLICT');
        }
        try {
            $this->damBaoVaiTroHoatDong((int) $taiKhoanId, 'PT', 'PROPOSAL_ASSIGNMENT_CONFLICT');
        } catch (PtWorkflowException) {
            throw new PtWorkflowException('PT nguồn không còn quyền hiệu lực.', 409, 'PROPOSAL_ASSIGNMENT_CONFLICT');
        }

        return $phanCong;
    }

    public function dangHieuLuc(PhanCongHuanLuyenVien $phanCong, CarbonImmutable $thoiDiem): bool
    {
        return CarbonImmutable::instance($phanCong->ngay_bat_dau)->lessThanOrEqualTo($thoiDiem)
            && ($phanCong->ngay_ket_thuc === null
                || $thoiDiem->lessThan(CarbonImmutable::instance($phanCong->ngay_ket_thuc)));
    }

    private function damBaoVaiTroHoatDong(int $nguoiDungId, string $vaiTro, string $maLoi): void
    {
        $taiKhoan = NguoiDung::query()->lockForUpdate()->find($nguoiDungId);
        $phanQuyen = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $nguoiDungId)
            ->where('vai_tro.ma_vai_tro', $vaiTro)
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc')
            ->lockForUpdate()
            ->first(['phan_quyen_nguoi_dung.id']);
        if (! $taiKhoan instanceof NguoiDung || $taiKhoan->trang_thai !== 'HOAT_DONG' || $phanQuyen === null) {
            throw new PtWorkflowException('Tài khoản hoặc vai trò không còn hiệu lực.', 403, $maLoi);
        }
    }
}
