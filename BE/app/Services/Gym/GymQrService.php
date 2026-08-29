<?php

namespace App\Services\Gym;

use App\Exceptions\Gym\GymWorkflowException;
use App\Models\DangKyGoiTap;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use App\Models\MaVaoPhongTap;
use App\Models\NguoiDung;
use App\Services\MembershipEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GymQrService
{
    public function __construct(private readonly MembershipEntitlementService $quyenLoi) {}

    /**
     * Phát hành credential QR động cho chính Member đang xác thực.
     *
     * Member được khóa trước chuỗi/kỳ và QR. Quyền Gym được đọc từ snapshot kỳ
     * áp dụng hoặc head CHO_KICH_HOAT; việc phát hành không tạo usage và không
     * kích hoạt Membership. QR cũ chưa dùng, chưa hết hạn bị thu hồi trong cùng
     * transaction để một Member chỉ còn một credential đang dùng được.
     *
     * @return array{qr_token: string, expires_at: string, ttl_seconds: int}
     */
    public function phatHanh(NguoiDung $nguoiDung): array
    {
        $hoiVienBanDau = HoSoHoiVien::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->first();
        if ($hoiVienBanDau === null) {
            throw new GymWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
        }

        return DB::transaction(function () use ($nguoiDung, $hoiVienBanDau): array {
            $hienTai = CarbonImmutable::now('UTC');
            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->find($hoiVienBanDau->getKey());
            if ($hoiVien === null) {
                throw new GymWorkflowException('Không tìm thấy hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
            }
            $this->damBaoTaiKhoanVaVaiTro($nguoiDung->getKey(), 'MEMBER');

            $ketQuaQuyen = $this->quyenLoi->kiemTra(
                (int) $hoiVien->getKey(),
                MembershipEntitlementService::VAO_PHONG_TAP,
                $hienTai,
                true,
            );
            if (! $ketQuaQuyen['allowed'] || $ketQuaQuyen['term_id'] === null) {
                throw new GymWorkflowException('Membership hiện tại không cấp quyền vào phòng tập.', 403, 'GYM_ACCESS_DENIED');
            }

            $ky = KyHanHoiVien::query()->lockForUpdate()->find($ketQuaQuyen['term_id']);
            $chuoi = $ky?->dang_ky_goi_tap_id === null
                ? null
                : DangKyGoiTap::query()->lockForUpdate()->find($ky->dang_ky_goi_tap_id);
            if ($ky === null || $chuoi === null || (int) $ky->hoi_vien_id !== (int) $hoiVien->getKey()) {
                throw new GymWorkflowException('Không xác định được kỳ Membership áp dụng.', 409, 'MEMBERSHIP_STATE_CONFLICT');
            }

            MaVaoPhongTap::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->whereNull('thu_hoi_luc')
                ->whereNull('da_su_dung_luc')
                ->where('het_han_luc', '>', $hienTai)
                ->lockForUpdate()
                ->get()
                ->each(function (MaVaoPhongTap $ma) use ($hienTai): void {
                    $ma->forceFill(['thu_hoi_luc' => $hienTai])->save();
                });

            $ttl = max(1, (int) config('gym.qr_ttl_seconds', 90));
            $hetHan = $hienTai->addSeconds($ttl);
            do {
                $rawToken = bin2hex(random_bytes(32));
                $maBam = hash('sha256', $rawToken);
            } while (MaVaoPhongTap::query()->where('ma_bam_bi_mat', $maBam)->exists());

            MaVaoPhongTap::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'chi_nhanh_id' => $chuoi->chi_nhanh_id,
                'ma_bam_bi_mat' => $maBam,
                'phat_hanh_luc' => $hienTai,
                'het_han_luc' => $hetHan,
                'thu_hoi_luc' => null,
                'da_su_dung_luc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);

            return [
                'qr_token' => $rawToken,
                'expires_at' => $hetHan->toISOString(),
                'ttl_seconds' => $ttl,
            ];
        }, 3);
    }

    private function damBaoTaiKhoanVaVaiTro(int $nguoiDungId, string $vaiTro): void
    {
        $taiKhoan = NguoiDung::query()->lockForUpdate()->find($nguoiDungId);
        $coVaiTro = $taiKhoan?->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->where('ma_vai_tro', $vaiTro))
            ->exists() ?? false;

        if ($taiKhoan === null || $taiKhoan->trang_thai !== 'HOAT_DONG' || ! $coVaiTro) {
            throw new GymWorkflowException('Tài khoản hội viên không còn đủ quyền.', 403, 'MEMBER_NOT_ELIGIBLE');
        }
    }
}
