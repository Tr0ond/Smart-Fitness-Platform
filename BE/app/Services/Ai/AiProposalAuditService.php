<?php

namespace App\Services\Ai;

use App\Models\DeXuatKeHoachTap;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use Carbon\CarbonImmutable;

class AiProposalAuditService
{
    /** Ghi audit đã lọc trong cùng transaction Apply; không lưu prompt hoặc payload AI. */
    public function ghiDaApDung(
        NguoiDung $nguoiDung,
        DeXuatKeHoachTap $deXuat,
        int $keHoachId,
        int $phienBanId,
        string $khoaTuongQuan,
        CarbonImmutable $thoiDiem,
    ): void {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $nguoiDung->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => 'AP_DUNG_DE_XUAT_TRO_LY',
            'loai_doi_tuong' => 'DE_XUAT_KE_HOACH_TAP',
            'dinh_danh_doi_tuong' => $deXuat->getKey(),
            'khoa_tuong_quan' => $khoaTuongQuan,
            'du_lieu_truoc' => [
                'proposal_status' => 'CHO_XAC_NHAN',
                'base_plan_id' => $deXuat->ke_hoach_tap_id,
                'base_version_id' => $deXuat->phien_ban_co_so_id,
            ],
            'du_lieu_sau' => [
                'proposal_status' => 'DA_AP_DUNG',
                'plan_id' => $keHoachId,
                'version_id' => $phienBanId,
            ],
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $thoiDiem,
            'ngay_tao' => $thoiDiem,
        ]);
    }
}
