<?php

namespace App\Services;

use App\Exceptions\MembershipLifecycleException;
use App\Models\DangKyGoiTap;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use App\Models\SuDungQuyenLoi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MembershipActivationService
{
    /**
     * Kích hoạt chuỗi bằng một usage trả phí đã được nghiệp vụ nguồn chấp nhận.
     *
     * Hàm không tạo usage, không trừ quota và không công khai thành API. Lock order
     * là Member → chuỗi → kỳ → usage. Lần đầu dùng mốc chap_nhan_luc của usage để
     * materialize toàn bộ đồng hồ nối tiếp; retry chỉ trả trạng thái hiện tại và
     * tuyệt đối không đổi nguồn hay reset ngày bắt đầu.
     *
     * @return array{result: string, registration_id: int, first_usage_id: int, started_at: string, term_id: int, term_ends_at: string}
     */
    public function kichHoatNeuCan(int $suDungQuyenLoiId): array
    {
        $suDungBanDau = SuDungQuyenLoi::query()->find($suDungQuyenLoiId);
        if ($suDungBanDau === null) {
            throw new MembershipLifecycleException('Không tìm thấy usage được chấp nhận.');
        }

        return DB::transaction(function () use ($suDungQuyenLoiId, $suDungBanDau): array {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($suDungBanDau->hoi_vien_id);

            $chuoi = DangKyGoiTap::query()
                ->where('hoi_vien_id', $suDungBanDau->hoi_vien_id)
                ->whereIn('trang_thai', ['CHO_KICH_HOAT', 'DANG_HOAT_DONG'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if ($chuoi === null) {
                throw new MembershipLifecycleException('Hội viên không có chuỗi Membership có thể sử dụng.');
            }

            $cacKy = $chuoi->kyHanHoiViens()
                ->orderBy('so_thu_tu')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $suDung = SuDungQuyenLoi::query()->lockForUpdate()->find($suDungQuyenLoiId);
            if ($suDung === null || (int) $suDung->hoi_vien_id !== (int) $chuoi->hoi_vien_id) {
                throw new MembershipLifecycleException('Usage không thuộc hội viên của chuỗi Membership.');
            }

            if ($chuoi->trang_thai === 'DANG_HOAT_DONG') {
                return $this->ketQuaDaKichHoat($chuoi, $cacKy, $suDung);
            }

            $kyDau = $cacKy->first();
            if (! $kyDau instanceof KyHanHoiVien
                || $kyDau->trang_thai !== 'CHO_KICH_HOAT'
                || $kyDau->ngay_bat_dau !== null
                || $kyDau->ngay_ket_thuc !== null
                || (int) $suDung->ky_han_hoi_vien_id !== (int) $kyDau->getKey()) {
                throw new MembershipLifecycleException('Chỉ usage thuộc kỳ đầu đang chờ kích hoạt mới được mở chuỗi.');
            }
            $this->damBaoCoQuyen($kyDau, $suDung->loai_su_dung);

            $mocBatDau = CarbonImmutable::instance($suDung->chap_nhan_luc);
            $mocKeTiep = $mocBatDau;
            foreach ($cacKy as $viTri => $ky) {
                if ($ky->trang_thai === 'HUY') {
                    continue;
                }
                if (! in_array($ky->trang_thai, ['CHO_KICH_HOAT', 'CHO_DEN_LUOT'], true)
                    || $ky->ngay_bat_dau !== null
                    || $ky->ngay_ket_thuc !== null) {
                    throw new MembershipLifecycleException('Chuỗi chờ kích hoạt chứa trạng thái kỳ không hợp lệ.');
                }

                $mocKetThuc = $mocKeTiep->addDays((int) $ky->thoi_han_ngay);
                $ky->forceFill([
                    'trang_thai' => $viTri === 0 ? 'DANG_HOAT_DONG' : 'CHO_DEN_LUOT',
                    'ngay_bat_dau' => $mocKeTiep,
                    'ngay_ket_thuc' => $mocKetThuc,
                ])->save();
                $mocKeTiep = $mocKetThuc;
            }

            $chuoi->forceFill([
                'trang_thai' => 'DANG_HOAT_DONG',
                'lan_su_dung_dau_tien_id' => $suDung->getKey(),
                'ngay_bat_dau' => $mocBatDau,
            ])->save();

            return [
                'result' => 'MOI_KICH_HOAT',
                'registration_id' => (int) $chuoi->getKey(),
                'first_usage_id' => (int) $suDung->getKey(),
                'started_at' => $mocBatDau->toISOString(),
                'term_id' => (int) $kyDau->getKey(),
                'term_ends_at' => $mocBatDau->addDays((int) $kyDau->thoi_han_ngay)->toISOString(),
            ];
        });
    }

    /** @param Collection<int, KyHanHoiVien> $cacKy */
    private function ketQuaDaKichHoat(
        DangKyGoiTap $chuoi,
        Collection $cacKy,
        SuDungQuyenLoi $suDung,
    ): array {
        if ($chuoi->lan_su_dung_dau_tien_id === null || $chuoi->ngay_bat_dau === null) {
            throw new MembershipLifecycleException('Chuỗi hoạt động thiếu nguồn hoặc mốc kích hoạt.');
        }

        $kySuDung = $cacKy->firstWhere('id', (int) $suDung->ky_han_hoi_vien_id);
        if (! $kySuDung instanceof KyHanHoiVien
            || $kySuDung->ngay_bat_dau === null
            || $kySuDung->ngay_ket_thuc === null
            || $suDung->chap_nhan_luc->lessThan($kySuDung->ngay_bat_dau)
            || ! $suDung->chap_nhan_luc->lessThan($kySuDung->ngay_ket_thuc)) {
            throw new MembershipLifecycleException('Usage không nằm trong kỳ áp dụng theo [start,end).');
        }
        $this->damBaoCoQuyen($kySuDung, $suDung->loai_su_dung);

        $kyDau = $cacKy->firstWhere('id', (int) $suDung->ky_han_hoi_vien_id);

        return [
            'result' => 'DA_KICH_HOAT',
            'registration_id' => (int) $chuoi->getKey(),
            'first_usage_id' => (int) $chuoi->lan_su_dung_dau_tien_id,
            'started_at' => CarbonImmutable::instance($chuoi->ngay_bat_dau)->toISOString(),
            'term_id' => (int) $kyDau->getKey(),
            'term_ends_at' => CarbonImmutable::instance($kyDau->ngay_ket_thuc)->toISOString(),
        ];
    }

    private function damBaoCoQuyen(KyHanHoiVien $ky, string $loaiSuDung): void
    {
        $duocPhep = match ($loaiSuDung) {
            'VAO_PHONG_TAP' => $ky->cho_phep_vao_phong_tap,
            'YEU_CAU_TRO_LY' => $ky->cho_phep_tro_ly_tap_luyen
                && ($ky->gioi_han_luot_tro_ly === null
                    || $ky->so_luot_tro_ly_da_dung + $ky->so_luot_tro_ly_giu_cho < $ky->gioi_han_luot_tro_ly),
            'BUOI_HUAN_LUYEN' => $ky->so_buoi_huan_luyen_vien_da_dung < $ky->so_buoi_huan_luyen_vien,
            'TRO_CHUYEN_HUAN_LUYEN' => $ky->cho_phep_tro_chuyen_huan_luyen_vien,
            default => false,
        };

        if (! $duocPhep) {
            throw new MembershipLifecycleException('Snapshot kỳ không cấp quyền cho loại usage này.');
        }
    }
}
