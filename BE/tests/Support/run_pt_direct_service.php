<?php

declare(strict_types=1);

use App\Exceptions\Pt\PtWorkflowException;
use App\Models\NguoiDung;
use App\Services\Pt\PtDirectService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $ptUserId, $assignmentId, $idempotencyKey, $barrier, $output] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "Start barrier timeout.\n");
        exit(3);
    }
    usleep(10000);
}

try {
    $user = NguoiDung::query()->findOrFail((int) $ptUserId);
    $result = $app->make(PtDirectService::class)->hoanTat($user, [
        'assignment_id' => (int) $assignmentId,
    ], $idempotencyKey);
    $line = ['status' => 'success', 'history_id' => $result['history_id'], 'replayed' => $result['replayed']];
} catch (PtWorkflowException $exception) {
    $line = ['status' => 'error', 'code' => $exception->safeCode, 'http_status' => $exception->responseStatus];
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
    exit(1);
}

file_put_contents($output, json_encode($line, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
