<?php

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\NguoiDung;
use App\Models\PhanQuyenNguoiDung;
use App\Models\VaiTro;
use Illuminate\Support\Collection;

class AdminActorGuard
{
    /** Kiểm tra lại quyền ADMIN từ Database cho các truy vấn không mutation. */
    public function damBaoQuanTriVienHienTai(NguoiDung $nguoiDung): void
    {
        $hopLe = $nguoiDung->trang_thai === 'HOAT_DONG'
            && $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
                ->whereNull('thu_hoi_luc')
                ->whereHas('vaiTro', fn ($truyVan) => $truyVan->where('ma_vai_tro', 'ADMIN'))
                ->exists();

        if (! $hopLe) {
            throw new AuthWorkflowException('Không có quyền quản trị tài khoản.', 403, 'ADMIN_ROLE_REQUIRED');
        }
    }

    /** Khóa actor và kiểm tra lại role cho transaction không có Account target. */
    public function khoaVaDamBaoQuanTriVien(NguoiDung $actor): NguoiDung
    {
        $actorDaKhoa = NguoiDung::query()->lockForUpdate()->find($actor->getKey());
        if (! $actorDaKhoa instanceof NguoiDung) {
            throw new AuthWorkflowException('Không có quyền quản trị tài khoản.', 403, 'ADMIN_ROLE_REQUIRED');
        }
        $this->damBaoQuanTriVienHienTai($actorDaKhoa);

        return $actorDaKhoa;
    }

    /**
     * Khóa actor/target theo thứ tự ID cố định rồi kiểm tra lại active ADMIN.
     * Mọi Role mutation dùng cùng khóa target nên revoke có hiệu lực tuyến tính.
     *
     * @return Collection<int, NguoiDung>
     */
    public function khoaActorVaDoiTuong(NguoiDung $actor, int $doiTuongId): Collection
    {
        $ids = array_values(array_unique([(int) $actor->getKey(), $doiTuongId]));
        sort($ids, SORT_NUMERIC);
        $cacTaiKhoan = NguoiDung::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (NguoiDung $nguoiDung): int => (int) $nguoiDung->getKey());

        $actorDaKhoa = $cacTaiKhoan->get((int) $actor->getKey());
        if (! $actorDaKhoa instanceof NguoiDung) {
            throw new AuthWorkflowException('Không có quyền quản trị tài khoản.', 403, 'ADMIN_ROLE_REQUIRED');
        }
        $this->damBaoQuanTriVienHienTai($actorDaKhoa);

        if (! $cacTaiKhoan->get($doiTuongId) instanceof NguoiDung) {
            throw new AuthWorkflowException('Không tìm thấy tài khoản.', 404, 'ACCOUNT_NOT_FOUND');
        }

        return $cacTaiKhoan;
    }

    /**
     * Role ADMIN là mutex chung cho các thay đổi có thể làm hệ thống không còn
     * quản trị viên hoạt động. Caller đã khóa account target trước khi gọi.
     */
    public function damBaoKhongVoHieuHoaAdminHoatDongCuoiCung(NguoiDung $taiKhoan): void
    {
        $vaiTroAdmin = $this->khoaVaiTroAdmin();
        $laAdminHoatDong = $taiKhoan->trang_thai === 'HOAT_DONG'
            && PhanQuyenNguoiDung::query()
                ->where('nguoi_dung_id', $taiKhoan->getKey())
                ->where('vai_tro_id', $vaiTroAdmin->getKey())
                ->whereNull('thu_hoi_luc')
                ->exists();

        if (! $laAdminHoatDong) {
            return;
        }

        $soAdminHoatDong = NguoiDung::query()
            ->where('trang_thai', 'HOAT_DONG')
            ->whereHas('phanQuyenNguoiDungsTheoNguoiDung', function ($truyVan) use ($vaiTroAdmin): void {
                $truyVan->where('vai_tro_id', $vaiTroAdmin->getKey())
                    ->whereNull('thu_hoi_luc');
            })
            ->count();

        if ($soAdminHoatDong <= 1) {
            throw new AuthWorkflowException(
                'Không thể vô hiệu hóa quản trị viên hoạt động cuối cùng.',
                409,
                'LAST_ACTIVE_ADMIN_PROTECTED',
            );
        }
    }

    /** Khóa hàng role ADMIN để serialize bootstrap, revoke và khóa tài khoản. */
    public function khoaVaiTroAdmin(): VaiTro
    {
        $vaiTro = VaiTro::query()
            ->where('ma_vai_tro', 'ADMIN')
            ->lockForUpdate()
            ->first();

        if (! $vaiTro instanceof VaiTro) {
            throw new AuthWorkflowException('Danh mục vai trò chưa sẵn sàng.', 503, 'ROLE_CONFIGURATION_REQUIRED');
        }

        return $vaiTro;
    }
}
