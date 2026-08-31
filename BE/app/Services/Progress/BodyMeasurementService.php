<?php

namespace App\Services\Progress;

use App\Exceptions\Progress\ProgressWorkflowException;
use App\Models\ChiSoCoThe;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BodyMeasurementService
{
    public function __construct(private readonly BodyMeasurementAuditService $audit) {}

    /** Append-only và idempotent theo ma_lan_ghi; không đụng Membership/Profile marker. */
    public function tao(NguoiDung $actor, HoSoHoiVien $hoiVien, array $duLieu): array
    {
        $payload = $this->chuanHoa($duLieu);

        return DB::transaction(function () use ($actor, $hoiVien, $payload): array {
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($hoiVien->getKey());
            $daCo = ChiSoCoThe::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->where('ma_lan_ghi', $payload['ma_lan_ghi'])
                ->lockForUpdate()
                ->first();
            if ($daCo instanceof ChiSoCoThe) {
                if (! $this->cungPayload($daCo, $payload)) {
                    throw new ProgressWorkflowException('Mã lần ghi đã được dùng cho nội dung khác.', 409, 'BODY_MEASUREMENT_IDEMPOTENCY_CONFLICT');
                }

                return array_merge($this->duLieu($daCo), ['replayed' => true]);
            }

            $hienTai = CarbonImmutable::now('UTC');
            $chiSo = ChiSoCoThe::query()->create(array_merge($payload, [
                'hoi_vien_id' => $hoiVien->getKey(),
                'ngay_tao' => $hienTai,
            ]));
            $this->audit->ghiDaTao($actor, $chiSo, $hienTai);

            return array_merge($this->duLieu($chiSo), ['replayed' => false]);
        }, 3);
    }

    public function danhSach(
        HoSoHoiVien $hoiVien,
        int $limit = 20,
        ?string $truocDoLuc = null,
        ?int $truocId = null,
    ): array {
        $limit = max(1, min(100, $limit));
        $query = ChiSoCoThe::query()->where('hoi_vien_id', $hoiVien->getKey());
        if ($truocDoLuc !== null && $truocId !== null) {
            $mocDo = CarbonImmutable::parse($truocDoLuc)->utc();
            $query->where(function ($cursor) use ($mocDo, $truocId): void {
                $cursor->where('do_luc', '<', $mocDo)
                    ->orWhere(function ($cungThoiDiem) use ($mocDo, $truocId): void {
                        $cungThoiDiem->where('do_luc', '=', $mocDo)->where('id', '<', $truocId);
                    });
            });
        }
        $cacChiSo = $query->orderByDesc('do_luc')->orderByDesc('id')->limit($limit)->get();
        $cuoi = $cacChiSo->last();

        return [
            'items' => $cacChiSo->map(fn (ChiSoCoThe $chiSo): array => $this->duLieu($chiSo))->values()->all(),
            'next_cursor' => $cacChiSo->count() === $limit && $cuoi instanceof ChiSoCoThe ? [
                'before_measured_at' => $cuoi->do_luc?->toISOString(),
                'before_id' => (int) $cuoi->getKey(),
            ] : null,
        ];
    }

    public function moiNhat(HoSoHoiVien $hoiVien): ?array
    {
        $chiSo = ChiSoCoThe::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderByDesc('do_luc')->orderByDesc('id')
            ->first();

        return $chiSo instanceof ChiSoCoThe ? $this->duLieu($chiSo) : null;
    }

    public function duLieu(ChiSoCoThe $chiSo): array
    {
        $chieuCaoMet = (float) $chiSo->chieu_cao_cm / 100;
        $bmi = $chieuCaoMet > 0
            ? number_format((float) $chiSo->can_nang_kg / ($chieuCaoMet ** 2), 2, '.', '')
            : null;

        return [
            'id' => (int) $chiSo->getKey(),
            'measured_at' => $chiSo->do_luc?->toISOString(),
            'weight_kg' => $chiSo->can_nang_kg,
            'height_cm' => $chiSo->chieu_cao_cm,
            'waist_cm' => $chiSo->vong_eo_cm,
            'notes' => $chiSo->ghi_chu,
            'entry_id' => $chiSo->ma_lan_ghi,
            'bmi' => $bmi,
            'bmi_status' => $bmi === null ? 'unavailable' : 'available',
            'created_at' => $chiSo->ngay_tao?->toISOString(),
        ];
    }

    private function chuanHoa(array $duLieu): array
    {
        return [
            'do_luc' => CarbonImmutable::parse((string) $duLieu['measured_at'])->utc(),
            'can_nang_kg' => $this->soThapPhan($duLieu['weight_kg']),
            'chieu_cao_cm' => $this->soThapPhan($duLieu['height_cm']),
            'vong_eo_cm' => array_key_exists('waist_cm', $duLieu) && $duLieu['waist_cm'] !== null
                ? $this->soThapPhan($duLieu['waist_cm'])
                : null,
            'ghi_chu' => $duLieu['notes'] ?? null,
            'ma_lan_ghi' => strtolower((string) $duLieu['entry_id']),
        ];
    }

    private function cungPayload(ChiSoCoThe $chiSo, array $payload): bool
    {
        return $chiSo->do_luc?->format('Y-m-d H:i:s.u') === $payload['do_luc']->format('Y-m-d H:i:s.u')
            && $chiSo->can_nang_kg === $payload['can_nang_kg']
            && $chiSo->chieu_cao_cm === $payload['chieu_cao_cm']
            && $chiSo->vong_eo_cm === $payload['vong_eo_cm']
            && $chiSo->ghi_chu === $payload['ghi_chu'];
    }

    private function soThapPhan(mixed $giaTri): string
    {
        return number_format((float) $giaTri, 2, '.', '');
    }
}
