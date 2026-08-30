<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiWorkflowException;
use App\Models\BaiTap;
use App\Models\BaiTapUngVien;
use App\Models\DeXuatKeHoachTap;
use App\Models\HoSoHoiVien;
use App\Models\YeuCauTroLy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiProposalApplyValidator
{
    public function __construct(private readonly AiStructuredOutputValidator $outputValidator) {}

    /**
     * Revalidate Proposal bất biến và ánh xạ payload canonical sang cấu trúc Workout nội bộ.
     *
     * @return array<string, mixed>
     */
    public function kiemTraVaAnhXa(
        DeXuatKeHoachTap $deXuat,
        YeuCauTroLy $yeuCau,
        HoSoHoiVien $hoiVien,
    ): array {
        if ($deXuat->nguon_de_xuat !== 'TRO_LY'
            || $deXuat->yeu_cau_tro_ly_id === null
            || (int) $deXuat->yeu_cau_tro_ly_id !== (int) $yeuCau->getKey()
            || (int) $yeuCau->hoi_vien_id !== (int) $hoiVien->getKey()
            || $yeuCau->trang_thai !== 'THANH_CONG'
            || $yeuCau->trang_thai_han_muc !== 'DA_TINH') {
            throw $this->xungDot('Proposal không có AI Request nguồn hợp lệ.', 'AI_PROPOSAL_SOURCE_CONFLICT');
        }
        if ($deXuat->phien_ban_cau_truc !== 'workout-proposal-v1') {
            throw $this->xungDot('Phiên bản cấu trúc Proposal chưa được hỗ trợ.', 'AI_PROPOSAL_SCHEMA_UNSUPPORTED');
        }
        if ((int) $deXuat->phien_ban_ho_so_co_so !== (int) $hoiVien->phien_ban_ho_so
            || (int) $deXuat->moc_thay_doi_ke_hoach_co_so !== (int) $hoiVien->moc_thay_doi_ke_hoach) {
            throw $this->xungDot('Hồ sơ hoặc mốc kế hoạch đã thay đổi.', 'AI_PROPOSAL_CONTEXT_STALE');
        }

        $noiDung = $deXuat->noi_dung_de_xuat;
        $nguCanh = $yeuCau->ngu_canh_da_chot;
        if (! is_array($noiDung) || ! is_array($nguCanh)
            || ! hash_equals((string) $deXuat->ma_bam_noi_dung, hash('sha256', $this->jsonChuan($noiDung)))) {
            throw $this->xungDot('Nội dung Proposal không còn toàn vẹn.', 'AI_PROPOSAL_INTEGRITY_FAILED');
        }

        try {
            $noiDung = $this->outputValidator->kiemTra($noiDung, $nguCanh);
        } catch (AiWorkflowException) {
            throw $this->xungDot('Nội dung Proposal không còn hợp lệ.', 'AI_PROPOSAL_CONTENT_INVALID');
        }
        if ($noiDung['loai_thay_doi'] !== $deXuat->loai_thay_doi
            || $noiDung['ap_dung_tu_ngay'] !== $deXuat->ap_dung_tu_ngay?->toDateString()) {
            throw $this->xungDot('Metadata và nội dung Proposal không nhất quán.', 'AI_PROPOSAL_INTEGRITY_FAILED');
        }

        $dungCuHienTai = $this->damBaoNgayRanhVaDungCu($hoiVien, $nguCanh);
        $this->damBaoCandidate($yeuCau, $noiDung, $dungCuHienTai);

        return $this->anhXaWorkout($hoiVien, $deXuat, $noiDung);
    }

    /** @param array<string, mixed> $nguCanh */
    private function damBaoNgayRanhVaDungCu(HoSoHoiVien $hoiVien, array $nguCanh): array
    {
        $ngayHienTai = DB::table('ngay_ranh_hoi_vien')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderBy('thu_trong_tuan')
            ->lockForUpdate()
            ->pluck('thu_trong_tuan')->map(fn ($ngay): int => (int) $ngay)->all();
        $ngayDaChot = array_map('intval', $nguCanh['ngay_ranh'] ?? []);
        if ($ngayHienTai !== $ngayDaChot) {
            throw $this->xungDot('Ngày rảnh đã thay đổi sau khi tạo Proposal.', 'AI_AVAILABILITY_STALE');
        }

        $dungCuDaGan = DB::table('dung_cu_hoi_vien')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderBy('dung_cu_id')->lockForUpdate()
            ->pluck('dung_cu_id')->map(fn ($id): int => (int) $id)->all();
        $dungCuHienTai = DB::table('dung_cu')
            ->whereIn('id', $dungCuDaGan)
            ->orderBy('id')->lockForUpdate()
            ->get(['id', 'trang_thai'])
            ->filter(fn (object $muc): bool => $muc->trang_thai === 'HOAT_DONG')
            ->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $dungCuDaChot = array_map(
            fn (array $muc): int => (int) $muc['id'],
            is_array($nguCanh['dung_cu'] ?? null) ? $nguCanh['dung_cu'] : [],
        );
        if ($dungCuHienTai !== $dungCuDaChot) {
            throw $this->xungDot('Dụng cụ đã thay đổi sau khi tạo Proposal.', 'AI_EQUIPMENT_STALE');
        }

        return $dungCuHienTai;
    }

    /** @param array<string, mixed> $noiDung @param array<int, int> $dungCuHoiVien */
    private function damBaoCandidate(YeuCauTroLy $yeuCau, array $noiDung, array $dungCuHoiVien): void
    {
        $ids = collect($noiDung['ngay_trong_ke_hoach'])
            ->flatMap(fn (array $ngay) => collect($ngay['bai_tap_trong_ke_hoach'])->pluck('bai_tap_id'))
            ->map(fn ($id): int => (int) $id)->unique()->sort()->values();
        $ungVien = BaiTapUngVien::query()
            ->where('yeu_cau_tro_ly_id', $yeuCau->getKey())
            ->whereIn('bai_tap_id', $ids)
            ->orderBy('bai_tap_id')
            ->lockForUpdate()
            ->get()->keyBy('bai_tap_id');
        $baiTaps = BaiTap::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($ids as $id) {
            $mucUngVien = $ungVien->get($id);
            $baiTap = $baiTaps->get($id);
            $dungCuBatBuoc = DB::table('bai_tap_dung_cu')
                ->where('bai_tap_id', $id)->orderBy('dung_cu_id')->lockForUpdate()
                ->pluck('dung_cu_id')->map(fn ($muc): int => (int) $muc)->all();
            if (! $mucUngVien instanceof BaiTapUngVien
                || ! $baiTap instanceof BaiTap
                || $baiTap->trang_thai !== 'HOAT_DONG'
                || (int) $baiTap->phien_ban_noi_dung !== (int) $mucUngVien->phien_ban_noi_dung
                || (int) ($mucUngVien->du_lieu_da_chot['id'] ?? 0) !== $id
                || array_diff($dungCuBatBuoc, $dungCuHoiVien) !== []) {
                throw $this->xungDot('Candidate không còn hợp lệ tại thời điểm Apply.', 'AI_CANDIDATE_STALE');
            }
        }
    }

    /** @param array<string, mixed> $noiDung @return array<string, mixed> */
    private function anhXaWorkout(HoSoHoiVien $hoiVien, DeXuatKeHoachTap $deXuat, array $noiDung): array
    {
        $thoiLuong = (int) $hoiVien->thoi_luong_moi_buoi_phut;

        return [
            'name' => trim((string) $deXuat->tieu_de),
            'goal' => trim((string) $hoiVien->muc_tieu_tap_luyen),
            'effective_from' => $noiDung['ap_dung_tu_ngay'],
            'days' => array_map(function (array $ngay, int $chiSo) use ($thoiLuong): array {
                return [
                    'logical_id' => (string) Str::uuid(),
                    'order' => $chiSo + 1,
                    'weekday' => (int) $ngay['thu_trong_tuan'],
                    'name' => 'Buổi tập thứ '.(int) $ngay['thu_trong_tuan'],
                    'estimated_minutes' => $thoiLuong,
                    'exercises' => array_map(fn (array $baiTap): array => [
                        'exercise_id' => (int) $baiTap['bai_tap_id'],
                        'logical_id' => (string) Str::uuid(),
                        'order' => (int) $baiTap['thu_tu'],
                        'target_sets' => (int) $baiTap['so_hiep_muc_tieu'],
                        'min_reps' => (int) $baiTap['so_lan_lap_toi_thieu'],
                        'max_reps' => (int) $baiTap['so_lan_lap_toi_da'],
                        'target_weight_kg' => null,
                        'rest_seconds' => (int) $baiTap['thoi_gian_nghi_giay'],
                        'notes' => null,
                    ], $ngay['bai_tap_trong_ke_hoach']),
                ];
            }, $noiDung['ngay_trong_ke_hoach'], array_keys($noiDung['ngay_trong_ke_hoach'])),
        ];
    }

    /** @param array<string, mixed> $duLieu */
    private function jsonChuan(array $duLieu): string
    {
        return json_encode($this->sapXepMang($duLieu), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $duLieu @return array<string, mixed> */
    private function sapXepMang(array $duLieu): array
    {
        if (! array_is_list($duLieu)) {
            ksort($duLieu);
        }
        foreach ($duLieu as $khoa => $giaTri) {
            if (is_array($giaTri)) {
                $duLieu[$khoa] = $this->sapXepMang($giaTri);
            }
        }

        return $duLieu;
    }

    private function xungDot(string $message, string $code): AiWorkflowException
    {
        return new AiWorkflowException($message, 409, $code, 'LOI_NGHIEP_VU', null);
    }
}
