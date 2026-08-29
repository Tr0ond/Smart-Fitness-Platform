<?php

declare(strict_types=1);

use App\Contracts\Ai\WorkoutAiProvider;
use App\Exceptions\Ai\AiWorkflowException;
use App\Models\NguoiDung;
use App\Services\Ai\AiRequestService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Fakes\FakeWorkoutAiProvider;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $userId, $idempotencyKey, $startBarrier, $providerEntered, $providerRelease, $providerCalls] = $argv;
if (preg_match('/^smart_fitness_.*test/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, 'Unsafe AI concurrency database.'.PHP_EOL);
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

$deadline = microtime(true) + 20;
while (! is_file($startBarrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Start barrier timeout.'.PHP_EOL);
        exit(3);
    }
    usleep(10000);
}

$fake = (new FakeWorkoutAiProvider)->beforeReturn(function () use ($providerEntered, $providerRelease, $providerCalls): void {
    file_put_contents($providerCalls, getmypid().PHP_EOL, FILE_APPEND | LOCK_EX);
    touch($providerEntered);
    $deadline = microtime(true) + 20;
    while (! is_file($providerRelease)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Provider release barrier timeout.');
        }
        usleep(10000);
    }
});
$app->instance(WorkoutAiProvider::class, $fake);

try {
    $user = NguoiDung::query()->findOrFail((int) $userId);
    $result = $app->make(AiRequestService::class)->tao($user, [
        'request_type' => 'TAO_KE_HOACH',
        'prompt' => 'Tạo kế hoạch tập ba buổi mỗi tuần.',
    ], $idempotencyKey);
    echo json_encode(['status' => 'success', 'request_id' => $result['id']], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (AiWorkflowException $exception) {
    echo json_encode([
        'status' => 'error',
        'code' => $exception->safeCode,
        'http_status' => $exception->responseStatus,
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
