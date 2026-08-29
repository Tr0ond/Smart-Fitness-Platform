<?php

namespace Tests\Fakes;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Data\Ai\WorkoutAiResult;
use App\Exceptions\Ai\AiProviderException;

class FakeWorkoutAiProvider implements WorkoutAiProvider
{
    public const SUCCESS = 'SUCCESS';

    public const TIMEOUT = 'TIMEOUT';

    public const RATE_LIMIT = 'RATE_LIMIT';

    public const SERVER_ERROR = 'SERVER_ERROR';

    public const MALFORMED_OUTPUT = 'MALFORMED_OUTPUT';

    public const UNKNOWN_EXERCISE = 'UNKNOWN_EXERCISE';

    public const INVALID_STRUCTURE = 'INVALID_STRUCTURE';

    public int $callCount = 0;

    /** @var array<string, mixed>|null */
    public ?array $lastContext = null;

    /** @var array<string, mixed>|null */
    private ?array $exactOutput = null;

    /** @var callable(array<string, mixed>): void|null */
    private $beforeReturn = null;

    public function __construct(public string $mode = self::SUCCESS) {}

    /** @param array<string, mixed> $output */
    public function withExactOutput(array $output): self
    {
        $this->exactOutput = $output;

        return $this;
    }

    /** @param callable(array<string, mixed>): void $callback */
    public function beforeReturn(callable $callback): self
    {
        $this->beforeReturn = $callback;

        return $this;
    }

    public function providerName(): string
    {
        return 'fake';
    }

    public function modelName(): string
    {
        return 'fake-workout-v1';
    }

    /** @param array<string, mixed> $context */
    public function generateStructuredProposal(array $context): WorkoutAiResult
    {
        $this->callCount++;
        $this->lastContext = $context;
        if ($this->beforeReturn !== null) {
            ($this->beforeReturn)($context);
        }

        match ($this->mode) {
            self::TIMEOUT => throw AiProviderException::timeout(),
            self::RATE_LIMIT => throw AiProviderException::rateLimited(),
            self::SERVER_ERROR => throw AiProviderException::serverError(),
            self::MALFORMED_OUTPUT => throw AiProviderException::malformedOutput(),
            default => null,
        };

        $output = $this->exactOutput ?? $this->taoOutputMacDinh($context);
        if ($this->mode === self::UNKNOWN_EXERCISE) {
            $output['ngay_trong_ke_hoach'][0]['bai_tap_trong_ke_hoach'][0]['bai_tap_id'] = 999999999;
        } elseif ($this->mode === self::INVALID_STRUCTURE) {
            $output = ['du_lieu' => 'khong_dung_schema'];
        }

        return new WorkoutAiResult($output, 'fake-request-'.$this->callCount, 120, 80);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function taoOutputMacDinh(array $context): array
    {
        $loai = match ($context['request']['loai_yeu_cau']) {
            'TAO_KE_HOACH' => 'TAO_MOI',
            'DIEU_CHINH' => 'DIEU_CHINH',
            'THAY_BAI' => 'THAY_BAI',
        };
        $soNgay = (int) $context['profile']['so_ngay_tap_mong_muon'];
        $ngayRanh = array_slice($context['available_days'], 0, $soNgay);
        $baiTap = $context['exercise_candidates'];

        return [
            'loai_thay_doi' => $loai,
            'tieu_de' => 'Kế hoạch tập được đề xuất',
            'giai_thich' => 'Kế hoạch dùng các bài đã được Backend xác minh.',
            'ap_dung_tu_ngay' => now('UTC')->addDay()->format('Y-m-d'),
            'ngay_trong_ke_hoach' => array_map(function ($ngay, int $index) use ($baiTap): array {
                $ungVien = $baiTap[$index % count($baiTap)];

                return [
                    'thu_trong_tuan' => (int) $ngay,
                    'bai_tap_trong_ke_hoach' => [[
                        'bai_tap_id' => (int) $ungVien['id'],
                        'thu_tu' => 1,
                        'so_hiep_muc_tieu' => 3,
                        'so_lan_lap_toi_thieu' => 8,
                        'so_lan_lap_toi_da' => 12,
                        'thoi_gian_nghi_giay' => 90,
                    ]],
                ];
            }, $ngayRanh, array_keys($ngayRanh)),
        ];
    }
}
