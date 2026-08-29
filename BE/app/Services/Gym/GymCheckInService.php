<?php

namespace App\Services\Gym;

use App\Exceptions\Gym\GymWorkflowException;
use App\Exceptions\MembershipLifecycleException;
use App\Models\HoSoHoiVien;
use App\Models\LichSuVaoPhongTap;
use App\Models\MaVaoPhongTap;
use App\Models\NguoiDung;
use App\Models\SuDungQuyenLoi;
use App\Services\MembershipActivationService;
use App\Services\MembershipEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GymCheckInService
{
    public function __construct(
        private readonly MembershipEntitlementService $quyenLoi,
        private readonly MembershipActivationService $kichHoat,
    ) {}

    /**
     * Redeem một QR động để ghi nhận check-in đúng một lần.
     *
     * Token rõ chỉ được băm để tra cứu. Transaction khóa Member trước QR, đọc lại
     * account/role của Member và nhân viên, hạn QR, trạng thái sử dụng và đúng kỳ
     * entitlement tại thời điểm quét. Usage, activation, lịch sử và mốc consumed
     * cùng commit/rollback; replay hoặc hai request đồng thời chỉ một request thắng.
     *
     * @return array<string, mixed>
     */
    public function xacNhan(NguoiDung $nhanVien, string $rawToken): array
    {
        $maBam = hash('sha256', $rawToken);
        $maBanDau = MaVaoPhongTap::query()->where('ma_bam_bi_mat', $maBam)->first();
        if ($maBanDau === null) {
            throw new GymWorkflowException('Mã QR không hợp lệ.', 404, 'QR_NOT_FOUND');
        }

        return DB::transaction(function () use ($nhanVien, $maBam, $maBanDau): array {
            $hienTai = CarbonImmutable::now('UTC');
            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->find($maBanDau->hoi_vien_id);
            if ($hoiVien === null) {
                throw new GymWorkflowException('Không tìm thấy hồ sơ hội viên.', 404, 'MEMBER_NOT_FOUND');
            }

            $taiKhoanHoiVien = $this->damBaoTaiKhoanVaVaiTro((int) $hoiVien->nguoi_dung_id, ['MEMBER'], 'MEMBER_NOT_ELIGIBLE');
            $this->damBaoTaiKhoanVaVaiTro((int) $nhanVien->getKey(), ['RECEPTIONIST', 'ADMIN'], 'STAFF_NOT_AUTHORIZED');

            $ma = MaVaoPhongTap::query()
                ->where('ma_bam_bi_mat', $maBam)
                ->lockForUpdate()
                ->first();
            if ($ma === null || (int) $ma->hoi_vien_id !== (int) $hoiVien->getKey()) {
                throw new GymWorkflowException('Mã QR không hợp lệ.', 404, 'QR_NOT_FOUND');
            }
            if ($ma->da_su_dung_luc !== null) {
                throw new GymWorkflowException('Mã QR đã được sử dụng.', 409, 'QR_ALREADY_USED');
            }
            if ($ma->thu_hoi_luc !== null) {
                throw new GymWorkflowException('Mã QR đã bị thu hồi.', 409, 'QR_REVOKED');
            }
            if ($hienTai->greaterThanOrEqualTo($ma->het_han_luc)) {
                throw new GymWorkflowException('Mã QR đã hết hạn.', 410, 'QR_EXPIRED');
            }

            $ketQuaQuyen = $this->quyenLoi->kiemTra(
                (int) $hoiVien->getKey(),
                MembershipEntitlementService::VAO_PHONG_TAP,
                $hienTai,
                true,
            );
            if (! $ketQuaQuyen['allowed'] || $ketQuaQuyen['term_id'] === null) {
                throw new GymWorkflowException('Membership hiện tại không cấp quyền vào phòng tập.', 403, 'GYM_ACCESS_DENIED');
            }

            $usage = SuDungQuyenLoi::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'ky_han_hoi_vien_id' => $ketQuaQuyen['term_id'],
                'nguoi_thuc_hien_id' => $nhanVien->getKey(),
                'loai_su_dung' => MembershipEntitlementService::VAO_PHONG_TAP,
                'ma_hanh_dong' => (string) Str::uuid(),
                'chap_nhan_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);

            try {
                $ketQuaKichHoat = $this->kichHoat->kichHoatNeuCan((int) $usage->getKey());
            } catch (MembershipLifecycleException $exception) {
                throw new GymWorkflowException($exception->getMessage(), 409, 'MEMBERSHIP_STATE_CONFLICT');
            }

            $lichSu = LichSuVaoPhongTap::query()->create([
                'ma_vao_phong_tap_id' => $ma->getKey(),
                'hoi_vien_id' => $hoiVien->getKey(),
                'chi_nhanh_id' => $ma->chi_nhanh_id,
                'ky_han_hoi_vien_id' => $ketQuaQuyen['term_id'],
                'su_dung_quyen_loi_id' => $usage->getKey(),
                'nguoi_xac_nhan_id' => $nhanVien->getKey(),
                'vao_phong_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);
            $ma->forceFill(['da_su_dung_luc' => $hienTai])->save();

            return [
                'check_in_id' => (int) $lichSu->getKey(),
                'checked_in_at' => $hienTai->toISOString(),
                'member' => [
                    'id' => (int) $hoiVien->getKey(),
                    'member_code' => $hoiVien->ma_hoi_vien,
                    'name' => $taiKhoanHoiVien->ho_ten,
                ],
                'branch_id' => (int) $ma->chi_nhanh_id,
                'membership_term_id' => (int) $ketQuaQuyen['term_id'],
                'usage_id' => (int) $usage->getKey(),
                'activation' => $ketQuaKichHoat['result'],
            ];
        }, 3);
    }

    /** @param array<int, string> $cacVaiTro */
    private function damBaoTaiKhoanVaVaiTro(
        int $nguoiDungId,
        array $cacVaiTro,
        string $maLoi,
    ): NguoiDung {
        $taiKhoan = NguoiDung::query()->lockForUpdate()->find($nguoiDungId);
        $coVaiTro = $taiKhoan?->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->whereIn('ma_vai_tro', $cacVaiTro))
            ->exists() ?? false;

        if ($taiKhoan === null || $taiKhoan->trang_thai !== 'HOAT_DONG' || ! $coVaiTro) {
            throw new GymWorkflowException('Tài khoản không còn đủ quyền xác nhận check-in.', 403, $maLoi);
        }

        return $taiKhoan;
    }
}
