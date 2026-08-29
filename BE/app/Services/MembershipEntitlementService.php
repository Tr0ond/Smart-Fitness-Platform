<?php

namespace App\Services;

use App\Exceptions\MembershipLifecycleException;
use App\Models\DangKyGoiTap;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MembershipEntitlementService
{
    public const VAO_PHONG_TAP = 'VAO_PHONG_TAP';

    public const YEU_CAU_TRO_LY = 'YEU_CAU_TRO_LY';

    public const BUOI_HUAN_LUYEN = 'BUOI_HUAN_LUYEN';

    public const TRO_CHUYEN_HUAN_LUYEN = 'TRO_CHUYEN_HUAN_LUYEN';

    public function __construct(private readonly MembershipLifecycleService $vongDoi) {}

    /**
     * Kiểm tra đúng một quyền trên đúng một snapshot kỳ, không tạo usage/quota.
     *
     * Mặc định chỉ kỳ đang hoạt động theo [start,end) được xét. Nghiệp vụ nguồn
     * có thể bật $choPhepKichHoat để kiểm tra kỳ đầu CHO_KICH_HOAT trước khi tạo
     * usage. Kỳ tương lai không bao giờ được mượn quyền và quyền các kỳ không được
     * cộng hoặc trộn với nhau.
     *
     * @return array{allowed: bool, reason: string, term_id: int|null, can_activate: bool}
     */
    public function kiemTra(
        int $hoiVienId,
        string $loaiSuDung,
        ?CarbonImmutable $thoiDiem = null,
        bool $choPhepKichHoat = false,
    ): array {
        if (! in_array($loaiSuDung, [
            self::VAO_PHONG_TAP,
            self::YEU_CAU_TRO_LY,
            self::BUOI_HUAN_LUYEN,
            self::TRO_CHUYEN_HUAN_LUYEN,
        ], true)) {
            throw new MembershipLifecycleException('Loại quyền lợi không hợp lệ.');
        }

        $thoiDiem ??= CarbonImmutable::now('UTC');
        $this->vongDoi->doiChieuHoiVien($hoiVienId, $thoiDiem);

        return DB::transaction(function () use ($hoiVienId, $loaiSuDung, $thoiDiem, $choPhepKichHoat): array {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($hoiVienId);
            $chuoi = DangKyGoiTap::query()
                ->where('hoi_vien_id', $hoiVienId)
                ->whereIn('trang_thai', ['CHO_KICH_HOAT', 'DANG_HOAT_DONG'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if ($chuoi === null) {
                return $this->tuChoi('KHONG_CO_MEMBERSHIP');
            }

            $cacKy = $chuoi->kyHanHoiViens()
                ->orderBy('so_thu_tu')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $ky = $cacKy->first(fn (KyHanHoiVien $muc): bool => $muc->trang_thai !== 'HUY'
                && $muc->ngay_bat_dau !== null
                && $muc->ngay_ket_thuc !== null
                && $thoiDiem->greaterThanOrEqualTo($muc->ngay_bat_dau)
                && $thoiDiem->lessThan($muc->ngay_ket_thuc));
            $coTheKichHoat = false;

            if (! $ky instanceof KyHanHoiVien
                && $choPhepKichHoat
                && $chuoi->trang_thai === 'CHO_KICH_HOAT') {
                $ky = $cacKy->first();
                $coTheKichHoat = $ky instanceof KyHanHoiVien && $ky->trang_thai === 'CHO_KICH_HOAT';
            }
            if (! $ky instanceof KyHanHoiVien) {
                return $this->tuChoi('KHONG_CO_KY_AP_DUNG');
            }

            $duocPhep = match ($loaiSuDung) {
                self::VAO_PHONG_TAP => $ky->cho_phep_vao_phong_tap,
                self::YEU_CAU_TRO_LY => $ky->cho_phep_tro_ly_tap_luyen
                    && ($ky->gioi_han_luot_tro_ly === null
                        || $ky->so_luot_tro_ly_da_dung + $ky->so_luot_tro_ly_giu_cho < $ky->gioi_han_luot_tro_ly),
                self::BUOI_HUAN_LUYEN => $ky->so_buoi_huan_luyen_vien_da_dung < $ky->so_buoi_huan_luyen_vien,
                self::TRO_CHUYEN_HUAN_LUYEN => $ky->cho_phep_tro_chuyen_huan_luyen_vien,
            };

            return [
                'allowed' => (bool) $duocPhep,
                'reason' => $duocPhep ? 'DUOC_PHEP' : 'KHONG_CO_QUYEN_HOAC_HET_HAN_MUC',
                'term_id' => (int) $ky->getKey(),
                'can_activate' => $duocPhep && $coTheKichHoat,
            ];
        });
    }

    /** @return array{allowed: false, reason: string, term_id: null, can_activate: false} */
    private function tuChoi(string $lyDo): array
    {
        return ['allowed' => false, 'reason' => $lyDo, 'term_id' => null, 'can_activate' => false];
    }
}
