<?php

namespace App\Services;

use App\Models\NguoiDung;
use App\Models\TheTruyCap;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public const CURRENT_TOKEN_ATTRIBUTE = 'the_truy_cap_hien_tai';

    private const TRANG_THAI_DUOC_PHEP = 'HOAT_DONG';

    private const CAC_VAI_TRO_CHINH_THUC = ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'];

    private const ROUTE_DOC_HO_SO_PT_KHONG_GHI_NHAN_SU_DUNG = 'admin.accounts.trainer-profile.show';

    /**
     * Xác minh thông tin đăng nhập và phát hành một Bearer token mới.
     *
     * Input: email canonical, mật khẩu rõ từ request và tên thiết bị. Mật khẩu
     * được kiểm tra bằng Hash API; tài khoản và role được đọc lại dưới khóa.
     * Output: raw token chỉ cho response lần này, token model và user. Side
     * effect: lưu SHA-256 hash, hạn token và mốc đăng nhập gần nhất.
     *
     * @return array{raw_token: string, token: TheTruyCap, user: NguoiDung}|null
     */
    public function dangNhap(string $thuDienTu, string $matKhau, string $tenThietBi): ?array
    {
        $nguoiDung = NguoiDung::query()->where('thu_dien_tu', $thuDienTu)->first();

        if (! $nguoiDung || ! Hash::check($matKhau, $nguoiDung->mat_khau_bam)) {
            return null;
        }

        $maBamMatKhauDaKiemTra = (string) $nguoiDung->mat_khau_bam;

        return DB::transaction(function () use ($nguoiDung, $maBamMatKhauDaKiemTra, $tenThietBi): ?array {
            $nguoiDungDaKhoa = NguoiDung::query()->lockForUpdate()->find($nguoiDung->getKey());

            if (
                ! $nguoiDungDaKhoa
                || $nguoiDungDaKhoa->trang_thai !== self::TRANG_THAI_DUOC_PHEP
                || ! hash_equals($maBamMatKhauDaKiemTra, (string) $nguoiDungDaKhoa->mat_khau_bam)
                || ! $this->coVaiTroDangHoatDong($nguoiDungDaKhoa)
            ) {
                return null;
            }

            $hienTai = CarbonImmutable::now('UTC');
            $soPhutHieuLuc = max(1, (int) config('auth.access_token_lifetime_minutes', 43200));
            $theDaPhatHanh = $this->taoTheNgauNhien($nguoiDungDaKhoa, $tenThietBi, $hienTai, $soPhutHieuLuc);

            $nguoiDungDaKhoa->forceFill(['dang_nhap_gan_nhat_luc' => $hienTai])->save();

            return [
                'raw_token' => $theDaPhatHanh['raw_token'],
                'token' => $theDaPhatHanh['token'],
                'user' => $nguoiDungDaKhoa,
            ];
        });
    }

    /**
     * Xác thực Bearer token cho mỗi protected request.
     *
     * Input: Authorization header. Hàm kiểm tra định dạng, SHA-256 hash, hạn,
     * thu hồi và trạng thái tài khoản. Output: NguoiDung cho Laravel guard hoặc
     * NULL. Side effect: cập nhật mốc sử dụng gần nhất của token hợp lệ trên
     * các route thông thường; route đọc hồ sơ PT được đặt tên rõ ràng là
     * ngoại lệ đọc toàn request và giữ nguyên hàng telemetry.
     */
    public function xacThucYeuCau(Request $request): ?NguoiDung
    {
        $rawToken = $this->docBearerToken($request);

        if ($rawToken === null) {
            return null;
        }

        $hienTai = CarbonImmutable::now('UTC');
        $maBamThe = hash('sha256', $rawToken);
        $theTruyCap = TheTruyCap::query()
            ->with('nguoiDung')
            ->where('ma_bam_the', $maBamThe)
            ->whereNull('thu_hoi_luc')
            ->where('het_han_luc', '>', $hienTai)
            ->first();

        if (! $theTruyCap || ! $theTruyCap->nguoiDung || $theTruyCap->nguoiDung->trang_thai !== self::TRANG_THAI_DUOC_PHEP) {
            return null;
        }

        if (! $this->laYeuCauDocKhongGhiNhanSuDung($request)) {
            $thoiDiemTelemetry = $hienTai->format('Y-m-d H:i:s.u');
            TheTruyCap::query()->whereKey($theTruyCap->getKey())->update([
                'su_dung_gan_nhat_luc' => $thoiDiemTelemetry,
                'ngay_cap_nhat' => $thoiDiemTelemetry,
            ]);
            $theTruyCap->su_dung_gan_nhat_luc = $hienTai;
        }
        $request->attributes->set(self::CURRENT_TOKEN_ATTRIBUTE, $theTruyCap);

        return $theTruyCap->nguoiDung;
    }

    /**
     * Xác định request đọc hồ sơ PT cần giữ nguyên telemetry của thẻ truy cập.
     *
     * Input: Request đã được Laravel định tuyến.
     * Cách hoạt động: chỉ so khớp tên route nội bộ do Server đăng ký, không đọc
     * URL, header hay dữ liệu do Client gửi để quyết định chính sách.
     * Kết quả: true cho đúng GET hồ sơ PT read-only; false cho mọi route khác.
     * Side effect: không có; mọi route không khớp vẫn ghi nhận telemetry bình thường.
     */
    private function laYeuCauDocKhongGhiNhanSuDung(Request $request): bool
    {
        return $request->routeIs(self::ROUTE_DOC_HO_SO_PT_KHONG_GHI_NHAN_SU_DUNG);
    }

    /** Thu hồi đúng token đã xác thực của request; không xóa hàng lịch sử. */
    public function thuHoiTheHienTai(Request $request): bool
    {
        $theTruyCap = $request->attributes->get(self::CURRENT_TOKEN_ATTRIBUTE);

        if (! $theTruyCap instanceof TheTruyCap) {
            return false;
        }

        $hienTai = CarbonImmutable::now('UTC');

        return TheTruyCap::query()
            ->whereKey($theTruyCap->getKey())
            ->whereNull('thu_hoi_luc')
            ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]) === 1;
    }

    /** @return array<int, string> */
    public function layMaVaiTroDangHoatDong(NguoiDung $nguoiDung): array
    {
        return $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->whereIn('ma_vai_tro', self::CAC_VAI_TRO_CHINH_THUC))
            ->with('vaiTro:id,ma_vai_tro')
            ->get()
            ->pluck('vaiTro.ma_vai_tro')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function coVaiTroDangHoatDong(NguoiDung $nguoiDung): bool
    {
        return $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->whereIn('ma_vai_tro', self::CAC_VAI_TRO_CHINH_THUC))
            ->exists();
    }

    /**
     * @return array{raw_token: string, token: TheTruyCap}
     */
    private function taoTheNgauNhien(
        NguoiDung $nguoiDung,
        string $tenThietBi,
        CarbonImmutable $hienTai,
        int $soPhutHieuLuc,
    ): array {
        do {
            $rawToken = bin2hex(random_bytes(32));
            $maBamThe = hash('sha256', $rawToken);
        } while (TheTruyCap::query()->where('ma_bam_the', $maBamThe)->exists());

        $theTruyCap = TheTruyCap::query()->create([
            'nguoi_dung_id' => $nguoiDung->getKey(),
            'ten_thiet_bi' => $tenThietBi,
            'ma_bam_the' => $maBamThe,
            'pham_vi_truy_cap' => ['api'],
            'su_dung_gan_nhat_luc' => null,
            'het_han_luc' => $hienTai->addMinutes($soPhutHieuLuc),
            'thu_hoi_luc' => null,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);

        return ['raw_token' => $rawToken, 'token' => $theTruyCap];
    }

    private function docBearerToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (! is_string($authorization)) {
            return null;
        }

        if (preg_match('/\A[Bb][Ee][Aa][Rr][Ee][Rr] ([0-9a-f]{64})\z/', trim($authorization), $ketQua) !== 1) {
            return null;
        }

        return $ketQua[1];
    }
}
