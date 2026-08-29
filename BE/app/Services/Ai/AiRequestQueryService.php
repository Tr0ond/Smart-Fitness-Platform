<?php

namespace App\Services\Ai;

use App\Models\DeXuatKeHoachTap;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use App\Models\YeuCauTroLy;
use Carbon\CarbonImmutable;

class AiRequestQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function danhSach(NguoiDung $nguoiDung): array
    {
        $hoiVien = $this->layHoiVien($nguoiDung);

        return YeuCauTroLy::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->with('deXuatKeHoachTap')
            ->orderByDesc('ngay_tao')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (YeuCauTroLy $muc): array => $this->duLieuYeuCau($muc))
            ->all();
    }

    /** @return array<string, mixed> */
    public function chiTiet(NguoiDung $nguoiDung, int $yeuCauId): array
    {
        $hoiVien = $this->layHoiVien($nguoiDung);
        $yeuCau = YeuCauTroLy::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->whereKey($yeuCauId)
            ->with(['deXuatKeHoachTap', 'lanGoiMoHinhs'])
            ->firstOrFail();
        $duLieu = $this->duLieuYeuCau($yeuCau);
        $duLieu['model_calls'] = $yeuCau->lanGoiMoHinhs->map(fn ($lan): array => [
            'attempt' => (int) $lan->so_lan,
            'status' => (string) $lan->trang_thai,
            'error_code' => $lan->ma_loi,
            'started_at' => $lan->bat_dau_luc->toISOString(),
            'completed_at' => $lan->ket_thuc_luc?->toISOString(),
        ])->all();

        return $duLieu;
    }

    /** @return array<string, mixed> */
    public function deXuat(NguoiDung $nguoiDung, int $deXuatId): array
    {
        $hoiVien = $this->layHoiVien($nguoiDung);
        $deXuat = DeXuatKeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('nguon_de_xuat', 'TRO_LY')
            ->whereKey($deXuatId)
            ->firstOrFail();

        return $this->duLieuDeXuat($deXuat);
    }

    /** @return array<string, mixed> */
    public function duLieuYeuCau(YeuCauTroLy $yeuCau): array
    {
        $deXuat = $yeuCau->relationLoaded('deXuatKeHoachTap')
            ? $yeuCau->deXuatKeHoachTap
            : $yeuCau->deXuatKeHoachTap()->first();

        return [
            'id' => (int) $yeuCau->getKey(),
            'request_code' => (string) $yeuCau->ma_yeu_cau,
            'request_type' => (string) $yeuCau->loai_yeu_cau,
            'status' => (string) $yeuCau->trang_thai,
            'quota_status' => (string) $yeuCau->trang_thai_han_muc,
            'membership_term_id' => $yeuCau->ky_han_hoi_vien_id === null ? null : (int) $yeuCau->ky_han_hoi_vien_id,
            'error_code' => $yeuCau->ma_loi,
            'created_at' => $yeuCau->ngay_tao->toISOString(),
            'completed_at' => $yeuCau->hoan_tat_luc?->toISOString(),
            'proposal' => $deXuat instanceof DeXuatKeHoachTap ? $this->duLieuDeXuat($deXuat) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuDeXuat(DeXuatKeHoachTap $deXuat): array
    {
        return [
            'id' => (int) $deXuat->getKey(),
            'request_id' => $deXuat->yeu_cau_tro_ly_id === null ? null : (int) $deXuat->yeu_cau_tro_ly_id,
            'source' => (string) $deXuat->nguon_de_xuat,
            'change_type' => (string) $deXuat->loai_thay_doi,
            'title' => (string) $deXuat->tieu_de,
            'explanation' => (string) $deXuat->giai_thich,
            'content' => $deXuat->noi_dung_de_xuat,
            'schema_version' => (string) $deXuat->phien_ban_cau_truc,
            'content_hash' => (string) $deXuat->ma_bam_noi_dung,
            'effective_date' => $deXuat->ap_dung_tu_ngay->format('Y-m-d'),
            'status' => (string) $deXuat->trang_thai,
            'is_expired' => CarbonImmutable::now('UTC')->greaterThanOrEqualTo($deXuat->het_han_luc),
            'expires_at' => $deXuat->het_han_luc->toISOString(),
            'created_at' => $deXuat->ngay_tao->toISOString(),
        ];
    }

    private function layHoiVien(NguoiDung $nguoiDung): HoSoHoiVien
    {
        return HoSoHoiVien::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->firstOrFail();
    }
}
