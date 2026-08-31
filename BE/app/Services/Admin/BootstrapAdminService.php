<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\ChiNhanh;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanQuyenNguoiDung;
use App\Support\EmailCanonicalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Khởi tạo đúng một ADMIN đầu tiên, không seed credential demo trên production. */
class BootstrapAdminService
{
    public function __construct(
        private readonly AdminActorGuard $adminGuard,
        private readonly EmailCanonicalizer $emailCanonicalizer,
    ) {}

    /** @param array{name: string, email: string, phone: string|null, branch: string} $duLieu */
    public function khoiTao(array $duLieu): array
    {
        $email = $this->emailCanonicalizer->chuanHoa($duLieu['email']);

        return DB::transaction(function () use ($duLieu, $email): array {
            $vaiTroAdmin = $this->adminGuard->khoaVaiTroAdmin();
            $adminHienTai = $this->adminHoatDong($vaiTroAdmin->getKey());

            if ($adminHienTai !== null) {
                if ($adminHienTai->thu_dien_tu === $email) {
                    return [
                        'transition' => 'UNCHANGED',
                        'account_id' => (int) $adminHienTai->getKey(),
                        'email' => $email,
                        'role' => 'ADMIN',
                    ];
                }

                throw new AuthWorkflowException(
                    'Hệ thống đã có quản trị viên hoạt động.',
                    409,
                    'ADMIN_ALREADY_BOOTSTRAPPED',
                );
            }

            $taiKhoanDaCo = NguoiDung::query()
                ->where('thu_dien_tu', $email)
                ->lockForUpdate()
                ->first();
            if ($taiKhoanDaCo !== null) {
                throw new AuthWorkflowException('Email đã được sử dụng.', 409, 'ACCOUNT_EMAIL_ALREADY_EXISTS');
            }

            $chiNhanh = $this->chiNhanhHoatDong((string) $duLieu['branch']);
            $hienTai = CarbonImmutable::now('UTC');
            $taiKhoan = NguoiDung::query()->create([
                'chi_nhanh_id' => $chiNhanh->getKey(),
                'ho_ten' => trim($duLieu['name']),
                'thu_dien_tu' => $email,
                'so_dien_thoai' => $this->soDienThoai($duLieu['phone'] ?? null),
                // Không có credential dùng được trước password-reset workflow.
                'mat_khau_bam' => Hash::make(bin2hex(random_bytes(32))),
                'anh_dai_dien' => null,
                'xac_minh_thu_luc' => null,
                'trang_thai' => 'HOAT_DONG',
                'dang_nhap_gan_nhat_luc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            $phanQuyen = PhanQuyenNguoiDung::query()->create([
                'nguoi_dung_id' => $taiKhoan->getKey(),
                'vai_tro_id' => $vaiTroAdmin->getKey(),
                'nguoi_cap_id' => null,
                'cap_luc' => $hienTai,
                'thu_hoi_luc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            NhatKyHeThong::query()->create([
                'nguoi_thuc_hien_id' => null,
                'loai_tac_nhan' => 'HE_THONG',
                'hanh_dong' => 'KHOI_TAO_ADMIN_DAU_TIEN',
                'loai_doi_tuong' => 'NGUOI_DUNG',
                'dinh_danh_doi_tuong' => $taiKhoan->getKey(),
                'khoa_tuong_quan' => (string) Str::uuid(),
                'du_lieu_truoc' => null,
                'du_lieu_sau' => [
                    'nguoi_dung_id' => (int) $taiKhoan->getKey(),
                    'phan_quyen_nguoi_dung_id' => (int) $phanQuyen->getKey(),
                    'ma_vai_tro' => 'ADMIN',
                ],
                'ket_qua' => 'THANH_CONG',
                'thuc_hien_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);

            return [
                'transition' => 'CREATED',
                'account_id' => (int) $taiKhoan->getKey(),
                'email' => $email,
                'role' => 'ADMIN',
            ];
        }, 3);
    }

    private function adminHoatDong(int $vaiTroAdminId): ?NguoiDung
    {
        return NguoiDung::query()
            ->where('trang_thai', 'HOAT_DONG')
            ->whereHas('phanQuyenNguoiDungsTheoNguoiDung', function ($truyVan) use ($vaiTroAdminId): void {
                $truyVan->where('vai_tro_id', $vaiTroAdminId)->whereNull('thu_hoi_luc');
            })
            ->orderBy('id')
            ->first();
    }

    private function chiNhanhHoatDong(string $chiNhanh): ChiNhanh
    {
        $chiNhanh = trim($chiNhanh);
        $truyVan = ChiNhanh::query()->where('trang_thai', 'HOAT_DONG')->lockForUpdate();
        if (ctype_digit($chiNhanh)) {
            $truyVan->whereKey((int) $chiNhanh);
        } else {
            $truyVan->where('ma_chi_nhanh', strtoupper($chiNhanh));
        }

        $ketQua = $truyVan->first();
        if (! $ketQua instanceof ChiNhanh) {
            throw new AuthWorkflowException('Chi nhánh không hoạt động hoặc không tồn tại.', 422, 'ACTIVE_BRANCH_REQUIRED');
        }

        return $ketQua;
    }

    private function soDienThoai(?string $soDienThoai): ?string
    {
        $soDienThoai = $soDienThoai === null ? null : trim($soDienThoai);

        return $soDienThoai === '' ? null : $soDienThoai;
    }
}
