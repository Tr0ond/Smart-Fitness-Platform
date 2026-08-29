<?php

namespace Tests\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

trait CreatesProfileFixtures
{
    /** @param array<string, mixed> $ghiDe */
    protected function taoHoSoHoiVien(array $fixture, array $ghiDe = []): int
    {
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $hienTai = CarbonImmutable::now('UTC');

        return DB::table('ho_so_hoi_vien')->insertGetId(array_merge([
            'nguoi_dung_id' => $fixture['user']->getKey(),
            'ma_hoi_vien' => 'HV_PROFILE_'.$hauTo,
            'ngay_sinh' => '2000-01-02',
            'gioi_tinh' => 'NAM',
            'muc_tieu_tap_luyen' => 'GIAM_MO',
            'kinh_nghiem_tap_luyen' => 'MOI_BAT_DAU',
            'so_ngay_tap_mong_muon' => 3,
            'thoi_luong_moi_buoi_phut' => 60,
            'phien_ban_ho_so' => 1,
            'moc_thay_doi_ke_hoach' => 0,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDe));
    }

    /** @param array<string, mixed> $ghiDe */
    protected function taoHoSoHuanLuyenVien(array $fixture, array $ghiDe = []): int
    {
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $hienTai = CarbonImmutable::now('UTC');

        return DB::table('ho_so_huan_luyen_vien')->insertGetId(array_merge([
            'nguoi_dung_id' => $fixture['user']->getKey(),
            'ma_huan_luyen_vien' => 'PT_PROFILE_'.$hauTo,
            'gioi_thieu' => 'Huấn luyện viên kiểm thử',
            'chuyen_mon' => 'Sức mạnh',
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDe));
    }

    /** @param array<string, mixed> $ghiDe */
    protected function taoDungCuProfile(array $ghiDe = []): int
    {
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $hienTai = CarbonImmutable::now('UTC');

        return DB::table('dung_cu')->insertGetId(array_merge([
            'ma_dung_cu' => 'DC_PROFILE_'.$hauTo,
            'ten_dung_cu' => 'Dụng cụ profile '.$hauTo,
            'mo_ta' => null,
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDe));
    }

    protected function layTokenProfile(array $fixture): string
    {
        return (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
    }

    protected function thuHoiVaiTroProfile(array $fixture, string $maVaiTro): void
    {
        DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $fixture['user']->getKey())
            ->where('vai_tro_id', $fixture['role_ids'][$maVaiTro])
            ->update(['thu_hoi_luc' => CarbonImmutable::now('UTC')]);
    }
}
