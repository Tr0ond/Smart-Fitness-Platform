<?php

declare(strict_types=1);

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\NgayTrongKeHoach;
use App\Models\NguoiDung;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $mode, $userId, $targetId, $argA, $argB, $barrier, $output] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        exit(3);
    } usleep(10000);
}

try {
    $user = NguoiDung::query()->findOrFail((int) $userId);
    if ($mode === 'activate') {
        $result = $app->make(WorkoutPlanService::class)->kichHoat($user, (int) $targetId);
        $line = ['status' => 'success', 'id' => $result->getKey()];
    } elseif ($mode === 'schedule') {
        $dayId = NgayTrongKeHoach::query()->where('phien_ban_ke_hoach_tap_id', (int) $argB)->orderBy('id')->value('id');
        $result = $app->make(WorkoutScheduleService::class)->taoMotBuoi((int) $targetId, (int) $argA, (int) $argB, (int) $dayId, date('Y-m-d', strtotime('+1 day')));
        $line = ['status' => 'success', 'id' => $result->getKey()];
    } elseif ($mode === 'start') {
        $result = $app->make(WorkoutSessionService::class)->batDau($user, (int) $targetId, $argA);
        $line = ['status' => 'success', 'id' => $result['id'], 'replayed' => $result['replayed']];
    } elseif ($mode === 'complete') {
        $result = $app->make(WorkoutSessionService::class)->hoanThanh($user, (int) $targetId, $argA);
        $line = ['status' => 'success', 'id' => $result['id'], 'replayed' => $result['replayed']];
    } else {
        throw new RuntimeException('Unknown mode');
    }
} catch (WorkoutWorkflowException $exception) {
    $line = ['status' => 'error', 'code' => $exception->safeCode, 'http_status' => $exception->responseStatus];
} catch (Throwable $exception) {
    $line = ['status' => 'fatal', 'type' => get_class($exception), 'message' => $exception->getMessage()];
}
file_put_contents($output, json_encode($line, JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);
