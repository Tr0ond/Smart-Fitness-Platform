<?php

namespace App\Services;

use App\Exceptions\MembershipLifecycleException;
use App\Models\DangKyGoiTap;
use App\Models\HoSoHoiVien;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MembershipLifecycleService
{
    /**
     * Đối chiếu projection trạng thái của các chuỗi đang mở theo khoảng [start,end).
     *
     * Input là ID hồ sơ Member và clock Backend tùy chọn cho test nội bộ. Hàm khóa
     * Member trước, sau đó chuỗi và các kỳ theo ID/thứ tự; không xóa history, không
     * kích hoạt chuỗi và không tạo usage. Khi đến biên cuối, kỳ cũ HET_HAN và kỳ
     * nối tiếp trở thành DANG_HOAT_DONG trong cùng transaction.
     */
    public function doiChieuHoiVien(int $hoiVienId, ?CarbonImmutable $thoiDiem = null): void
    {
        DB::transaction(function () use ($hoiVienId, $thoiDiem): void {
            $thoiDiem ??= CarbonImmutable::now('UTC');
            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->find($hoiVienId);
            if ($hoiVien === null) {
                throw new MembershipLifecycleException('Không tìm thấy hồ sơ hội viên.');
            }

            $cacChuoi = DangKyGoiTap::query()
                ->where('hoi_vien_id', $hoiVienId)
                ->whereIn('trang_thai', ['CHO_KICH_HOAT', 'DANG_HOAT_DONG'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($cacChuoi as $chuoi) {
                $this->doiChieuChuoiDaKhoa($chuoi, $thoiDiem);
            }
        });
    }

    /** Chuỗi phải được gọi sau khi hàng Member và chuỗi đã khóa theo lock order chung. */
    private function doiChieuChuoiDaKhoa(DangKyGoiTap $chuoi, CarbonImmutable $thoiDiem): void
    {
        $cacKy = $chuoi->kyHanHoiViens()
            ->orderBy('so_thu_tu')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($chuoi->trang_thai === 'CHO_KICH_HOAT') {
            return;
        }

        $coKyHienTai = false;
        $coKyTuongLai = false;
        $coKyCoThoiGian = false;
        $mocKetThucCuoi = null;

        foreach ($cacKy as $ky) {
            if ($ky->trang_thai === 'HUY' || $ky->ngay_bat_dau === null || $ky->ngay_ket_thuc === null) {
                continue;
            }

            $coKyCoThoiGian = true;
            $mocKetThucCuoi = $mocKetThucCuoi === null || $ky->ngay_ket_thuc->greaterThan($mocKetThucCuoi)
                ? $ky->ngay_ket_thuc
                : $mocKetThucCuoi;

            if ($thoiDiem->greaterThanOrEqualTo($ky->ngay_ket_thuc)) {
                if ($ky->trang_thai !== 'HET_HAN') {
                    $ky->trang_thai = 'HET_HAN';
                    $ky->save();
                }

                continue;
            }

            if ($thoiDiem->greaterThanOrEqualTo($ky->ngay_bat_dau)) {
                $coKyHienTai = true;
                if ($ky->trang_thai !== 'DANG_HOAT_DONG') {
                    $ky->trang_thai = 'DANG_HOAT_DONG';
                    $ky->save();
                }

                continue;
            }

            $coKyTuongLai = true;
            if ($ky->trang_thai !== 'CHO_DEN_LUOT') {
                $ky->trang_thai = 'CHO_DEN_LUOT';
                $ky->save();
            }
        }

        if ($coKyHienTai || $coKyTuongLai) {
            if ($chuoi->trang_thai !== 'DANG_HOAT_DONG') {
                $chuoi->trang_thai = 'DANG_HOAT_DONG';
                $chuoi->save();
            }

            return;
        }

        if ($coKyCoThoiGian && $mocKetThucCuoi !== null && $thoiDiem->greaterThanOrEqualTo($mocKetThucCuoi)) {
            $chuoi->trang_thai = 'HET_HAN';
            $chuoi->ket_thuc_ghi_nhan_luc ??= $thoiDiem;
            $chuoi->save();
        }
    }
}
