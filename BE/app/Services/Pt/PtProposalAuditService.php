<?php

namespace App\Services\Pt;

use App\Models\DeXuatKeHoachTap;
use App\Models\GhiChuHuanLuyen;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class PtProposalAuditService
{
    /** Ghi một chuyển trạng thái Proposal PT bằng dữ liệu đã lọc trong transaction gọi. */
    public function ghiProposal(
        NguoiDung $actor,
        DeXuatKeHoachTap $deXuat,
        string $hanhDong,
        ?string $trangThaiTruoc,
        string $trangThaiSau,
        string $khoaTuongQuan,
        CarbonImmutable $thoiDiem,
        array $boSung = [],
    ): void {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => $hanhDong,
            'loai_doi_tuong' => 'DE_XUAT_KE_HOACH_TAP',
            'dinh_danh_doi_tuong' => $deXuat->getKey(),
            'khoa_tuong_quan' => $khoaTuongQuan,
            'du_lieu_truoc' => $trangThaiTruoc === null ? null : [
                'proposal_status' => $trangThaiTruoc,
                'base_plan_id' => $deXuat->ke_hoach_tap_id,
                'base_version_id' => $deXuat->phien_ban_co_so_id,
            ],
            'du_lieu_sau' => array_merge([
                'proposal_status' => $trangThaiSau,
                'assignment_id' => (int) $deXuat->phan_cong_huan_luyen_vien_id,
            ], $boSung),
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $thoiDiem,
            'ngay_tao' => $thoiDiem,
        ]);
    }

    /** Ghi dấu vết append-only cho ghi chú, không sao chép nội dung tư vấn vào audit. */
    public function ghiGhiChu(
        NguoiDung $actor,
        GhiChuHuanLuyen $ghiChu,
        CarbonImmutable $thoiDiem,
    ): void {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => 'TAO_GHI_CHU_HUAN_LUYEN',
            'loai_doi_tuong' => 'GHI_CHU_HUAN_LUYEN',
            'dinh_danh_doi_tuong' => $ghiChu->getKey(),
            'khoa_tuong_quan' => (string) Str::uuid(),
            'du_lieu_truoc' => null,
            'du_lieu_sau' => [
                'member_id' => (int) $ghiChu->hoi_vien_id,
                'assignment_id' => (int) $ghiChu->phan_cong_huan_luyen_vien_id,
                'plan_id' => $ghiChu->ke_hoach_tap_id === null ? null : (int) $ghiChu->ke_hoach_tap_id,
                'session_id' => $ghiChu->phien_tap_id === null ? null : (int) $ghiChu->phien_tap_id,
            ],
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $thoiDiem,
            'ngay_tao' => $thoiDiem,
        ]);
    }
}
