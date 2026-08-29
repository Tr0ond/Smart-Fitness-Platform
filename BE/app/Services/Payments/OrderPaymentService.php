<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Exceptions\Payments\PaymentGatewayException;
use App\Exceptions\Payments\PaymentWorkflowException;
use App\Models\DonMuaGoi;
use App\Models\GoiTap;
use App\Models\HoSoHoiVien;
use App\Models\LanThanhToan;
use App\Models\NguoiDung;
use App\Models\QuyenLoiGoiTap;
use App\Models\YeuCauChongLap;
use App\Services\MembershipSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    private const PHAM_VI_TAO_DON = 'TAO_DON_MUA_GOI';

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly MembershipSnapshotService $snapshot,
        private readonly OrderQueryService $query,
    ) {}

    /**
     * Tạo bền vững đơn/snapshot/payment trước, gọi payOS ngoài transaction rồi
     * khóa lại Member → đơn → payment để ghi kết quả. Khóa chống lặp thuộc user.
     *
     * @return array<string, mixed>
     */
    public function taoDon(NguoiDung $nguoiDung, int $goiTapId, string $khoaYeuCau): array
    {
        $this->kiemTraCauHinhUrl();
        $maBam = hash('sha256', json_encode(['package_id' => $goiTapId], JSON_THROW_ON_ERROR));

        $khoiTao = DB::transaction(function () use ($nguoiDung, $goiTapId, $khoaYeuCau, $maBam): array {
            $hoiVienBanDau = HoSoHoiVien::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->first();
            if ($hoiVienBanDau === null) {
                throw new PaymentWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 403, 'MEMBER_PROFILE_REQUIRED');
            }

            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->findOrFail($hoiVienBanDau->getKey());
            $yeuCauCu = YeuCauChongLap::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->where('pham_vi', self::PHAM_VI_TAO_DON)
                ->where('khoa_yeu_cau', $khoaYeuCau)
                ->lockForUpdate()
                ->first();
            if ($yeuCauCu !== null) {
                return $this->xuLyYeuCauLap($yeuCauCu, $maBam);
            }

            $goiTap = GoiTap::query()->lockForUpdate()->find($goiTapId);
            $quyenLoi = QuyenLoiGoiTap::query()
                ->where('goi_tap_id', $goiTapId)
                ->lockForUpdate()
                ->first();
            if ($goiTap === null || $goiTap->trang_thai !== 'DANG_BAN' || $quyenLoi === null) {
                throw new PaymentWorkflowException('Không tìm thấy gói tập đang bán.', 404, 'PACKAGE_NOT_PURCHASABLE');
            }

            $hienTai = CarbonImmutable::now('UTC');
            $hetHan = $hienTai->addMinutes(max(1, (int) config('payos.order_ttl_minutes', 30)));
            $yeuCau = YeuCauChongLap::query()->create([
                'nguoi_dung_id' => $nguoiDung->getKey(),
                'pham_vi' => self::PHAM_VI_TAO_DON,
                'khoa_yeu_cau' => $khoaYeuCau,
                'ma_bam_noi_dung' => $maBam,
                'trang_thai' => 'DANG_XU_LY',
                'ma_phan_hoi' => null,
                'ket_qua_da_loc' => null,
                'het_han_luc' => $hienTai->addHours(max(1, (int) config('payos.idempotency_ttl_hours', 24))),
            ]);
            $don = DonMuaGoi::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'goi_tap_id' => $goiTap->getKey(),
                'ma_don' => 'PAY-'.strtoupper(bin2hex(random_bytes(16))),
                'ma_yeu_cau' => $khoaYeuCau,
                'so_tien_phai_thu' => $goiTap->gia,
                'don_vi_tien' => 'VND',
                'trang_thai' => 'CHO_THANH_TOAN',
                'chot_gia_luc' => $hienTai,
                'het_han_thanh_toan_luc' => $hetHan,
                'thanh_toan_luc' => null,
                'huy_luc' => null,
                'ly_do_huy' => null,
            ]);

            $this->snapshot->taoChoDon((int) $don->getKey());
            $maDonCong = $this->taoMaDonCongThanhToan((int) $don->getKey(), 1);
            $lanThanhToan = LanThanhToan::query()->create([
                'don_mua_goi_id' => $don->getKey(),
                'so_lan' => 1,
                'ma_kenh_thanh_toan' => (string) config('payos.channel_code', 'PAYOS'),
                'ma_don_cong_thanh_toan' => $maDonCong,
                'ma_lien_ket_thanh_toan' => null,
                'duong_dan_thanh_toan' => null,
                'so_tien_yeu_cau' => $don->so_tien_phai_thu,
                'don_vi_tien' => 'VND',
                'trang_thai' => 'DANG_TAO',
                'ma_tham_chieu_duoc_chap_nhan' => null,
                'so_tien_da_nhan' => null,
                'thanh_toan_luc' => null,
                'xac_nhan_luc' => null,
                'het_han_luc' => $hetHan,
                'ma_loi' => null,
            ]);

            return [
                'reused' => false,
                'request_id' => (int) $yeuCau->getKey(),
                'member_id' => (int) $hoiVien->getKey(),
                'order_id' => (int) $don->getKey(),
                'payment_id' => (int) $lanThanhToan->getKey(),
                'order_code' => $maDonCong,
                'amount' => (int) $don->so_tien_phai_thu,
                'expires_at' => $hetHan,
            ];
        }, 3);

        if ($khoiTao['reused']) {
            return $this->ganQrCodeVaoPhanHoi(
                $this->query->duLieuDonTheoId($khoiTao['order_id']),
                $khoiTao['payment_id'],
                $khoiTao['qr_code'],
            );
        }

        try {
            $ketQua = $this->gateway->taoLienKetThanhToan([
                'order_code' => $khoiTao['order_code'],
                'amount' => $khoiTao['amount'],
                'description' => 'SFP '.$khoiTao['order_id'],
                'cancel_url' => (string) config('payos.cancel_url'),
                'return_url' => (string) config('payos.return_url'),
                'expired_at' => $khoiTao['expires_at']->getTimestamp(),
            ]);
        } catch (PaymentGatewayException $exception) {
            $this->ghiNhanLoiCongThanhToan($khoiTao, $exception);
            throw new PaymentWorkflowException($exception->getMessage(), $exception->responseStatus, $exception->safeCode);
        }

        $phanHoiKhongKhop = DB::transaction(function () use ($khoiTao, $ketQua): bool {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($khoiTao['member_id']);
            $don = DonMuaGoi::query()->lockForUpdate()->findOrFail($khoiTao['order_id']);
            $lan = LanThanhToan::query()->lockForUpdate()->findOrFail($khoiTao['payment_id']);
            $yeuCau = YeuCauChongLap::query()->lockForUpdate()->findOrFail($khoiTao['request_id']);

            $hopLe = $ketQua->orderCode === (int) $lan->ma_don_cong_thanh_toan
                && $ketQua->amount === (int) $lan->so_tien_yeu_cau
                && $ketQua->currency === $lan->don_vi_tien
                && $ketQua->paymentLinkId !== ''
                && strlen($ketQua->paymentLinkId) <= 100
                && filter_var($ketQua->checkoutUrl, FILTER_VALIDATE_URL) !== false
                && strlen($ketQua->checkoutUrl) <= 1000
                && $ketQua->qrCode !== ''
                && strlen($ketQua->qrCode) <= 20000
                && ($ketQua->expiredAt === null || $ketQua->expiredAt > CarbonImmutable::now('UTC')->getTimestamp());
            if (! $hopLe) {
                $don->forceFill(['trang_thai' => 'CAN_DOI_SOAT'])->save();
                $lan->forceFill(['trang_thai' => 'CAN_DOI_SOAT', 'ma_loi' => 'PAYOS_RESPONSE_MISMATCH'])->save();
                $yeuCau->forceFill([
                    'trang_thai' => 'THAT_BAI',
                    'ma_phan_hoi' => 502,
                    'ket_qua_da_loc' => ['order_id' => $don->getKey(), 'code' => 'PAYOS_RESPONSE_MISMATCH'],
                ])->save();

                return true;
            }

            $hetHanCong = $ketQua->expiredAt === null
                ? CarbonImmutable::instance($lan->het_han_luc)
                : CarbonImmutable::createFromTimestampUTC($ketQua->expiredAt);
            $hetHan = $hetHanCong->lessThan($lan->het_han_luc) ? $hetHanCong : CarbonImmutable::instance($lan->het_han_luc);
            $lan->forceFill([
                'ma_lien_ket_thanh_toan' => $ketQua->paymentLinkId,
                'duong_dan_thanh_toan' => $ketQua->checkoutUrl,
                'trang_thai' => 'CHO_THANH_TOAN',
                'het_han_luc' => $hetHan,
                'ma_loi' => null,
            ])->save();
            $yeuCau->forceFill([
                'trang_thai' => 'DA_HOAN_TAT',
                'ma_phan_hoi' => 201,
                'ket_qua_da_loc' => [
                    'order_id' => $don->getKey(),
                    'payment_id' => $lan->getKey(),
                    'qr_code' => $ketQua->qrCode,
                ],
            ])->save();

            return false;
        }, 3);

        if ($phanHoiKhongKhop) {
            throw new PaymentWorkflowException('Phản hồi cổng thanh toán không khớp đơn.', 502, 'PAYOS_RESPONSE_MISMATCH');
        }

        return $this->ganQrCodeVaoPhanHoi(
            $this->query->duLieuDonTheoId($khoiTao['order_id']),
            $khoiTao['payment_id'],
            $ketQua->qrCode,
        );
    }

    /** @return array{reused: true, order_id: int, payment_id: int, qr_code: string|null} */
    private function xuLyYeuCauLap(YeuCauChongLap $yeuCau, string $maBam): array
    {
        if (! hash_equals($yeuCau->ma_bam_noi_dung, $maBam)) {
            throw new PaymentWorkflowException('Idempotency-Key đã được dùng cho yêu cầu khác.', 409, 'IDEMPOTENCY_CONFLICT');
        }
        if ($yeuCau->trang_thai === 'DANG_XU_LY') {
            throw new PaymentWorkflowException('Yêu cầu đang được xử lý.', 409, 'IDEMPOTENCY_IN_PROGRESS');
        }
        $orderId = (int) ($yeuCau->ket_qua_da_loc['order_id'] ?? 0);
        if ($yeuCau->trang_thai !== 'DA_HOAN_TAT' || $orderId <= 0) {
            $code = (string) ($yeuCau->ket_qua_da_loc['code'] ?? 'PAYMENT_LINK_FAILED');
            $status = (int) $yeuCau->ma_phan_hoi;
            if ($status < 400 || $status > 599) {
                $status = 503;
            }
            throw new PaymentWorkflowException('Yêu cầu trước đó không tạo được liên kết thanh toán.', $status, $code);
        }

        return [
            'reused' => true,
            'order_id' => $orderId,
            'payment_id' => (int) ($yeuCau->ket_qua_da_loc['payment_id'] ?? 0),
            'qr_code' => is_string($yeuCau->ket_qua_da_loc['qr_code'] ?? null)
                ? $yeuCau->ket_qua_da_loc['qr_code']
                : null,
        ];
    }

    private function taoMaDonCongThanhToan(int $donId, int $soLan): int
    {
        $gioiHanDon = intdiv(9007199254740991 - 999, 1000);
        if ($donId <= 0 || $donId > $gioiHanDon || $soLan < 1 || $soLan > 999) {
            throw new PaymentWorkflowException('Không thể sinh mã đơn cổng thanh toán.', 500, 'ORDER_CODE_RANGE');
        }

        return ($donId * 1000) + $soLan;
    }

    /** @param array<string, mixed> $khoiTao */
    private function ghiNhanLoiCongThanhToan(array $khoiTao, PaymentGatewayException $exception): void
    {
        DB::transaction(function () use ($khoiTao, $exception): void {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($khoiTao['member_id']);
            $don = DonMuaGoi::query()->lockForUpdate()->findOrFail($khoiTao['order_id']);
            $lan = LanThanhToan::query()->lockForUpdate()->findOrFail($khoiTao['payment_id']);
            $yeuCau = YeuCauChongLap::query()->lockForUpdate()->findOrFail($khoiTao['request_id']);
            $canDoiSoat = in_array($exception->safeCode, ['PAYOS_TIMEOUT', 'PAYOS_CONNECTION', 'PAYOS_UPSTREAM'], true);

            $lan->forceFill([
                'trang_thai' => $canDoiSoat ? 'CAN_DOI_SOAT' : 'THAT_BAI',
                'ma_loi' => $exception->safeCode,
            ])->save();
            if ($canDoiSoat) {
                $don->forceFill(['trang_thai' => 'CAN_DOI_SOAT'])->save();
            }
            $yeuCau->forceFill([
                'trang_thai' => 'THAT_BAI',
                'ma_phan_hoi' => $exception->responseStatus,
                'ket_qua_da_loc' => ['order_id' => $don->getKey(), 'payment_id' => $lan->getKey(), 'code' => $exception->safeCode],
            ])->save();
        }, 3);
    }

    private function kiemTraCauHinhUrl(): void
    {
        foreach (['return_url', 'cancel_url'] as $ten) {
            $url = (string) config('payos.'.$ten);
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                throw new PaymentWorkflowException('Cấu hình URL thanh toán chưa hợp lệ.', 503, 'PAYOS_URL_NOT_CONFIGURED');
            }
        }
    }

    /** @param array<string, mixed> $duLieu @return array<string, mixed> */
    private function ganQrCodeVaoPhanHoi(array $duLieu, int $paymentId, ?string $qrCode): array
    {
        foreach ($duLieu['payments'] as &$payment) {
            if ((int) $payment['id'] === $paymentId) {
                $payment['qr_code'] = $qrCode;
                break;
            }
        }
        unset($payment);

        return $duLieu;
    }
}
