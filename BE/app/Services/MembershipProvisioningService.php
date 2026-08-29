<?php

namespace App\Services;

use App\Exceptions\MembershipLifecycleException;
use App\Models\DangKyGoiTap;
use App\Models\DonMuaGoi;
use App\Models\GoiTap;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use App\Models\LanThanhToan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MembershipProvisioningService
{
    public function __construct(private readonly MembershipLifecycleService $vongDoi) {}

    /**
     * Xếp một snapshot kỳ vào chuỗi sau khi Payment workflow đã xác nhận hợp lệ.
     *
     * Input là ID đơn và lần thanh toán đã ở trạng thái chính thức. Hàm không gọi
     * payOS và không tự đổi trạng thái đơn/payment. Trong transaction, nó khóa
     * Member → đơn/payment/snapshot → chuỗi/kỳ; retry cùng nguồn trả kết quả cũ,
     * còn nguồn khác cho cùng đơn bị từ chối. Chuỗi chưa chạy nhận head/tail không
     * ngày; chuỗi đã chạy nối kỳ sau mốc cuối theo [start,end).
     *
     * @return array{result: string, registration_id: int, term_id: int, sequence: int}
     */
    public function capTuThanhToanDaXacNhan(int $donMuaGoiId, int $lanThanhToanId): array
    {
        return DB::transaction(function () use ($donMuaGoiId, $lanThanhToanId): array {
            $donBanDau = DonMuaGoi::query()->find($donMuaGoiId);
            if ($donBanDau === null) {
                throw new MembershipLifecycleException('Không tìm thấy đơn mua gói.');
            }

            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->findOrFail($donBanDau->hoi_vien_id);
            $don = DonMuaGoi::query()->lockForUpdate()->findOrFail($donMuaGoiId);
            $lanThanhToan = LanThanhToan::query()->lockForUpdate()->find($lanThanhToanId);
            $ky = KyHanHoiVien::query()->where('don_mua_goi_id', $don->getKey())->lockForUpdate()->first();

            if ($lanThanhToan === null || $ky === null) {
                throw new MembershipLifecycleException('Thiếu payment hoặc snapshot kỳ của đơn.');
            }
            if ((int) $lanThanhToan->don_mua_goi_id !== (int) $don->getKey()
                || $don->trang_thai !== 'DA_THANH_TOAN'
                || $lanThanhToan->trang_thai !== 'THANH_CONG'
                || $lanThanhToan->xac_nhan_luc === null
                || $lanThanhToan->so_tien_da_nhan === null
                || (string) $lanThanhToan->so_tien_da_nhan !== (string) $don->so_tien_phai_thu
                || $lanThanhToan->don_vi_tien !== $don->don_vi_tien) {
                throw new MembershipLifecycleException('Payment chưa đạt invariant cấp kỳ.');
            }

            if ($ky->dang_ky_goi_tap_id !== null) {
                if ((int) $ky->lan_thanh_toan_id !== (int) $lanThanhToan->getKey()) {
                    throw new MembershipLifecycleException('Đơn đã được cấp bởi nguồn thanh toán khác.');
                }

                return $this->ketQua('DA_CAP_TRUOC', $ky);
            }
            if ($ky->trang_thai !== 'CHO_THANH_TOAN' || $ky->lan_thanh_toan_id !== null) {
                throw new MembershipLifecycleException('Snapshot kỳ không còn ở trạng thái chờ thanh toán.');
            }

            $this->vongDoi->doiChieuHoiVien($hoiVien->getKey());
            $chuoi = DangKyGoiTap::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->whereIn('trang_thai', ['CHO_KICH_HOAT', 'DANG_HOAT_DONG'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            $muaLuc = CarbonImmutable::instance($lanThanhToan->xac_nhan_luc);
            if ($chuoi === null) {
                $goiTap = GoiTap::query()->findOrFail($don->goi_tap_id);
                $chuoi = DangKyGoiTap::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'chi_nhanh_id' => $goiTap->chi_nhanh_id,
                    'trang_thai' => 'CHO_KICH_HOAT',
                    'lan_su_dung_dau_tien_id' => null,
                    'ngay_bat_dau' => null,
                    'ket_thuc_ghi_nhan_luc' => null,
                ]);
                $soThuTu = 1;
                $trangThai = 'CHO_KICH_HOAT';
                $ngayBatDau = null;
                $ngayKetThuc = null;
            } else {
                $cacKy = $chuoi->kyHanHoiViens()
                    ->orderBy('so_thu_tu')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $kyCuoi = $cacKy->last();
                if ($kyCuoi === null || $kyCuoi->mua_luc === null || $muaLuc->lessThan($kyCuoi->mua_luc)) {
                    throw new MembershipLifecycleException('Không được chèn ngược kỳ vào chuỗi đã xếp.');
                }

                $soThuTu = ((int) $kyCuoi->so_thu_tu) + 1;
                $trangThai = 'CHO_DEN_LUOT';
                if ($chuoi->trang_thai === 'DANG_HOAT_DONG') {
                    if ($kyCuoi->ngay_ket_thuc === null) {
                        throw new MembershipLifecycleException('Chuỗi đã chạy nhưng kỳ cuối thiếu mốc kết thúc.');
                    }
                    $ngayBatDau = CarbonImmutable::instance($kyCuoi->ngay_ket_thuc);
                    $ngayKetThuc = $ngayBatDau->addDays((int) $ky->thoi_han_ngay);
                } else {
                    $ngayBatDau = null;
                    $ngayKetThuc = null;
                }
            }

            $ky->forceFill([
                'lan_thanh_toan_id' => $lanThanhToan->getKey(),
                'dang_ky_goi_tap_id' => $chuoi->getKey(),
                'so_thu_tu' => $soThuTu,
                'trang_thai' => $trangThai,
                'mua_luc' => $muaLuc,
                'ngay_bat_dau' => $ngayBatDau,
                'ngay_ket_thuc' => $ngayKetThuc,
            ])->save();

            return $this->ketQua('DA_CAP_MOI', $ky->refresh());
        });
    }

    /** @return array{result: string, registration_id: int, term_id: int, sequence: int} */
    private function ketQua(string $ketQua, KyHanHoiVien $ky): array
    {
        return [
            'result' => $ketQua,
            'registration_id' => (int) $ky->dang_ky_goi_tap_id,
            'term_id' => (int) $ky->getKey(),
            'sequence' => (int) $ky->so_thu_tu,
        ];
    }
}
