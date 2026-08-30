<?php

declare(strict_types=1);

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use App\Services\Workout\WorkoutPlanService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $userId, $memberId, $planId, $exerciseId, $start, $locked, $release] = $argv;
if (preg_match('/^smart_fitness_ai_apply_test_/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, 'Unsafe AI Apply plan-change database.'.PHP_EOL);
    exit(2);
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$wait = static function (string $file): void {
    $deadline = microtime(true) + 20;
    while (! is_file($file)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Barrier timeout: '.$file);
        }
        usleep(10000);
    }
};
$wait($start);

try {
    $result = DB::transaction(function () use ($app, $userId, $memberId, $planId, $exerciseId, $locked, $release, $wait) {
        HoSoHoiVien::query()->lockForUpdate()->findOrFail((int) $memberId);
        $ngay = CarbonImmutable::now('Asia/Ho_Chi_Minh')->addDay();
        $thu = $ngay->dayOfWeekIso === 7 ? 8 : $ngay->dayOfWeekIso + 1;
        $cauTruc = [
            'name' => 'Plan thay đổi đồng thời',
            'goal' => 'TANG_SUC_MANH',
            'effective_from' => $ngay->toDateString(),
            'days' => [[
                'logical_id' => (string) Str::uuid(),
                'order' => 1,
                'weekday' => $thu,
                'name' => 'Ngày thay đổi đồng thời',
                'estimated_minutes' => 60,
                'exercises' => [[
                    'exercise_id' => (int) $exerciseId,
                    'logical_id' => (string) Str::uuid(),
                    'order' => 1,
                    'target_sets' => 3,
                    'min_reps' => 8,
                    'max_reps' => 12,
                    'target_weight_kg' => null,
                    'rest_seconds' => 90,
                    'notes' => null,
                ]],
            ]],
        ];

        $phienBan = $app->make(WorkoutPlanService::class)->taoPhienBanTiepTheo(
            NguoiDung::query()->findOrFail((int) $userId),
            (int) $planId,
            $cauTruc,
        );
        touch($locked);
        $wait($release);

        return $phienBan;
    }, 3);
    echo json_encode(['status' => 'success', 'version_id' => $result->getKey()], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (WorkoutWorkflowException $exception) {
    echo json_encode(['status' => 'error', 'code' => $exception->safeCode], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
