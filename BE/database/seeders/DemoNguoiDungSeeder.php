<?php

namespace Database\Seeders;

use App\Models\ChiNhanh;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanQuyenNguoiDung;
use App\Models\VaiTro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoNguoiDungSeeder extends Seeder
{
    use WithoutModelEvents;

    private const MAT_KHAU_PHAT_TRIEN = 'DevOnly!ChangeMe123';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException(
                'DemoNguoiDungSeeder chỉ được phép chạy trong môi trường local hoặc testing.',
            );
        }

        $chiNhanh = ChiNhanh::query()->where('ma_chi_nhanh', 'CHI_NHANH_MVP')->firstOrFail();
        $vaiTro = VaiTro::query()->whereIn('ma_vai_tro', ['ADMIN', 'PT', 'RECEPTIONIST', 'MEMBER'])
            ->pluck('id', 'ma_vai_tro');

        foreach (['ADMIN', 'PT', 'RECEPTIONIST', 'MEMBER'] as $maVaiTro) {
            if (! isset($vaiTro[$maVaiTro])) {
                throw new \RuntimeException("Missing official role: {$maVaiTro}");
            }
        }

        $danhSach = [
            ['ma' => 'admin', 'ho_ten' => 'Demo Quan tri vien', 'thu_dien_tu' => 'dev.admin@smartfitness.local', 'vai_tro' => 'ADMIN'],
            ['ma' => 'pt01', 'ho_ten' => 'Demo Huan luyen vien 01', 'thu_dien_tu' => 'dev.pt01@smartfitness.local', 'vai_tro' => 'PT'],
            ['ma' => 'pt02', 'ho_ten' => 'Demo Huan luyen vien 02', 'thu_dien_tu' => 'dev.pt02@smartfitness.local', 'vai_tro' => 'PT'],
            ['ma' => 'receptionist', 'ho_ten' => 'Demo Le tan', 'thu_dien_tu' => 'dev.receptionist@smartfitness.local', 'vai_tro' => 'RECEPTIONIST'],
            ['ma' => 'member01', 'ho_ten' => 'Demo Hoi vien 01', 'thu_dien_tu' => 'dev.member01@smartfitness.local', 'vai_tro' => 'MEMBER'],
            ['ma' => 'member02', 'ho_ten' => 'Demo Hoi vien 02', 'thu_dien_tu' => 'dev.member02@smartfitness.local', 'vai_tro' => 'MEMBER'],
            ['ma' => 'member03', 'ho_ten' => 'Demo Hoi vien 03', 'thu_dien_tu' => 'dev.member03@smartfitness.local', 'vai_tro' => 'MEMBER'],
            ['ma' => 'member04', 'ho_ten' => 'Demo Hoi vien 04', 'thu_dien_tu' => 'dev.member04@smartfitness.local', 'vai_tro' => 'MEMBER'],
        ];

        // Fail trước mọi mutation nếu fixture gặp grant đã thu hồi. Regrant phải
        // đi qua workflow có transaction + audit, không được thực hiện trong seeder.
        $this->damBaoKhongTaiCapVaiTroDaThuHoi($danhSach, $vaiTro);

        $nguoiDung = [];
        foreach ($danhSach as $duLieu) {
            $taiKhoan = NguoiDung::query()->firstOrNew(['thu_dien_tu' => $duLieu['thu_dien_tu']]);
            $taiKhoan->chi_nhanh_id = $chiNhanh->id;
            $taiKhoan->ho_ten = $duLieu['ho_ten'];
            $taiKhoan->trang_thai = 'HOAT_DONG';
            if (! $taiKhoan->exists) {
                $taiKhoan->mat_khau_bam = Hash::make(self::MAT_KHAU_PHAT_TRIEN);
            }
            $taiKhoan->save();
            $nguoiDung[$duLieu['ma']] = $taiKhoan;
        }

        $adminId = $nguoiDung['admin']->id;
        foreach ($danhSach as $duLieu) {
            $taiKhoan = $nguoiDung[$duLieu['ma']];
            $phanQuyen = PhanQuyenNguoiDung::query()->firstOrNew([
                'nguoi_dung_id' => $taiKhoan->id,
                'vai_tro_id' => $vaiTro[$duLieu['vai_tro']],
            ]);
            if ($phanQuyen->exists) {
                // Active grant đã có là idempotent; không sửa metadata cấp quyền.
                continue;
            }

            $phanQuyen->nguoi_cap_id = $duLieu['vai_tro'] === 'ADMIN' ? null : $adminId;
            $phanQuyen->cap_luc = '2026-08-29 00:00:00.000000';
            $phanQuyen->thu_hoi_luc = null;
            $phanQuyen->save();
        }

        $memberIndex = 1;
        foreach (array_filter($danhSach, static fn (array $duLieu): bool => $duLieu['vai_tro'] === 'MEMBER') as $duLieu) {
            $taiKhoan = $nguoiDung[$duLieu['ma']];
            $hoSo = HoSoHoiVien::query()->firstOrNew(['nguoi_dung_id' => $taiKhoan->id]);
            $hoSo->ma_hoi_vien = 'HV_DEMO_'.str_pad((string) $memberIndex, 2, '0', STR_PAD_LEFT);
            $hoSo->ngay_sinh = sprintf('199%d-01-15', $memberIndex + 0);
            $hoSo->gioi_tinh = $memberIndex % 2 === 0 ? 'NU' : 'NAM';
            $hoSo->muc_tieu_tap_luyen = $memberIndex % 2 === 0 ? 'TANG_SUC_BEN' : 'GIAM_MO';
            $hoSo->kinh_nghiem_tap_luyen = $memberIndex === 1 ? 'MOI_BAT_DAU' : 'CO_BAN';
            $hoSo->so_ngay_tap_mong_muon = min(7, $memberIndex + 2);
            $hoSo->thoi_luong_moi_buoi_phut = 45;
            $hoSo->phien_ban_ho_so = 1;
            $hoSo->moc_thay_doi_ke_hoach = 0;
            $hoSo->save();
            $memberIndex++;
        }

        $ptIndex = 1;
        foreach (array_filter($danhSach, static fn (array $duLieu): bool => $duLieu['vai_tro'] === 'PT') as $duLieu) {
            $taiKhoan = $nguoiDung[$duLieu['ma']];
            $hoSo = HoSoHuanLuyenVien::query()->firstOrNew(['nguoi_dung_id' => $taiKhoan->id]);
            $hoSo->ma_huan_luyen_vien = 'PT_DEMO_'.str_pad((string) $ptIndex, 2, '0', STR_PAD_LEFT);
            $hoSo->gioi_thieu = 'Tai khoan PT demo phuc vu phat trien va kiem thu.';
            $hoSo->chuyen_mon = $ptIndex === 1 ? 'SUC_MANH, THE_HINH' : 'MOBILITY, SUC_BEN';
            $hoSo->trang_thai = 'HOAT_DONG';
            $hoSo->save();
            $ptIndex++;
        }
    }

    /**
     * @param  array<int, array{ma: string, ho_ten: string, thu_dien_tu: string, vai_tro: string}>  $danhSach
     * @param  Collection<string, int>  $vaiTro
     */
    private function damBaoKhongTaiCapVaiTroDaThuHoi(array $danhSach, Collection $vaiTro): void
    {
        $nguoiDungIds = NguoiDung::query()
            ->whereIn('thu_dien_tu', array_column($danhSach, 'thu_dien_tu'))
            ->pluck('id', 'thu_dien_tu');

        foreach ($danhSach as $duLieu) {
            $nguoiDungId = $nguoiDungIds[$duLieu['thu_dien_tu']] ?? null;
            if ($nguoiDungId === null) {
                continue;
            }

            $daThuHoi = PhanQuyenNguoiDung::query()
                ->where('nguoi_dung_id', $nguoiDungId)
                ->where('vai_tro_id', $vaiTro[$duLieu['vai_tro']])
                ->whereNotNull('thu_hoi_luc')
                ->exists();
            if ($daThuHoi) {
                throw new \RuntimeException(sprintf(
                    'Vai trò demo %s của %s đã bị thu hồi; phải tái cấp qua workflow có audit.',
                    $duLieu['vai_tro'],
                    $duLieu['thu_dien_tu'],
                ));
            }
        }
    }
}
