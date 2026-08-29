<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiWorkflowException;
use App\Models\BaiTap;
use App\Models\GiaoAnMau;
use App\Models\HoSoHoiVien;
use App\Models\KeHoachTap;
use Illuminate\Support\Facades\DB;

class AiCandidateRuleEngine
{
    public const VERSION = 'workout-rule-v1';

    /**
     * Lập ngữ cảnh workout và candidate hoàn toàn từ dữ liệu canonical nội bộ.
     *
     * Hàm đọc profile, ngày rảnh, dụng cụ, metadata nhóm cơ và catalog đang hoạt
     * động. Một bài có nhiều quan hệ dụng cụ chỉ hợp lệ khi Member có toàn bộ
     * dụng cụ đó (AND); bài không có quan hệ dụng cụ vẫn hợp lệ. Kết quả được sắp
     * theo ID tăng dần để cùng input luôn tạo cùng candidate ordering.
     *
     * @param  array{loai_yeu_cau: string, prompt: string}  $yeuCau
     * @return array<string, mixed>
     */
    public function taoNguCanh(HoSoHoiVien $hoiVien, array $yeuCau): array
    {
        $soNgay = (int) ($hoiVien->so_ngay_tap_mong_muon ?? 0);
        $thoiLuong = (int) ($hoiVien->thoi_luong_moi_buoi_phut ?? 0);
        if ($hoiVien->muc_tieu_tap_luyen === null || $soNgay < 1 || $thoiLuong < 1) {
            throw new AiWorkflowException(
                'Hồ sơ cần mục tiêu, số ngày tập và thời lượng buổi trước khi dùng AI.',
                422,
                'AI_CONTEXT_INCOMPLETE',
            );
        }

        $ngayRanh = DB::table('ngay_ranh_hoi_vien')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderBy('thu_trong_tuan')
            ->pluck('thu_trong_tuan')
            ->map(fn ($muc): int => (int) $muc)
            ->values()
            ->all();
        if (count($ngayRanh) < $soNgay) {
            throw new AiWorkflowException(
                'Số ngày rảnh chưa đủ cho số ngày tập mong muốn.',
                422,
                'AI_AVAILABILITY_INCOMPLETE',
            );
        }

        $dungCu = DB::table('dung_cu_hoi_vien')
            ->join('dung_cu', 'dung_cu.id', '=', 'dung_cu_hoi_vien.dung_cu_id')
            ->where('dung_cu_hoi_vien.hoi_vien_id', $hoiVien->getKey())
            ->where('dung_cu.trang_thai', 'HOAT_DONG')
            ->orderBy('dung_cu.id')
            ->get(['dung_cu.id', 'dung_cu.ten_dung_cu'])
            ->map(fn (object $muc): array => ['id' => (int) $muc->id, 'ten' => (string) $muc->ten_dung_cu])
            ->all();
        $idDungCu = array_column($dungCu, 'id');

        $gioiHan = max(1, min(1000, (int) config('ai.candidate_limit', 200)));
        $baiTap = BaiTap::query()
            ->where('trang_thai', 'HOAT_DONG')
            ->with([
                'baiTapDungCus' => fn ($query) => $query->orderBy('dung_cu_id'),
                'baiTapNhomCos' => fn ($query) => $query->with('nhomCo')->orderBy('nhom_co_id'),
            ])
            ->orderBy('id')
            ->get()
            ->filter(function (BaiTap $baiTap) use ($idDungCu): bool {
                $batBuoc = $baiTap->baiTapDungCus->pluck('dung_cu_id')->map(fn ($id): int => (int) $id)->all();

                return array_diff($batBuoc, $idDungCu) === [];
            })
            ->take($gioiHan)
            ->map(function (BaiTap $baiTap): array {
                return [
                    'id' => (int) $baiTap->getKey(),
                    'ten' => (string) $baiTap->ten_bai_tap,
                    'do_kho' => (string) $baiTap->do_kho,
                    'phien_ban_noi_dung' => (int) $baiTap->phien_ban_noi_dung,
                    'dung_cu_bat_buoc' => $baiTap->baiTapDungCus
                        ->pluck('dung_cu_id')->map(fn ($id): int => (int) $id)->values()->all(),
                    'nhom_co' => $baiTap->baiTapNhomCos->map(fn ($muc): array => [
                        'id' => (int) $muc->nhom_co_id,
                        'vai_tro' => (string) $muc->vai_tro_nhom_co,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
        if ($baiTap === []) {
            throw new AiWorkflowException(
                'Không có bài tập phù hợp với dụng cụ hiện tại.',
                422,
                'AI_NO_CANDIDATES',
            );
        }

        $giaoAn = GiaoAnMau::query()
            ->where('trang_thai', 'HOAT_DONG')
            ->where('so_buoi_moi_tuan', $soNgay)
            ->orderBy('id')
            ->get()
            ->map(fn (GiaoAnMau $muc): array => [
                'id' => (int) $muc->getKey(),
                'ten' => (string) $muc->ten_giao_an,
                'muc_tieu' => (string) $muc->muc_tieu,
                'trinh_do' => (string) $muc->trinh_do,
                'so_buoi_moi_tuan' => (int) $muc->so_buoi_moi_tuan,
                'phien_ban_noi_dung' => (int) $muc->phien_ban_noi_dung,
            ])->all();

        $keHoach = KeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('trang_thai', 'DANG_SU_DUNG')
            ->orderBy('id')
            ->first();
        if (in_array($yeuCau['loai_yeu_cau'], ['DIEU_CHINH', 'THAY_BAI'], true)
            && ($keHoach === null || $keHoach->phien_ban_hien_tai_id === null)) {
            throw new AiWorkflowException(
                'Yêu cầu điều chỉnh cần một kế hoạch đang sử dụng có phiên bản hiện tại.',
                422,
                'AI_ACTIVE_PLAN_REQUIRED',
            );
        }

        return [
            'phien_ban_quy_tac' => self::VERSION,
            'yeu_cau' => $yeuCau,
            'ho_so' => [
                'phien_ban_ho_so' => (int) $hoiVien->phien_ban_ho_so,
                'moc_thay_doi_ke_hoach' => (int) $hoiVien->moc_thay_doi_ke_hoach,
                'muc_tieu_tap_luyen' => (string) $hoiVien->muc_tieu_tap_luyen,
                'kinh_nghiem_tap_luyen' => $hoiVien->kinh_nghiem_tap_luyen,
                'so_ngay_tap_mong_muon' => $soNgay,
                'thoi_luong_moi_buoi_phut' => $thoiLuong,
            ],
            'ngay_ranh' => $ngayRanh,
            'dung_cu' => $dungCu,
            'bai_tap_ung_vien' => $baiTap,
            'giao_an_ung_vien' => $giaoAn,
            'ke_hoach_hien_tai' => $keHoach === null ? null : [
                'id' => (int) $keHoach->getKey(),
                'phien_ban_hien_tai_id' => $keHoach->phien_ban_hien_tai_id === null
                    ? null
                    : (int) $keHoach->phien_ban_hien_tai_id,
            ],
        ];
    }
}
