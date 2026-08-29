<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiWorkflowException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class AiStructuredOutputValidator
{
    /**
     * Kiểm tra schema và toàn bộ giới hạn nghiệp vụ của output provider.
     *
     * Output chỉ được dùng ID thuộc candidate snapshot, đúng ngày rảnh, đúng số
     * ngày, sets/reps/rest trong giới hạn và không có bài hoặc thứ tự trùng trong
     * một ngày. Hàm trả payload canonical để lưu Proposal; không ghi Database.
     *
     * @param  array<string, mixed>  $output
     * @param  array<string, mixed>  $nguCanh
     * @return array<string, mixed>
     */
    public function kiemTra(array $output, array $nguCanh): array
    {
        $validator = Validator::make($output, [
            'loai_thay_doi' => ['required', 'in:TAO_MOI,DIEU_CHINH,THAY_BAI'],
            'tieu_de' => ['required', 'string', 'max:200'],
            'giai_thich' => ['required', 'string', 'max:5000'],
            'ap_dung_tu_ngay' => ['required', 'date_format:Y-m-d'],
            'ngay_trong_ke_hoach' => ['required', 'array', 'min:1', 'max:7'],
            'ngay_trong_ke_hoach.*.thu_trong_tuan' => ['required', 'integer', 'between:2,8'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach' => ['required', 'array', 'min:1', 'max:12'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach.*.bai_tap_id' => ['required', 'integer', 'min:1'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach.*.thu_tu' => ['required', 'integer', 'min:1', 'max:12'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach.*.so_hiep_muc_tieu' => ['required', 'integer', 'between:1,10'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach.*.so_lan_lap_toi_thieu' => ['required', 'integer', 'between:1,100'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach.*.so_lan_lap_toi_da' => ['required', 'integer', 'between:1,100'],
            'ngay_trong_ke_hoach.*.bai_tap_trong_ke_hoach.*.thoi_gian_nghi_giay' => ['required', 'integer', 'between:0,600'],
        ]);
        if ($validator->fails()) {
            throw new AiWorkflowException(
                'Output AI không đúng schema bắt buộc.',
                502,
                'AI_INVALID_STRUCTURE',
                'LOI_CAU_TRUC',
                $output,
            );
        }

        $hopLe = $validator->validated();
        $loaiMongDoi = match ($nguCanh['yeu_cau']['loai_yeu_cau']) {
            'TAO_KE_HOACH' => 'TAO_MOI',
            'DIEU_CHINH' => 'DIEU_CHINH',
            'THAY_BAI' => 'THAY_BAI',
        };
        if ($hopLe['loai_thay_doi'] !== $loaiMongDoi) {
            throw $this->loiNghiepVu('AI_CHANGE_TYPE_MISMATCH', $output);
        }

        $ngayRanh = array_map('intval', $nguCanh['ngay_ranh']);
        $soNgay = (int) $nguCanh['ho_so']['so_ngay_tap_mong_muon'];
        $cacNgay = array_map(
            fn (array $ngay): int => (int) $ngay['thu_trong_tuan'],
            $hopLe['ngay_trong_ke_hoach'],
        );
        if (count($cacNgay) !== $soNgay
            || count(array_unique($cacNgay)) !== count($cacNgay)
            || array_diff($cacNgay, $ngayRanh) !== []) {
            throw $this->loiNghiepVu('AI_INVALID_TRAINING_DAYS', $output);
        }

        $idUngVien = array_map(
            fn (array $baiTap): int => (int) $baiTap['id'],
            $nguCanh['bai_tap_ung_vien'],
        );
        foreach ($hopLe['ngay_trong_ke_hoach'] as &$ngay) {
            $idTrongNgay = [];
            $thuTuTrongNgay = [];
            foreach ($ngay['bai_tap_trong_ke_hoach'] as &$baiTap) {
                $id = (int) $baiTap['bai_tap_id'];
                $thuTu = (int) $baiTap['thu_tu'];
                if (! in_array($id, $idUngVien, true)) {
                    throw $this->loiNghiepVu('AI_UNKNOWN_EXERCISE_ID', $output);
                }
                if (in_array($id, $idTrongNgay, true) || in_array($thuTu, $thuTuTrongNgay, true)) {
                    throw $this->loiNghiepVu('AI_DUPLICATE_EXERCISE_OR_ORDER', $output);
                }
                if ((int) $baiTap['so_lan_lap_toi_thieu'] > (int) $baiTap['so_lan_lap_toi_da']) {
                    throw $this->loiNghiepVu('AI_INVALID_REP_RANGE', $output);
                }
                $idTrongNgay[] = $id;
                $thuTuTrongNgay[] = $thuTu;
                foreach (['bai_tap_id', 'thu_tu', 'so_hiep_muc_tieu', 'so_lan_lap_toi_thieu', 'so_lan_lap_toi_da', 'thoi_gian_nghi_giay'] as $cot) {
                    $baiTap[$cot] = (int) $baiTap[$cot];
                }
            }
            unset($baiTap);
            usort(
                $ngay['bai_tap_trong_ke_hoach'],
                fn (array $a, array $b): int => $a['thu_tu'] <=> $b['thu_tu'],
            );
            $ngay['thu_trong_tuan'] = (int) $ngay['thu_trong_tuan'];
        }
        unset($ngay);
        usort(
            $hopLe['ngay_trong_ke_hoach'],
            fn (array $a, array $b): int => $a['thu_trong_tuan'] <=> $b['thu_trong_tuan'],
        );

        $apDung = CarbonImmutable::createFromFormat('!Y-m-d', (string) $hopLe['ap_dung_tu_ngay'], 'UTC');
        if ($apDung === false || $apDung->lessThan(CarbonImmutable::now('UTC')->startOfDay())) {
            throw $this->loiNghiepVu('AI_EFFECTIVE_DATE_IN_PAST', $output);
        }

        return $hopLe;
    }

    /** @param array<string, mixed> $output */
    private function loiNghiepVu(string $maLoi, array $output): AiWorkflowException
    {
        return new AiWorkflowException(
            'Output AI vi phạm ràng buộc nghiệp vụ.',
            502,
            $maLoi,
            'LOI_NGHIEP_VU',
            $output,
        );
    }
}
