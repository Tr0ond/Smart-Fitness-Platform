<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Models\DonMuaGoi;
use App\Models\HoSoHoiVien;
use App\Models\LanThanhToan;
use App\Models\SuKienThanhToan;
use App\Services\MembershipProvisioningService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class PayOSWebhookService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly MembershipProvisioningService $provisioning,
    ) {}

    /**
     * Xác minh chữ ký trước, sau đó ghi event và finalize dưới thứ tự khóa
     * Member → event → order → payment. Payload lưu chỉ gồm allow-list không PII.
     *
     * @return array{result: string, event_id: int|null}
     */
    public function xuLy(array $payload): array
    {
        $maBamRaw = hash('sha256', $this->jsonOnDinh($payload));
        try {
            $duLieu = $this->gateway->xacMinhWebhook($payload);
        } catch (InvalidWebhookSignatureException $exception) {
            $this->ghiNhanChuKySai($maBamRaw);
            throw $exception;
        }

        $daLoc = $this->locDuLieuDaXacMinh($duLieu);
        $maBam = hash('sha256', $this->jsonOnDinh($duLieu));
        $khoaChongLap = hash('sha256', implode('|', [
            (string) config('payos.channel_code', 'PAYOS'),
            (string) ($daLoc['orderCode'] ?? ''),
            (string) ($daLoc['paymentLinkId'] ?? ''),
            (string) ($daLoc['reference'] ?? ''),
            $maBam,
        ]));

        $maDonCong = $this->soNguyenDuong($daLoc['orderCode'] ?? null);
        $lanBanDau = $maDonCong === null ? null : LanThanhToan::query()
            ->where('ma_kenh_thanh_toan', (string) config('payos.channel_code', 'PAYOS'))
            ->where('ma_don_cong_thanh_toan', $maDonCong)
            ->first();
        if ($lanBanDau === null) {
            return $this->ghiNhanKhongTimThay($daLoc, $maBam, $khoaChongLap);
        }

        $donBanDau = DonMuaGoi::query()->find($lanBanDau->don_mua_goi_id);
        if ($donBanDau === null) {
            return $this->ghiNhanKhongTimThay($daLoc, $maBam, $khoaChongLap);
        }

        return DB::transaction(function () use ($lanBanDau, $donBanDau, $duLieu, $daLoc, $maBam, $khoaChongLap): array {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($donBanDau->hoi_vien_id);
            $eventCu = SuKienThanhToan::query()->where('khoa_chong_lap', $khoaChongLap)->lockForUpdate()->first();
            if ($eventCu !== null) {
                $eventCu->forceFill([
                    'so_lan_nhan' => ((int) $eventCu->so_lan_nhan) + 1,
                    'nhan_cuoi_luc' => CarbonImmutable::now('UTC'),
                ])->save();

                return ['result' => 'DA_XU_LY_TRUOC', 'event_id' => (int) $eventCu->getKey()];
            }

            $don = DonMuaGoi::query()->lockForUpdate()->findOrFail($donBanDau->getKey());
            $lan = LanThanhToan::query()->lockForUpdate()->findOrFail($lanBanDau->getKey());
            $event = $this->taoEvent($lan, $daLoc, $maBam, $khoaChongLap);

            if (($daLoc['code'] ?? null) !== '00') {
                $event->forceFill([
                    'trang_thai_xu_ly' => 'BI_TU_CHOI',
                    'xu_ly_luc' => CarbonImmutable::now('UTC'),
                    'ly_do' => 'PAYOS_NON_SUCCESS_EVENT',
                ])->save();

                return ['result' => 'KHONG_THANH_CONG', 'event_id' => (int) $event->getKey()];
            }

            $batThuong = $this->lyDoBatThuong($duLieu, $daLoc, $don, $lan);
            if ($batThuong !== null) {
                return $this->chuyenDoiSoat($event, $don, $lan, $batThuong);
            }

            $thamChieu = (string) $daLoc['reference'];
            if ($lan->ma_tham_chieu_duoc_chap_nhan !== null) {
                if (hash_equals((string) $lan->ma_tham_chieu_duoc_chap_nhan, $thamChieu)) {
                    $event->forceFill([
                        'trang_thai_xu_ly' => 'DA_XU_LY',
                        'xu_ly_luc' => CarbonImmutable::now('UTC'),
                        'ly_do' => 'REFERENCE_ALREADY_CONFIRMED',
                    ])->save();

                    return ['result' => 'DA_XAC_NHAN_TRUOC', 'event_id' => (int) $event->getKey()];
                }

                return $this->chuyenDoiSoat($event, $don, $lan, 'CONFLICTING_SUCCESS_REFERENCE');
            }

            if ($don->trang_thai !== 'CHO_THANH_TOAN' || ! in_array($lan->trang_thai, ['DANG_TAO', 'CHO_THANH_TOAN'], true)) {
                return $this->chuyenDoiSoat($event, $don, $lan, 'LOCAL_STATE_CONFLICT');
            }

            $xacNhanLuc = CarbonImmutable::now('UTC');
            try {
                $thanhToanLuc = CarbonImmutable::parse((string) $daLoc['transactionDateTime'], 'Asia/Ho_Chi_Minh')->utc();
            } catch (Throwable) {
                return $this->chuyenDoiSoat($event, $don, $lan, 'INVALID_PROVIDER_DATETIME');
            }

            $lan->forceFill([
                'trang_thai' => 'THANH_CONG',
                'ma_tham_chieu_duoc_chap_nhan' => $thamChieu,
                'so_tien_da_nhan' => $daLoc['amount'],
                'thanh_toan_luc' => $thanhToanLuc,
                'xac_nhan_luc' => $xacNhanLuc,
                'ma_loi' => null,
            ])->save();
            $don->forceFill([
                'trang_thai' => 'DA_THANH_TOAN',
                'thanh_toan_luc' => $xacNhanLuc,
            ])->save();

            $this->provisioning->capTuThanhToanDaXacNhan((int) $don->getKey(), (int) $lan->getKey());
            $event->forceFill([
                'trang_thai_xu_ly' => 'DA_XU_LY',
                'xu_ly_luc' => $xacNhanLuc,
                'ly_do' => null,
            ])->save();

            return ['result' => 'DA_XAC_NHAN', 'event_id' => (int) $event->getKey()];
        }, 3);
    }

    private function ghiNhanChuKySai(string $maBam): void
    {
        $hienTai = CarbonImmutable::now('UTC');
        SuKienThanhToan::query()->create([
            'lan_thanh_toan_id' => null,
            'ma_kenh_thanh_toan' => (string) config('payos.channel_code', 'PAYOS'),
            'ma_don_cong_thanh_toan' => null,
            'ma_lien_ket_thanh_toan' => null,
            'ma_tham_chieu' => null,
            'so_tien' => null,
            'don_vi_tien' => null,
            'ma_ket_qua' => null,
            'chu_ky_hop_le' => false,
            'khoa_chong_lap' => null,
            'ma_bam_noi_dung' => $maBam,
            'du_lieu_da_loc' => ['received' => true],
            'trang_thai_xu_ly' => 'BI_TU_CHOI',
            'so_lan_nhan' => 1,
            'nhan_dau_luc' => $hienTai,
            'nhan_cuoi_luc' => $hienTai,
            'xu_ly_luc' => $hienTai,
            'ly_do' => 'INVALID_SIGNATURE',
        ]);
    }

    /** @param array<string, mixed> $daLoc */
    private function ghiNhanKhongTimThay(array $daLoc, string $maBam, string $khoaChongLap): array
    {
        return DB::transaction(function () use ($daLoc, $maBam, $khoaChongLap): array {
            $eventCu = SuKienThanhToan::query()->where('khoa_chong_lap', $khoaChongLap)->lockForUpdate()->first();
            if ($eventCu !== null) {
                $eventCu->forceFill([
                    'so_lan_nhan' => ((int) $eventCu->so_lan_nhan) + 1,
                    'nhan_cuoi_luc' => CarbonImmutable::now('UTC'),
                ])->save();

                return ['result' => 'DA_XU_LY_TRUOC', 'event_id' => (int) $eventCu->getKey()];
            }

            $hienTai = CarbonImmutable::now('UTC');
            $event = SuKienThanhToan::query()->create([
                'lan_thanh_toan_id' => null,
                'ma_kenh_thanh_toan' => (string) config('payos.channel_code', 'PAYOS'),
                'ma_don_cong_thanh_toan' => $this->soNguyenDuong($daLoc['orderCode'] ?? null),
                'ma_lien_ket_thanh_toan' => $this->chuoiGioiHan($daLoc['paymentLinkId'] ?? null, 100),
                'ma_tham_chieu' => $this->chuoiGioiHan($daLoc['reference'] ?? null, 150),
                'so_tien' => $this->soTien($daLoc['amount'] ?? null),
                'don_vi_tien' => $this->chuoiGioiHan($daLoc['currency'] ?? null, 3),
                'ma_ket_qua' => $this->chuoiGioiHan($daLoc['code'] ?? null, 30),
                'chu_ky_hop_le' => true,
                'khoa_chong_lap' => $khoaChongLap,
                'ma_bam_noi_dung' => $maBam,
                'du_lieu_da_loc' => $daLoc,
                'trang_thai_xu_ly' => 'CAN_DOI_SOAT',
                'so_lan_nhan' => 1,
                'nhan_dau_luc' => $hienTai,
                'nhan_cuoi_luc' => $hienTai,
                'xu_ly_luc' => $hienTai,
                'ly_do' => 'UNKNOWN_PROVIDER_ORDER',
            ]);

            return ['result' => 'CAN_DOI_SOAT', 'event_id' => (int) $event->getKey()];
        }, 3);
    }

    /** @param array<string, mixed> $daLoc */
    private function taoEvent(LanThanhToan $lan, array $daLoc, string $maBam, string $khoaChongLap): SuKienThanhToan
    {
        $hienTai = CarbonImmutable::now('UTC');

        return SuKienThanhToan::query()->create([
            'lan_thanh_toan_id' => $lan->getKey(),
            'ma_kenh_thanh_toan' => (string) config('payos.channel_code', 'PAYOS'),
            'ma_don_cong_thanh_toan' => $this->soNguyenDuong($daLoc['orderCode'] ?? null),
            'ma_lien_ket_thanh_toan' => $this->chuoiGioiHan($daLoc['paymentLinkId'] ?? null, 100),
            'ma_tham_chieu' => $this->chuoiGioiHan($daLoc['reference'] ?? null, 150),
            'so_tien' => $this->soTien($daLoc['amount'] ?? null),
            'don_vi_tien' => $this->chuoiGioiHan($daLoc['currency'] ?? null, 3),
            'ma_ket_qua' => $this->chuoiGioiHan($daLoc['code'] ?? null, 30),
            'chu_ky_hop_le' => true,
            'khoa_chong_lap' => $khoaChongLap,
            'ma_bam_noi_dung' => $maBam,
            'du_lieu_da_loc' => $daLoc,
            'trang_thai_xu_ly' => 'CHO_XU_LY',
            'so_lan_nhan' => 1,
            'nhan_dau_luc' => $hienTai,
            'nhan_cuoi_luc' => $hienTai,
            'xu_ly_luc' => null,
            'ly_do' => null,
        ]);
    }

    /** @param array<string, mixed> $duLieu @param array<string, mixed> $daLoc */
    private function lyDoBatThuong(array $duLieu, array $daLoc, DonMuaGoi $don, LanThanhToan $lan): ?string
    {
        $orderCode = $this->soNguyenDuong($duLieu['orderCode'] ?? null);
        $amount = $this->soTien($duLieu['amount'] ?? null);
        $currency = $duLieu['currency'] ?? null;
        $paymentLinkId = $duLieu['paymentLinkId'] ?? null;
        $reference = $duLieu['reference'] ?? null;

        return match (true) {
            $orderCode !== (int) $lan->ma_don_cong_thanh_toan => 'ORDER_CODE_MISMATCH',
            $amount === null || $amount !== (int) $don->so_tien_phai_thu || $amount !== (int) $lan->so_tien_yeu_cau => 'AMOUNT_MISMATCH',
            ! is_string($currency) || $currency !== 'VND' || $currency !== $lan->don_vi_tien => 'CURRENCY_MISMATCH',
            ! is_string($paymentLinkId) || strlen($paymentLinkId) > 100 || $paymentLinkId !== $lan->ma_lien_ket_thanh_toan => 'PAYMENT_LINK_MISMATCH',
            ! is_string($reference) || $reference === '' || strlen($reference) > 150 => 'INVALID_REFERENCE',
            ! is_string($daLoc['transactionDateTime'] ?? null) || $daLoc['transactionDateTime'] === '' => 'INVALID_PROVIDER_DATETIME',
            CarbonImmutable::now('UTC')->greaterThanOrEqualTo($don->het_han_thanh_toan_luc) => 'ORDER_EXPIRED',
            CarbonImmutable::now('UTC')->greaterThanOrEqualTo($lan->het_han_luc) => 'PAYMENT_LINK_EXPIRED',
            default => null,
        };
    }

    /** @return array{result: string, event_id: int} */
    private function chuyenDoiSoat(SuKienThanhToan $event, DonMuaGoi $don, LanThanhToan $lan, string $lyDo): array
    {
        $hienTai = CarbonImmutable::now('UTC');
        $don->forceFill(['trang_thai' => 'CAN_DOI_SOAT'])->save();
        $lan->forceFill(['trang_thai' => 'CAN_DOI_SOAT', 'ma_loi' => $lyDo])->save();
        $event->forceFill([
            'trang_thai_xu_ly' => 'CAN_DOI_SOAT',
            'xu_ly_luc' => $hienTai,
            'ly_do' => $lyDo,
        ])->save();

        return ['result' => 'CAN_DOI_SOAT', 'event_id' => (int) $event->getKey()];
    }

    /** @return array<string, mixed> */
    private function locDuLieuDaXacMinh(array $duLieu): array
    {
        return [
            'orderCode' => $this->soNguyenDuong($duLieu['orderCode'] ?? null),
            'amount' => $this->soTien($duLieu['amount'] ?? null),
            'description' => $this->chuoiGioiHan($duLieu['description'] ?? null, 250),
            'reference' => $this->chuoiGioiHan($duLieu['reference'] ?? null, 150),
            'transactionDateTime' => $this->chuoiGioiHan($duLieu['transactionDateTime'] ?? null, 100),
            'currency' => $this->chuoiGioiHan($duLieu['currency'] ?? null, 3),
            'paymentLinkId' => $this->chuoiGioiHan($duLieu['paymentLinkId'] ?? null, 100),
            'code' => $this->chuoiGioiHan($duLieu['code'] ?? null, 30),
            'desc' => $this->chuoiGioiHan($duLieu['desc'] ?? null, 250),
        ];
    }

    private function soNguyenDuong(mixed $giaTri): ?int
    {
        if (! is_int($giaTri) && ! (is_string($giaTri) && ctype_digit($giaTri))) {
            return null;
        }
        $so = (int) $giaTri;

        return $so >= 1 && $so <= 9007199254740991 ? $so : null;
    }

    private function soTien(mixed $giaTri): ?int
    {
        if (! is_int($giaTri) && ! (is_string($giaTri) && ctype_digit($giaTri))) {
            return null;
        }
        $so = (int) $giaTri;

        return $so >= 0 && $so <= 999999999999999 ? $so : null;
    }

    private function chuoiGioiHan(mixed $giaTri, int $doDai): ?string
    {
        return is_string($giaTri) && $giaTri !== '' ? mb_substr($giaTri, 0, $doDai) : null;
    }

    private function jsonOnDinh(mixed $duLieu): string
    {
        if (is_array($duLieu)) {
            if (array_is_list($duLieu)) {
                $duLieu = array_map(fn (mixed $giaTri): mixed => json_decode($this->jsonOnDinh($giaTri), true), $duLieu);
            } else {
                ksort($duLieu);
                foreach ($duLieu as $khoa => $giaTri) {
                    $duLieu[$khoa] = json_decode($this->jsonOnDinh($giaTri), true);
                }
            }
        }

        return json_encode($duLieu, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
