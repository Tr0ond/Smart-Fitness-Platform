<?php

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanQuyenNguoiDung;
use App\Models\TheTruyCap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountManagementService
{
    private const TRANG_THAI_HOP_LE = ['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'];

    public function __construct(private readonly AdminActorGuard $guard) {}

    /** @return array{items: array<int, array<string, mixed>>, pagination: array<string, int>} */
    public function danhSach(NguoiDung $actor, array $boLoc): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $truyVan = NguoiDung::query()
            ->where('chi_nhanh_id', $actor->chi_nhanh_id)
            ->with([
                'chiNhanh:id,ma_chi_nhanh,ten_chi_nhanh',
                'hoSoHoiVien:id,nguoi_dung_id,ma_hoi_vien',
                'hoSoHuanLuyenVien:id,nguoi_dung_id,ma_huan_luyen_vien,trang_thai',
                'phanQuyenNguoiDungsTheoNguoiDung.vaiTro:id,ma_vai_tro,ten_vai_tro',
            ]);

        $tuKhoa = trim((string) ($boLoc['search'] ?? ''));
        if ($tuKhoa !== '') {
            $mau = '%'.addcslashes($tuKhoa, '\\%_').'%';
            $email = mb_strtolower($tuKhoa, 'UTF-8');
            $truyVan->where(function ($query) use ($tuKhoa, $mau, $email): void {
                $query->where('ho_ten', 'like', $mau)
                    ->orWhere('thu_dien_tu', 'like', '%'.addcslashes($email, '\\%_').'%')
                    ->orWhere('so_dien_thoai', 'like', $mau)
                    ->orWhereHas('hoSoHoiVien', fn ($member) => $member->where('ma_hoi_vien', 'like', $mau))
                    ->orWhereHas('hoSoHuanLuyenVien', fn ($trainer) => $trainer->where('ma_huan_luyen_vien', 'like', $mau));
                if (ctype_digit($tuKhoa)) {
                    $query->orWhereKey((int) $tuKhoa);
                }
            });
        }

        if (isset($boLoc['status'])) {
            $truyVan->where('trang_thai', (string) $boLoc['status']);
        }
        if (isset($boLoc['role'])) {
            $maVaiTro = (string) $boLoc['role'];
            $truyVan->whereHas(
                'phanQuyenNguoiDungsTheoNguoiDung',
                fn ($phanQuyen) => $phanQuyen
                    ->whereNull('thu_hoi_luc')
                    ->whereHas('vaiTro', fn ($vaiTro) => $vaiTro->where('ma_vai_tro', $maVaiTro)),
            );
        }

        $phanTrang = $truyVan->orderBy('id')->paginate((int) ($boLoc['per_page'] ?? 20));

        return [
            'items' => collect($phanTrang->items())
                ->map(fn (NguoiDung $nguoiDung): array => $this->duLieuTaiKhoan($nguoiDung))
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $phanTrang->currentPage(),
                'per_page' => $phanTrang->perPage(),
                'total' => $phanTrang->total(),
                'last_page' => $phanTrang->lastPage(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function chiTiet(NguoiDung $actor, int $taiKhoanId): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $nguoiDung = $this->truyVanChiTiet()
            ->where('chi_nhanh_id', $actor->chi_nhanh_id)
            ->find($taiKhoanId);
        if ($nguoiDung === null) {
            throw new AuthWorkflowException('Không tìm thấy tài khoản.', 404, 'ACCOUNT_NOT_FOUND');
        }

        return $this->duLieuTaiKhoan($nguoiDung);
    }

    /** @return array<string, mixed> */
    public function capNhatTrangThai(NguoiDung $actor, int $taiKhoanId, string $trangThai): array
    {
        if (! in_array($trangThai, self::TRANG_THAI_HOP_LE, true)) {
            throw new AuthWorkflowException('Trạng thái tài khoản không hợp lệ.', 422, 'INVALID_ACCOUNT_STATUS');
        }

        return DB::transaction(function () use ($actor, $taiKhoanId, $trangThai): array {
            $cacTaiKhoan = $this->guard->khoaActorVaDoiTuong($actor, $taiKhoanId);
            /** @var NguoiDung $nguoiDung */
            $nguoiDung = $cacTaiKhoan->get($taiKhoanId);
            $trangThaiCu = (string) $nguoiDung->trang_thai;
            if ($trangThaiCu === $trangThai) {
                return $this->duLieuTaiKhoan($this->truyVanChiTiet()->findOrFail($taiKhoanId));
            }

            if ($trangThai !== 'HOAT_DONG') {
                $this->guard->damBaoKhongVoHieuHoaAdminHoatDongCuoiCung($nguoiDung);
            }

            $hienTai = CarbonImmutable::now('UTC');
            $nguoiDung->forceFill([
                'trang_thai' => $trangThai,
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            if ($trangThai !== 'HOAT_DONG') {
                TheTruyCap::query()
                    ->where('nguoi_dung_id', $nguoiDung->getKey())
                    ->whereNull('thu_hoi_luc')
                    ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);
            }

            NhatKyHeThong::query()->create([
                'nguoi_thuc_hien_id' => $actor->getKey(),
                'loai_tac_nhan' => 'NGUOI_DUNG',
                'hanh_dong' => 'CAP_NHAT_TRANG_THAI_TAI_KHOAN',
                'loai_doi_tuong' => 'NGUOI_DUNG',
                'dinh_danh_doi_tuong' => $nguoiDung->getKey(),
                'khoa_tuong_quan' => (string) Str::uuid(),
                'du_lieu_truoc' => ['trang_thai' => $trangThaiCu],
                'du_lieu_sau' => ['trang_thai' => $trangThai],
                'ket_qua' => 'THANH_CONG',
                'thuc_hien_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);

            return $this->duLieuTaiKhoan($this->truyVanChiTiet()->findOrFail($taiKhoanId));
        }, 3);
    }

    private function truyVanChiTiet()
    {
        return NguoiDung::query()->with([
            'chiNhanh:id,ma_chi_nhanh,ten_chi_nhanh',
            'hoSoHoiVien:id,nguoi_dung_id,ma_hoi_vien',
            'hoSoHuanLuyenVien:id,nguoi_dung_id,ma_huan_luyen_vien,trang_thai',
            'phanQuyenNguoiDungsTheoNguoiDung.vaiTro:id,ma_vai_tro,ten_vai_tro',
        ]);
    }

    /** @return array<string, mixed> */
    private function duLieuTaiKhoan(NguoiDung $nguoiDung): array
    {
        $phanQuyen = $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung
            ->sortBy(fn (PhanQuyenNguoiDung $muc): string => (string) $muc->vaiTro?->ma_vai_tro)
            ->map(fn (PhanQuyenNguoiDung $muc): array => [
                'assignment_id' => (int) $muc->getKey(),
                'code' => (string) $muc->vaiTro?->ma_vai_tro,
                'name' => (string) $muc->vaiTro?->ten_vai_tro,
                'active' => $muc->thu_hoi_luc === null,
                'granted_at' => $muc->cap_luc?->toISOString(),
                'revoked_at' => $muc->thu_hoi_luc?->toISOString(),
            ])
            ->values()
            ->all();

        return [
            'id' => (int) $nguoiDung->getKey(),
            'name' => (string) $nguoiDung->ho_ten,
            'email' => (string) $nguoiDung->thu_dien_tu,
            'phone' => $nguoiDung->so_dien_thoai,
            'avatar' => $nguoiDung->anh_dai_dien,
            'status' => (string) $nguoiDung->trang_thai,
            'email_verified_at' => $nguoiDung->xac_minh_thu_luc?->toISOString(),
            'last_login_at' => $nguoiDung->dang_nhap_gan_nhat_luc?->toISOString(),
            'branch' => $nguoiDung->chiNhanh === null ? null : [
                'id' => (int) $nguoiDung->chiNhanh->getKey(),
                'code' => (string) $nguoiDung->chiNhanh->ma_chi_nhanh,
                'name' => (string) $nguoiDung->chiNhanh->ten_chi_nhanh,
            ],
            'member_profile' => $nguoiDung->hoSoHoiVien === null ? null : [
                'id' => (int) $nguoiDung->hoSoHoiVien->getKey(),
                'code' => (string) $nguoiDung->hoSoHoiVien->ma_hoi_vien,
            ],
            'trainer_profile' => $nguoiDung->hoSoHuanLuyenVien === null ? null : [
                'id' => (int) $nguoiDung->hoSoHuanLuyenVien->getKey(),
                'code' => (string) $nguoiDung->hoSoHuanLuyenVien->ma_huan_luyen_vien,
                'status' => (string) $nguoiDung->hoSoHuanLuyenVien->trang_thai,
            ],
            'roles' => $phanQuyen,
            'created_at' => $nguoiDung->ngay_tao?->toISOString(),
            'updated_at' => $nguoiDung->ngay_cap_nhat?->toISOString(),
        ];
    }
}
