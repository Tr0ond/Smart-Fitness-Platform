<?php

declare(strict_types=1);

use App\Exceptions\Gym\GymWorkflowException;
use App\Models\NguoiDung;
use App\Services\Gym\GymCheckInService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $staffId, $secretFile, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$deadline = microtime(true) + 15;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Barrier timeout.'.PHP_EOL);
        exit(3);
    }
    usleep(10000);
}

try {
    $token = trim((string) file_get_contents($secretFile));
    $staff = NguoiDung::query()->findOrFail((int) $staffId);
    $result = $app->make(GymCheckInService::class)->xacNhan($staff, $token);
    echo json_encode(['status' => 'success', 'result' => $result], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (GymWorkflowException $exception) {
    echo json_encode([
        'status' => 'error',
        'code' => $exception->safeCode,
        'http_status' => $exception->responseStatus,
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
