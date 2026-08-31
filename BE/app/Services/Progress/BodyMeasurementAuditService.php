<?php

namespace App\Services\Progress;

use App\Models\ChiSoCoThe;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use Carbon\CarbonImmutable;

class BodyMeasurementAuditService
{
    /** Audit không sao chép số đo hoặc ghi chú riêng tư. */
    public function ghiDaTao(NguoiDung $actor, ChiSoCoThe $chiSo, CarbonImmutable $thoiDiem): void
    {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => 'TAO_CHI_SO_CO_THE',
            'loai_doi_tuong' => 'CHI_SO_CO_THE',
            'dinh_danh_doi_tuong' => $chiSo->getKey(),
            'khoa_tuong_quan' => $chiSo->ma_lan_ghi,
            'du_lieu_truoc' => null,
            'du_lieu_sau' => [
                'member_id' => (int) $chiSo->hoi_vien_id,
                'measured_at' => $chiSo->do_luc?->toISOString(),
            ],
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $thoiDiem,
            'ngay_tao' => $thoiDiem,
        ]);
    }
}
