<?php

namespace App\Services;

use App\Models\DangKyGoiTap;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class MembershipQueryService
{
    public function __construct(private readonly MembershipLifecycleService $vongDoi) {}

    /**
     * Trả Membership của chính user từ snapshot B18, không đọc ngược catalog.
     * Việc đọc chỉ đối chiếu projection thời gian; không tạo usage, không kích hoạt
     * và không đưa thông tin payment/provider hoặc Membership của người khác ra API.
     *
     * @return array<string, mixed>
     */
    public function layCuaNguoiDung(NguoiDung $nguoiDung): array
    {
        $hoiVien = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
        abort_if($hoiVien === null, 404, 'Không tìm thấy hồ sơ hội viên.');

        $this->vongDoi->doiChieuHoiVien($hoiVien->getKey());
        $hienTai = CarbonImmutable::now('UTC');
        $cacChuoi = DangKyGoiTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->with(['kyHanHoiViens' => fn ($truyVan) => $truyVan
                ->orderBy('so_thu_tu')
                ->orderBy('id')])
            ->orderByDesc('id')
            ->get();
        $cacKyChoThanhToan = KyHanHoiVien::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->whereNull('dang_ky_goi_tap_id')
            ->where('trang_thai', 'CHO_THANH_TOAN')
            ->orderByDesc('id')
            ->get()
            ->map(fn (KyHanHoiVien $ky): array => $this->duLieuKy($ky))
            ->all();

        $chuoiMo = $cacChuoi->first(fn (DangKyGoiTap $chuoi): bool => in_array(
            $chuoi->trang_thai,
            ['CHO_KICH_HOAT', 'DANG_HOAT_DONG'],
            true,
        ));

        return [
            'member_id' => (int) $hoiVien->getKey(),
            'current' => $chuoiMo instanceof DangKyGoiTap
                ? $this->duLieuChuoiHienTai($chuoiMo, $hienTai)
                : null,
            'pending_payment_terms' => $cacKyChoThanhToan,
            'history' => $cacChuoi
                ->map(fn (DangKyGoiTap $chuoi): array => $this->duLieuChuoi($chuoi))
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuChuoiHienTai(DangKyGoiTap $chuoi, CarbonImmutable $hienTai): array
    {
        /** @var Collection<int, KyHanHoiVien> $cacKy */
        $cacKy = $chuoi->kyHanHoiViens;
        $kyApDung = $cacKy->first(fn (KyHanHoiVien $ky): bool => $ky->trang_thai !== 'HUY'
            && $ky->ngay_bat_dau !== null
            && $ky->ngay_ket_thuc !== null
            && $hienTai->greaterThanOrEqualTo($ky->ngay_bat_dau)
            && $hienTai->lessThan($ky->ngay_ket_thuc));
        if (! $kyApDung instanceof KyHanHoiVien && $chuoi->trang_thai === 'CHO_KICH_HOAT') {
            $kyApDung = $cacKy->firstWhere('trang_thai', 'CHO_KICH_HOAT');
        }

        return [
            'registration' => $this->duLieuDangKy($chuoi),
            'applicable_term' => $kyApDung instanceof KyHanHoiVien ? $this->duLieuKy($kyApDung) : null,
            'queued_terms' => $cacKy
                ->filter(fn (KyHanHoiVien $ky): bool => $ky->trang_thai === 'CHO_DEN_LUOT')
                ->map(fn (KyHanHoiVien $ky): array => $this->duLieuKy($ky))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuChuoi(DangKyGoiTap $chuoi): array
    {
        return [
            'registration' => $this->duLieuDangKy($chuoi),
            'terms' => $chuoi->kyHanHoiViens
                ->map(fn (KyHanHoiVien $ky): array => $this->duLieuKy($ky))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuDangKy(DangKyGoiTap $chuoi): array
    {
        return [
            'id' => (int) $chuoi->getKey(),
            'branch_id' => (int) $chuoi->chi_nhanh_id,
            'status' => $chuoi->trang_thai,
            'started_at' => $chuoi->ngay_bat_dau?->toISOString(),
            'closed_at' => $chuoi->ket_thuc_ghi_nhan_luc?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuKy(KyHanHoiVien $ky): array
    {
        $soBuoiConLai = max(0, $ky->so_buoi_huan_luyen_vien - $ky->so_buoi_huan_luyen_vien_da_dung);

        return [
            'id' => (int) $ky->getKey(),
            'sequence' => $ky->so_thu_tu,
            'status' => $ky->trang_thai,
            'package_name' => $ky->ten_goi,
            'package_version' => $ky->phien_ban_goi,
            'purchase_price' => $ky->gia_da_mua,
            'currency' => 'VND',
            'duration_days' => $ky->thoi_han_ngay,
            'purchased_at' => $ky->mua_luc?->toISOString(),
            'starts_at' => $ky->ngay_bat_dau?->toISOString(),
            'ends_at' => $ky->ngay_ket_thuc?->toISOString(),
            'entitlements' => [
                'gym_access' => $ky->cho_phep_vao_phong_tap,
                'fitness_assistant' => [
                    'allowed' => $ky->cho_phep_tro_ly_tap_luyen,
                    'limit' => $ky->gioi_han_luot_tro_ly,
                    'used' => $ky->so_luot_tro_ly_da_dung,
                    'reserved' => $ky->so_luot_tro_ly_giu_cho,
                ],
                'trainer_chat' => $ky->cho_phep_tro_chuyen_huan_luyen_vien,
                'direct_trainer_sessions' => [
                    'total' => $ky->so_buoi_huan_luyen_vien,
                    'used' => $ky->so_buoi_huan_luyen_vien_da_dung,
                    'remaining' => $soBuoiConLai,
                ],
            ],
        ];
    }
}
