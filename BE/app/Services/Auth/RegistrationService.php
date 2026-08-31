<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\ChiNhanh;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanQuyenNguoiDung;
use App\Models\VaiTro;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegistrationService
{
    private const MA_CHI_NHANH_MVP = 'CHI_NHANH_MVP';

    /**
     * Tạo Account, Role MEMBER và hồ sơ Member trong cùng transaction.
     * Chi nhánh, Role, trạng thái và mã Member đều do server quyết định.
     */
    public function dangKy(array $duLieu): NguoiDung
    {
        $matKhauBam = Hash::make((string) $duLieu['password']);

        try {
            return DB::transaction(function () use ($duLieu, $matKhauBam): NguoiDung {
                $chiNhanh = ChiNhanh::query()
                    ->where('ma_chi_nhanh', self::MA_CHI_NHANH_MVP)
                    ->where('trang_thai', 'HOAT_DONG')
                    ->lockForUpdate()
                    ->first();
                $vaiTro = VaiTro::query()->where('ma_vai_tro', 'MEMBER')->lockForUpdate()->first();

                if ($chiNhanh === null || $vaiTro === null) {
                    throw new AuthWorkflowException(
                        'Hệ thống chưa sẵn sàng tiếp nhận đăng ký.',
                        503,
                        'REGISTRATION_CONFIGURATION_REQUIRED',
                    );
                }

                $hienTai = CarbonImmutable::now('UTC');
                $nguoiDung = NguoiDung::query()->create([
                    'chi_nhanh_id' => $chiNhanh->getKey(),
                    'ho_ten' => trim((string) $duLieu['name']),
                    'thu_dien_tu' => (string) $duLieu['email'],
                    'so_dien_thoai' => isset($duLieu['phone']) ? trim((string) $duLieu['phone']) : null,
                    'mat_khau_bam' => $matKhauBam,
                    'anh_dai_dien' => null,
                    'xac_minh_thu_luc' => null,
                    'trang_thai' => 'HOAT_DONG',
                    'dang_nhap_gan_nhat_luc' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                $phanQuyen = PhanQuyenNguoiDung::query()->create([
                    'nguoi_dung_id' => $nguoiDung->getKey(),
                    'vai_tro_id' => $vaiTro->getKey(),
                    'nguoi_cap_id' => null,
                    'cap_luc' => $hienTai,
                    'thu_hoi_luc' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                HoSoHoiVien::query()->create([
                    'nguoi_dung_id' => $nguoiDung->getKey(),
                    'ma_hoi_vien' => $this->taoMaHoiVien(),
                    'ngay_sinh' => null,
                    'gioi_tinh' => null,
                    'muc_tieu_tap_luyen' => null,
                    'kinh_nghiem_tap_luyen' => null,
                    'so_ngay_tap_mong_muon' => null,
                    'thoi_luong_moi_buoi_phut' => null,
                    'phien_ban_ho_so' => 1,
                    'moc_thay_doi_ke_hoach' => 0,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                NhatKyHeThong::query()->create([
                    'nguoi_thuc_hien_id' => null,
                    'loai_tac_nhan' => 'HE_THONG',
                    'hanh_dong' => 'DANG_KY_VAI_TRO_MEMBER',
                    'loai_doi_tuong' => 'PHAN_QUYEN_NGUOI_DUNG',
                    'dinh_danh_doi_tuong' => $phanQuyen->getKey(),
                    'khoa_tuong_quan' => (string) Str::uuid(),
                    'du_lieu_truoc' => null,
                    'du_lieu_sau' => $this->duLieuPhanQuyen($phanQuyen, 'MEMBER'),
                    'ket_qua' => 'THANH_CONG',
                    'thuc_hien_luc' => $hienTai,
                    'ngay_tao' => $hienTai,
                ]);

                return $nguoiDung->refresh();
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new AuthWorkflowException(
                    'Email đã được sử dụng.',
                    409,
                    'ACCOUNT_ALREADY_EXISTS',
                );
            }

            throw $exception;
        }
    }

    private function taoMaHoiVien(): string
    {
        do {
            $maHoiVien = 'HV_'.strtoupper(bin2hex(random_bytes(10)));
        } while (HoSoHoiVien::query()->where('ma_hoi_vien', $maHoiVien)->exists());

        return $maHoiVien;
    }

    /** @return array<string, int|string|null> */
    private function duLieuPhanQuyen(PhanQuyenNguoiDung $phanQuyen, string $maVaiTro): array
    {
        return [
            'id' => (int) $phanQuyen->getKey(),
            'nguoi_dung_id' => (int) $phanQuyen->nguoi_dung_id,
            'vai_tro_id' => (int) $phanQuyen->vai_tro_id,
            'ma_vai_tro' => $maVaiTro,
            'nguoi_cap_id' => null,
            'cap_luc' => $phanQuyen->cap_luc?->format('Y-m-d H:i:s.u'),
            'thu_hoi_luc' => null,
        ];
    }
}
