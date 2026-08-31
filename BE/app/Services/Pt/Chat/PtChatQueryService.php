<?php

namespace App\Services\Pt\Chat;

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Models\HoiThoai;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\TinNhan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class PtChatQueryService
{
    public function __construct(private readonly PtChatAuthorizationService $phanQuyen) {}

    /** @return array<int, array<string, mixed>> */
    public function danhSach(NguoiDung $nguoiDung): array
    {
        $hoiVienId = $this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::MEMBER)
            ? HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->value('id')
            : null;
        $huanLuyenVienId = $this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::PT)
            ? HoSoHuanLuyenVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->value('id')
            : null;

        if ($hoiVienId === null && $huanLuyenVienId === null) {
            return [];
        }

        $hienTai = CarbonImmutable::now('UTC');

        return HoiThoai::query()
            ->with(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung', 'phanCongHuanLuyenVien'])
            ->where(function (Builder $truyVan) use ($hoiVienId, $huanLuyenVienId, $hienTai): void {
                if ($hoiVienId !== null) {
                    $truyVan->where('hoi_vien_id', $hoiVienId);
                }
                if ($huanLuyenVienId !== null) {
                    $phuongThuc = $hoiVienId === null ? 'where' : 'orWhere';
                    $truyVan->{$phuongThuc}(function (Builder $truyVanPt) use ($huanLuyenVienId, $hienTai): void {
                        $truyVanPt
                            ->where('huan_luyen_vien_id', $huanLuyenVienId)
                            ->whereHas('phanCongHuanLuyenVien', function (Builder $phanCong) use ($hienTai): void {
                                $phanCong
                                    ->where('ngay_bat_dau', '<=', $hienTai)
                                    ->where(function (Builder $mocKetThuc) use ($hienTai): void {
                                        $mocKetThuc
                                            ->whereNull('ngay_ket_thuc')
                                            ->orWhere('ngay_ket_thuc', '>', $hienTai);
                                    });
                            });
                    });
                }
            })
            ->orderByDesc('ngay_cap_nhat')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (HoiThoai $hoiThoai): array => $this->duLieuHoiThoai($hoiThoai))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function chiTiet(NguoiDung $nguoiDung, int $hoiThoaiId): array
    {
        return $this->duLieuHoiThoai($this->timCuaNguoiDung($nguoiDung, $hoiThoaiId));
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int|bool|null>} */
    public function tinNhans(
        NguoiDung $nguoiDung,
        int $hoiThoaiId,
        ?int $truocSoThuTu = null,
        int $gioiHan = 50,
    ): array {
        $hoiThoai = $this->timCuaNguoiDung($nguoiDung, $hoiThoaiId);
        $truyVan = TinNhan::query()
            ->where('hoi_thoai_id', $hoiThoai->getKey())
            ->orderByDesc('so_thu_tu')
            ->orderByDesc('id');
        if ($truocSoThuTu !== null) {
            $truyVan->where('so_thu_tu', '<', $truocSoThuTu);
        }

        $cacTin = $truyVan->limit($gioiHan + 1)->get();
        $conTrangCu = $cacTin->count() > $gioiHan;
        $cacTin = $cacTin->take($gioiHan)->reverse()->values();

        return [
            'data' => $cacTin
                ->map(fn (TinNhan $tinNhan): array => $this->duLieuTinNhan($tinNhan, $hoiThoai))
                ->all(),
            'meta' => [
                'limit' => $gioiHan,
                'has_older' => $conTrangCu,
                'next_before_sequence' => $conTrangCu && $cacTin->isNotEmpty()
                    ? (int) $cacTin->min('so_thu_tu')
                    : null,
            ],
        ];
    }

    public function timCuaNguoiDung(NguoiDung $nguoiDung, int $hoiThoaiId): HoiThoai
    {
        $hoiThoai = HoiThoai::query()
            ->with(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung', 'phanCongHuanLuyenVien'])
            ->find($hoiThoaiId);
        if (! $hoiThoai instanceof HoiThoai) {
            throw $this->khongTimThay();
        }
        $this->phanQuyen->loaiNguoiThamGia($nguoiDung, $hoiThoai);

        return $hoiThoai;
    }

    /** @return array<string, mixed> */
    public function duLieuHoiThoai(HoiThoai $hoiThoai): array
    {
        $hoiThoai->loadMissing(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung', 'phanCongHuanLuyenVien']);
        $phanCong = $hoiThoai->phanCongHuanLuyenVien;
        $hienTai = CarbonImmutable::now('UTC');
        $dangHieuLuc = $phanCong !== null
            && CarbonImmutable::instance($phanCong->ngay_bat_dau)->lessThanOrEqualTo($hienTai)
            && ($phanCong->ngay_ket_thuc === null
                || $hienTai->lessThan(CarbonImmutable::instance($phanCong->ngay_ket_thuc)));

        return [
            'id' => (int) $hoiThoai->getKey(),
            'assignment_id' => (int) $hoiThoai->phan_cong_huan_luyen_vien_id,
            'member' => [
                'id' => (int) $hoiThoai->hoi_vien_id,
                'code' => $hoiThoai->hoiVien?->ma_hoi_vien,
                'name' => $hoiThoai->hoiVien?->nguoiDung?->ho_ten,
            ],
            'trainer' => [
                'id' => (int) $hoiThoai->huan_luyen_vien_id,
                'code' => $hoiThoai->huanLuyenVien?->ma_huan_luyen_vien,
                'name' => $hoiThoai->huanLuyenVien?->nguoiDung?->ho_ten,
            ],
            'last_sequence' => (int) $hoiThoai->so_thu_tu_cuoi,
            'current_assignment' => $dangHieuLuc,
            'created_at' => $hoiThoai->ngay_tao?->toISOString(),
            'updated_at' => $hoiThoai->ngay_cap_nhat?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    public function duLieuTinNhan(TinNhan $tinNhan, ?HoiThoai $hoiThoai = null): array
    {
        $hoiThoai ??= HoiThoai::query()
            ->with(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung'])
            ->findOrFail($tinNhan->hoi_thoai_id);
        $nguoiGuiId = (int) $tinNhan->nguoi_gui_id;
        $nguoiDungHoiVienId = (int) ($hoiThoai->hoiVien?->nguoi_dung_id ?? 0);

        return [
            'id' => (int) $tinNhan->getKey(),
            'conversation_id' => (int) $tinNhan->hoi_thoai_id,
            'sequence' => (int) $tinNhan->so_thu_tu,
            'client_message_id' => $tinNhan->ma_tin_nhan_phia_gui,
            'sender' => [
                'id' => $nguoiGuiId,
                'type' => $nguoiGuiId === $nguoiDungHoiVienId
                    ? PtChatAuthorizationService::MEMBER
                    : PtChatAuthorizationService::PT,
            ],
            'content' => $tinNhan->noi_dung,
            'sent_at' => $tinNhan->gui_luc?->toISOString(),
        ];
    }

    private function khongTimThay(): PtChatWorkflowException
    {
        return new PtChatWorkflowException(
            'Không tìm thấy hội thoại trong phạm vi truy cập.',
            404,
            'CHAT_CONVERSATION_NOT_FOUND',
        );
    }
}
