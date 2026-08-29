<?php

namespace App\Services\Payments;

use App\Models\DonMuaGoi;
use App\Models\HoSoHoiVien;
use App\Models\LanThanhToan;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class OrderQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function layDanhSachCuaToi(NguoiDung $nguoiDung): array
    {
        $hoiVien = $this->hoiVienCua($nguoiDung);

        return $this->truyVanDonCua($hoiVien)
            ->orderByDesc('id')
            ->get()
            ->map(fn (DonMuaGoi $don): array => $this->duLieuDon($don))
            ->all();
    }

    /** @return array<string, mixed> */
    public function layChiTietCuaToi(NguoiDung $nguoiDung, int $donId): array
    {
        $hoiVien = $this->hoiVienCua($nguoiDung);
        $don = $this->truyVanDonCua($hoiVien)->find($donId);
        abort_if($don === null, 404, 'Không tìm thấy đơn mua gói.');

        return $this->duLieuDon($don);
    }

    /** @return array<string, mixed> */
    public function layThanhToanCuaToi(NguoiDung $nguoiDung, int $donId): array
    {
        $hoiVien = $this->hoiVienCua($nguoiDung);
        $don = $this->truyVanDonCua($hoiVien)->find($donId);
        abort_if($don === null, 404, 'Không tìm thấy đơn mua gói.');

        return [
            'order_id' => (int) $don->getKey(),
            'order_status' => $don->trang_thai,
            'payments' => $don->lanThanhToans
                ->sortBy(['so_lan', 'id'])
                ->values()
                ->map(fn (LanThanhToan $lan): array => $this->duLieuThanhToan($lan))
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function duLieuDonTheoId(int $donId): array
    {
        $don = DonMuaGoi::query()
            ->with(['kyHanHoiVien', 'lanThanhToans'])
            ->findOrFail($donId);

        return $this->duLieuDon($don);
    }

    private function hoiVienCua(NguoiDung $nguoiDung): HoSoHoiVien
    {
        $hoiVien = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
        abort_if($hoiVien === null, 403, 'Tài khoản chưa có hồ sơ hội viên.');

        return $hoiVien;
    }

    /** @return Builder<DonMuaGoi> */
    private function truyVanDonCua(HoSoHoiVien $hoiVien): Builder
    {
        return DonMuaGoi::query()
            ->with(['kyHanHoiVien', 'lanThanhToans'])
            ->where('hoi_vien_id', $hoiVien->getKey());
    }

    /** @return array<string, mixed> */
    private function duLieuDon(DonMuaGoi $don): array
    {
        $snapshot = $don->kyHanHoiVien;

        return [
            'id' => (int) $don->getKey(),
            'code' => $don->ma_don,
            'package_id' => (int) $don->goi_tap_id,
            'amount' => $don->so_tien_phai_thu,
            'currency' => $don->don_vi_tien,
            'status' => $don->trang_thai,
            'price_locked_at' => $don->chot_gia_luc?->toISOString(),
            'payment_expires_at' => $don->het_han_thanh_toan_luc?->toISOString(),
            'confirmed_at' => $don->thanh_toan_luc?->toISOString(),
            'snapshot' => $snapshot === null ? null : [
                'id' => (int) $snapshot->getKey(),
                'status' => $snapshot->trang_thai,
                'package_name' => $snapshot->ten_goi,
                'package_version' => (int) $snapshot->phien_ban_goi,
                'price' => $snapshot->gia_da_mua,
                'duration_days' => (int) $snapshot->thoi_han_ngay,
                'benefits' => [
                    'gym_access' => (bool) $snapshot->cho_phep_vao_phong_tap,
                    'fitness_assistant' => (bool) $snapshot->cho_phep_tro_ly_tap_luyen,
                    'fitness_assistant_limit' => $snapshot->gioi_han_luot_tro_ly,
                    'trainer_chat' => (bool) $snapshot->cho_phep_tro_chuyen_huan_luyen_vien,
                    'direct_trainer_sessions' => (int) $snapshot->so_buoi_huan_luyen_vien,
                ],
                'starts_at' => $snapshot->ngay_bat_dau?->toISOString(),
                'ends_at' => $snapshot->ngay_ket_thuc?->toISOString(),
            ],
            'payments' => $don->lanThanhToans
                ->sortBy(['so_lan', 'id'])
                ->values()
                ->map(fn (LanThanhToan $lan): array => $this->duLieuThanhToan($lan))
                ->all(),
            'created_at' => $don->ngay_tao?->toISOString(),
            'updated_at' => $don->ngay_cap_nhat?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuThanhToan(LanThanhToan $lan): array
    {
        $conHieuLuc = $lan->trang_thai === 'CHO_THANH_TOAN'
            && $lan->het_han_luc !== null
            && CarbonImmutable::now('UTC')->lessThan($lan->het_han_luc);

        return [
            'id' => (int) $lan->getKey(),
            'attempt' => (int) $lan->so_lan,
            'channel' => $lan->ma_kenh_thanh_toan,
            'order_code' => (int) $lan->ma_don_cong_thanh_toan,
            'payment_link_id' => $lan->ma_lien_ket_thanh_toan,
            'checkout_url' => $conHieuLuc ? $lan->duong_dan_thanh_toan : null,
            'amount' => $lan->so_tien_yeu_cau,
            'currency' => $lan->don_vi_tien,
            'status' => $lan->trang_thai,
            'expires_at' => $lan->het_han_luc?->toISOString(),
            'confirmed_at' => $lan->xac_nhan_luc?->toISOString(),
        ];
    }
}
