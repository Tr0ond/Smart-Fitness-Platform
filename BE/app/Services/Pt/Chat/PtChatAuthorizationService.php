<?php

namespace App\Services\Pt\Chat;

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Models\HoiThoai;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;

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
                && (! $yeuCauDuDieuKienGui || $huanLuyenVien->trang_thai === 'HOAT_DONG')) {
                return self::PT;
            }
        }

        throw $this->khongTimThay();
    }

    /** Channel auth chỉ kiểm tra participant lịch sử, không đụng Membership/activation. */
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

    private function khongTimThay(): PtChatWorkflowException
    {
        return new PtChatWorkflowException(
            'Không tìm thấy hội thoại trong phạm vi truy cập.',
            404,
            'CHAT_CONVERSATION_NOT_FOUND',
        );
    }
}
