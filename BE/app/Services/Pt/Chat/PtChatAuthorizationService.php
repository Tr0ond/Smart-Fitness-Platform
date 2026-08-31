<?php

namespace App\Services\Pt\Chat;

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Models\HoiThoai;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use Carbon\CarbonImmutable;

class PtChatAuthorizationService
{
    public const MEMBER = 'MEMBER';

    public const PT = 'PT';

    /**
     * Xác định actor từ Bearer principal; không tin bất kỳ participant ID nào từ client.
     */
    public function loaiNguoiThamGia(
        NguoiDung $nguoiDung,
        HoiThoai $hoiThoai,
        bool $yeuCauDuDieuKienGui = false,
    ): string {
        if ($nguoiDung->trang_thai !== 'HOAT_DONG') {
            throw $this->khongTimThay();
        }

        if ($this->coVaiTro($nguoiDung, self::MEMBER)) {
            $hoiVien = HoSoHoiVien::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->first();
            if ($hoiVien instanceof HoSoHoiVien
                && (int) $hoiVien->getKey() === (int) $hoiThoai->hoi_vien_id) {
                return self::MEMBER;
            }
        }

        if ($this->coVaiTro($nguoiDung, self::PT)) {
            $huanLuyenVien = HoSoHuanLuyenVien::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->first();
            if ($huanLuyenVien instanceof HoSoHuanLuyenVien
                && (int) $huanLuyenVien->getKey() === (int) $hoiThoai->huan_luyen_vien_id
                && $this->phanCongCuaHoiThoaiDangHieuLuc($hoiThoai)
                && (! $yeuCauDuDieuKienGui || $huanLuyenVien->trang_thai === 'HOAT_DONG')) {
                return self::PT;
            }
        }

        throw $this->khongTimThay();
    }

    /** Member được đọc lịch sử; PT chỉ subscribe khi exact assignment còn hiệu lực. */
    public function laNguoiThamGia(NguoiDung $nguoiDung, int $hoiThoaiId): bool
    {
        $hoiThoai = HoiThoai::query()->find($hoiThoaiId);
        if (! $hoiThoai instanceof HoiThoai) {
            return false;
        }

        try {
            $this->loaiNguoiThamGia($nguoiDung, $hoiThoai);

            return true;
        } catch (PtChatWorkflowException) {
            return false;
        }
    }

    public function coVaiTro(NguoiDung $nguoiDung, string $maVaiTro): bool
    {
        return $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->where('ma_vai_tro', $maVaiTro))
            ->exists();
    }

    private function phanCongCuaHoiThoaiDangHieuLuc(HoiThoai $hoiThoai): bool
    {
        $phanCong = $hoiThoai->phanCongHuanLuyenVien()->first();
        if (! $phanCong instanceof PhanCongHuanLuyenVien
            || (int) $phanCong->hoi_vien_id !== (int) $hoiThoai->hoi_vien_id
            || (int) $phanCong->huan_luyen_vien_id !== (int) $hoiThoai->huan_luyen_vien_id) {
            return false;
        }

        $hienTai = CarbonImmutable::now('UTC');

        return CarbonImmutable::instance($phanCong->ngay_bat_dau)->lessThanOrEqualTo($hienTai)
            && ($phanCong->ngay_ket_thuc === null
                || $hienTai->lessThan(CarbonImmutable::instance($phanCong->ngay_ket_thuc)));
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
