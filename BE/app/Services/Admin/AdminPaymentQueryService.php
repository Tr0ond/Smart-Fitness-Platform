<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\LanThanhToan;
use App\Models\NguoiDung;
use App\Models\SuKienThanhToan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AdminPaymentQueryService
{
    public function __construct(private readonly AdminActorGuard $guard) {}

    /** @return array{items: array<int, array<string,mixed>>, pagination: array<string,int>} */
    public function danhSachThanhToan(NguoiDung $actor, array $boLoc): array
    {
        $chiNhanhId = $this->chiNhanhId($actor);
        $truyVan = LanThanhToan::query()->with([
            'donMuaGoi.hoiVien.nguoiDung',
            'kyHanHoiVien',
            'suKienThanhToans',
        ]);
        $this->apDungScopeChiNhanhThanhToan($truyVan, $chiNhanhId);
        $this->apDungBoLocThanhToan($truyVan, $boLoc);

        $sapXep = [
            'created_at' => 'ngay_tao',
            'confirmed_at' => 'xac_nhan_luc',
            'expected_amount' => 'so_tien_yeu_cau',
            'received_amount' => 'so_tien_da_nhan',
        ];
        $truyVan->orderBy($sapXep[(string) ($boLoc['sort_by'] ?? 'created_at')], (string) ($boLoc['sort_direction'] ?? 'desc'))
            ->orderByDesc('id');
        $phanTrang = $truyVan->paginate((int) ($boLoc['per_page'] ?? 20));

        return $this->phanTrang($phanTrang, fn (LanThanhToan $lan): array => $this->duLieuThanhToan($lan));
    }

    /** @return array<string,mixed> */
    public function chiTietThanhToan(NguoiDung $actor, int $lanThanhToanId): array
    {
        $chiNhanhId = $this->chiNhanhId($actor);
        $truyVan = LanThanhToan::query()->with([
            'donMuaGoi.hoiVien.nguoiDung',
            'kyHanHoiVien',
            'suKienThanhToans',
        ]);
        $this->apDungScopeChiNhanhThanhToan($truyVan, $chiNhanhId);
        $lan = $truyVan->find($lanThanhToanId);
        if (! $lan instanceof LanThanhToan) {
            throw new AuthWorkflowException('Không tìm thấy lần thanh toán.', 404, 'PAYMENT_NOT_FOUND');
        }

        return $this->duLieuThanhToan($lan);
    }

    /** @return array{items: array<int, array<string,mixed>>, pagination: array<string,int>} */
    public function danhSachSuKien(NguoiDung $actor, array $boLoc): array
    {
        $chiNhanhId = $this->chiNhanhId($actor);
        $truyVan = SuKienThanhToan::query()->with(['lanThanhToan.donMuaGoi.hoiVien.nguoiDung']);
        $truyVan->whereNotNull('lan_thanh_toan_id')
            ->whereHas('lanThanhToan.donMuaGoi.hoiVien.nguoiDung', fn (Builder $nguoiDung) => $nguoiDung->where('chi_nhanh_id', $chiNhanhId));
        $this->apDungBoLocSuKien($truyVan, $boLoc);
        $sapXep = [
            'received_at' => 'nhan_dau_luc',
            'processed_at' => 'xu_ly_luc',
            'created_at' => 'ngay_tao',
        ];
        $phanTrang = $truyVan->orderBy(
            $sapXep[(string) ($boLoc['sort_by'] ?? 'received_at')],
            (string) ($boLoc['sort_direction'] ?? 'desc'),
        )->orderByDesc('id')
            ->paginate((int) ($boLoc['per_page'] ?? 20));

        return $this->phanTrang($phanTrang, fn (SuKienThanhToan $suKien): array => $this->duLieuSuKien($suKien));
    }

    private function chiNhanhId(NguoiDung $actor): int
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        if ($actor->chi_nhanh_id === null) {
            throw new AuthWorkflowException('Không xác định được chi nhánh quản trị.', 409, 'ADMIN_BRANCH_REQUIRED');
        }

        return (int) $actor->chi_nhanh_id;
    }

    /** @param Builder<LanThanhToan> $truyVan */
    private function apDungScopeChiNhanhThanhToan(Builder $truyVan, int $chiNhanhId): void
    {
        $truyVan->whereHas('donMuaGoi.hoiVien.nguoiDung', fn (Builder $nguoiDung) => $nguoiDung->where('chi_nhanh_id', $chiNhanhId));
    }

    /** @param Builder<LanThanhToan> $truyVan */
    private function apDungBoLocThanhToan(Builder $truyVan, array $boLoc): void
    {
        if (isset($boLoc['order_code'])) {
            $truyVan->whereHas('donMuaGoi', fn (Builder $don) => $don->where('ma_don', (string) $boLoc['order_code']));
        }
        if (isset($boLoc['member'])) {
            $this->apDungBoLocHoiVien($truyVan, (string) $boLoc['member']);
        }
        if (isset($boLoc['payment_status'])) {
            $truyVan->where('trang_thai', (string) $boLoc['payment_status']);
        }
        if (isset($boLoc['order_status'])) {
            $truyVan->whereHas('donMuaGoi', fn (Builder $don) => $don->where('trang_thai', (string) $boLoc['order_status']));
        }
        if (isset($boLoc['provider_order_code'])) {
            $truyVan->where('ma_don_cong_thanh_toan', (int) $boLoc['provider_order_code']);
        }
        if (isset($boLoc['provider_reference'])) {
            $truyVan->where('ma_tham_chieu_duoc_chap_nhan', (string) $boLoc['provider_reference']);
        }
        if (filter_var($boLoc['reconciliation_required'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $truyVan->where(function (Builder $query): void {
                $query->where('trang_thai', 'CAN_DOI_SOAT')
                    ->orWhereHas('donMuaGoi', fn (Builder $don) => $don->where('trang_thai', 'CAN_DOI_SOAT'));
            });
        }
        $this->apDungKhoangThoiGian($truyVan, 'ngay_tao', $boLoc);
    }

    /** @param Builder<SuKienThanhToan> $truyVan */
    private function apDungBoLocSuKien(Builder $truyVan, array $boLoc): void
    {
        if (isset($boLoc['order_code'])) {
            $truyVan->whereHas('lanThanhToan.donMuaGoi', fn (Builder $don) => $don->where('ma_don', (string) $boLoc['order_code']));
        }
        if (isset($boLoc['member'])) {
            $truyVan->whereHas('lanThanhToan', fn (Builder $lan) => $this->apDungBoLocHoiVien($lan, (string) $boLoc['member']));
        }
        if (isset($boLoc['processing_status'])) {
            $truyVan->where('trang_thai_xu_ly', (string) $boLoc['processing_status']);
        }
        if (isset($boLoc['provider_order_code'])) {
            $truyVan->where('ma_don_cong_thanh_toan', (int) $boLoc['provider_order_code']);
        }
        if (isset($boLoc['provider_reference'])) {
            $truyVan->where('ma_tham_chieu', (string) $boLoc['provider_reference']);
        }
        if (filter_var($boLoc['reconciliation_required'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $truyVan->where('trang_thai_xu_ly', 'CAN_DOI_SOAT');
        }
        $this->apDungKhoangThoiGian($truyVan, 'nhan_dau_luc', $boLoc);
    }

    /** @param Builder<LanThanhToan|SuKienThanhToan> $truyVan */
    private function apDungKhoangThoiGian(Builder $truyVan, string $cot, array $boLoc): void
    {
        if (isset($boLoc['from'])) {
            $truyVan->where($cot, '>=', CarbonImmutable::parse((string) $boLoc['from'], 'UTC')->startOfDay());
        }
        if (isset($boLoc['to'])) {
            $truyVan->where($cot, '<', CarbonImmutable::parse((string) $boLoc['to'], 'UTC')->addDay()->startOfDay());
        }
    }

    /** @param Builder<LanThanhToan> $truyVan */
    private function apDungBoLocHoiVien(Builder $truyVan, string $giaTri): void
    {
        $giaTri = trim($giaTri);
        $mau = '%'.addcslashes($giaTri, '\\%_').'%';
        $email = mb_strtolower($giaTri, 'UTF-8');
        $truyVan->whereHas('donMuaGoi.hoiVien', function (Builder $hoiVien) use ($mau, $email): void {
            $hoiVien->where('ma_hoi_vien', 'like', $mau)
                ->orWhereHas('nguoiDung', fn (Builder $nguoiDung) => $nguoiDung->where('thu_dien_tu', 'like', '%'.addcslashes($email, '\\%_').'%'));
        });
    }

    /** @return array<string,mixed> */
    private function duLieuThanhToan(LanThanhToan $lan): array
    {
        $don = $lan->donMuaGoi;
        $hoiVien = $don?->hoiVien;
        $nguoiDung = $hoiVien?->nguoiDung;
        $ky = $lan->kyHanHoiVien;

        return [
            'payment_id' => (int) $lan->getKey(),
            'attempt' => (int) $lan->so_lan,
            'channel' => $lan->ma_kenh_thanh_toan,
            'provider_order_code' => (int) $lan->ma_don_cong_thanh_toan,
            'provider_payment_link_id' => $lan->ma_lien_ket_thanh_toan,
            'provider_reference' => $lan->ma_tham_chieu_duoc_chap_nhan,
            'expected_amount' => $lan->so_tien_yeu_cau,
            'received_amount' => $lan->so_tien_da_nhan,
            'currency' => $lan->don_vi_tien,
            'status' => $lan->trang_thai,
            'reconciliation_reason' => $lan->ma_loi,
            'paid_at' => $lan->thanh_toan_luc?->toISOString(),
            'confirmed_at' => $lan->xac_nhan_luc?->toISOString(),
            'expires_at' => $lan->het_han_luc?->toISOString(),
            'order' => $don === null ? null : [
                'id' => (int) $don->getKey(),
                'code' => $don->ma_don,
                'status' => $don->trang_thai,
                'expected_amount' => $don->so_tien_phai_thu,
                'currency' => $don->don_vi_tien,
                'price_locked_at' => $don->chot_gia_luc?->toISOString(),
            ],
            'member' => $hoiVien === null || $nguoiDung === null ? null : [
                'id' => (int) $hoiVien->getKey(),
                'code' => $hoiVien->ma_hoi_vien,
                'account_id' => (int) $nguoiDung->getKey(),
                'email' => $nguoiDung->thu_dien_tu,
            ],
            'membership_term' => $ky === null ? null : [
                'id' => (int) $ky->getKey(),
                'status' => $ky->trang_thai,
                'sequence' => $ky->so_thu_tu,
                'package_name' => $ky->ten_goi,
                'package_version' => (int) $ky->phien_ban_goi,
                'purchase_price' => $ky->gia_da_mua,
            ],
            'events' => $lan->suKienThanhToans->sortBy('id')->map(fn (SuKienThanhToan $suKien): array => $this->duLieuSuKien($suKien))->values()->all(),
            'created_at' => $lan->ngay_tao?->toISOString(),
            'updated_at' => $lan->ngay_cap_nhat?->toISOString(),
        ];
    }

    /** @return array<string,mixed> */
    private function duLieuSuKien(SuKienThanhToan $suKien): array
    {
        return [
            'event_id' => (int) $suKien->getKey(),
            'payment_id' => $suKien->lan_thanh_toan_id === null ? null : (int) $suKien->lan_thanh_toan_id,
            'channel' => $suKien->ma_kenh_thanh_toan,
            'provider_order_code' => $suKien->ma_don_cong_thanh_toan,
            'provider_payment_link_id' => $suKien->ma_lien_ket_thanh_toan,
            'provider_reference' => $suKien->ma_tham_chieu,
            'amount' => $suKien->so_tien,
            'currency' => $suKien->don_vi_tien,
            'provider_result_code' => $suKien->ma_ket_qua,
            'processing_status' => $suKien->trang_thai_xu_ly,
            'receipt_count' => (int) $suKien->so_lan_nhan,
            'received_at' => $suKien->nhan_dau_luc?->toISOString(),
            'last_received_at' => $suKien->nhan_cuoi_luc?->toISOString(),
            'processed_at' => $suKien->xu_ly_luc?->toISOString(),
            'reconciliation_reason' => $suKien->ly_do,
            'created_at' => $suKien->ngay_tao?->toISOString(),
        ];
    }

    /** @template T of \Illuminate\Database\Eloquent\Model @param \Illuminate\Contracts\Pagination\LengthAwarePaginator<int,T> $phanTrang */
    private function phanTrang($phanTrang, callable $chuyenDoi): array
    {
        return [
            'items' => collect($phanTrang->items())->map($chuyenDoi)->values()->all(),
            'pagination' => [
                'current_page' => $phanTrang->currentPage(),
                'per_page' => $phanTrang->perPage(),
                'total' => $phanTrang->total(),
                'last_page' => $phanTrang->lastPage(),
            ],
        ];
    }
}
