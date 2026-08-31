<?php

namespace App\Services\Progress;

use App\Exceptions\Progress\ProgressWorkflowException;
use App\Models\BaiTap;
use App\Models\BaiTapTrongPhien;
use App\Models\ChiSoCoThe;
use App\Models\HoSoHoiVien;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ProgressQueryService
{
    public function __construct(private readonly BodyMeasurementService $body) {}

    public function tongQuan(HoSoHoiVien $hoiVien, array $boLoc): array
    {
        [$tuNgay, $denNgay, $tuUtc, $denUtc, $muiGio] = $this->khoang($hoiVien, $boLoc);
        $sessions = DB::table('phien_tap')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('trang_thai', 'HOAN_THANH')
            ->where('ket_thuc_luc', '>=', $tuUtc)
            ->where('ket_thuc_luc', '<', $denUtc);
        $soBuoi = (int) (clone $sessions)->count();
        $offset = $tuNgay->format('P');
        $soNgayTap = (int) ((clone $sessions)
            ->selectRaw("COUNT(DISTINCT DATE(CONVERT_TZ(ket_thuc_luc, '+00:00', ?))) AS aggregate", [$offset])
            ->value('aggregate') ?? 0);
        $soNgay = $tuNgay->diffInDays($denNgay) + 1;

        $canNang = ChiSoCoThe::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('do_luc', '>=', $tuUtc)
            ->where('do_luc', '<', $denUtc)
            ->orderByDesc('do_luc')->orderByDesc('id')
            ->limit(100)->get()->reverse()->values()
            ->map(fn (ChiSoCoThe $chiSo): array => [
                'measurement_id' => (int) $chiSo->getKey(),
                'measured_at' => $chiSo->do_luc?->toISOString(),
                'weight_kg' => $chiSo->can_nang_kg,
            ])->all();

        return [
            'period' => [
                'from' => $tuNgay->toDateString(),
                'to' => $denNgay->toDateString(),
                'timezone' => $muiGio,
                'inclusive_days' => $soNgay,
            ],
            'completed_sessions_count' => $soBuoi,
            'training_frequency' => [
                'active_days_count' => $soNgayTap,
                'completed_sessions_per_7_days' => number_format($soBuoi * 7 / $soNgay, 2, '.', ''),
            ],
            'latest_body_measurement' => $this->body->moiNhat($hoiVien),
            'weight_trend' => $canNang,
        ];
    }

    public function baiTap(HoSoHoiVien $hoiVien, int $baiTapId, array $boLoc, int $limit = 50): array
    {
        $baiTap = BaiTap::query()->find($baiTapId);
        if (! $baiTap instanceof BaiTap) {
            throw new ProgressWorkflowException('Không tìm thấy bài tập.', 404, 'EXERCISE_NOT_FOUND');
        }
        [$tuNgay, $denNgay, $tuUtc, $denUtc, $muiGio] = $this->khoang($hoiVien, $boLoc);
        $limit = max(1, min(100, $limit));
        $lichSu = BaiTapTrongPhien::query()
            ->select('bai_tap_trong_phien.*')
            ->join('phien_tap', 'phien_tap.id', '=', 'bai_tap_trong_phien.phien_tap_id')
            ->with(['phienTap', 'hiepTaps' => fn ($query) => $query->orderBy('so_thu_tu')->orderBy('id')])
            ->where('bai_tap_trong_phien.bai_tap_id', $baiTapId)
            ->where('phien_tap.hoi_vien_id', $hoiVien->getKey())
            ->where('phien_tap.trang_thai', 'HOAN_THANH')
            ->where('phien_tap.ket_thuc_luc', '>=', $tuUtc)
            ->where('phien_tap.ket_thuc_luc', '<', $denUtc)
            ->orderByDesc('phien_tap.ket_thuc_luc')->orderByDesc('bai_tap_trong_phien.id')
            ->limit($limit)->get();

        return [
            'exercise' => [
                'id' => (int) $baiTap->getKey(),
                'code' => $baiTap->ma_bai_tap,
                'name' => $baiTap->ten_bai_tap,
            ],
            'period' => [
                'from' => $tuNgay->toDateString(),
                'to' => $denNgay->toDateString(),
                'timezone' => $muiGio,
            ],
            'items' => $lichSu->map(fn (BaiTapTrongPhien $muc): array => [
                'session_id' => (int) $muc->phien_tap_id,
                'session_name' => $muc->phienTap?->ten_buoi_tap,
                'completed_at' => $muc->phienTap?->ket_thuc_luc?->toISOString(),
                'exercise_name_snapshot' => $muc->ten_bai_tap,
                'sets_count' => $muc->hiepTaps->count(),
                'sets' => $muc->hiepTaps->map(fn ($hiep): array => [
                    'order' => (int) $hiep->so_thu_tu,
                    'reps' => (int) $hiep->so_lan_lap,
                    'weight_kg' => $hiep->khoi_luong_kg,
                    'completed_at' => $hiep->hoan_thanh_luc?->toISOString(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /** @return array{CarbonImmutable,CarbonImmutable,CarbonImmutable,CarbonImmutable,string} */
    private function khoang(HoSoHoiVien $hoiVien, array $boLoc): array
    {
        $muiGio = $hoiVien->nguoiDung?->chiNhanh?->mui_gio ?: 'Asia/Ho_Chi_Minh';
        $denNgay = isset($boLoc['to'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $boLoc['to'], $muiGio)
            : CarbonImmutable::now($muiGio)->startOfDay();
        $tuNgay = isset($boLoc['from'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $boLoc['from'], $muiGio)
            : $denNgay?->subDays(29);
        if ($tuNgay === false || $denNgay === false || $tuNgay === null || $denNgay === null) {
            throw new ProgressWorkflowException('Khoảng Progress không hợp lệ.', 422, 'INVALID_PROGRESS_PERIOD');
        }

        return [
            $tuNgay,
            $denNgay,
            $tuNgay->startOfDay()->utc(),
            $denNgay->addDay()->startOfDay()->utc(),
            $muiGio,
        ];
    }
}
