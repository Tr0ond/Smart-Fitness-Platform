<?php

declare(strict_types=1);

use App\Exceptions\Ai\AiWorkflowException;
use App\Models\NguoiDung;
use App\Services\Ai\AiProposalApplyService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $userId, $proposalId, $idempotencyKey, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Start barrier timeout.'.PHP_EOL);
        exit(3);
    }
    usleep(10000);
}

try {
    $result = $app->make(AiProposalApplyService::class)->apDung(
        NguoiDung::query()->findOrFail((int) $userId),
        (int) $proposalId,
        $idempotencyKey,
    );
    echo json_encode([
        'status' => 'success',
        'plan_id' => $result['plan']['id'],
        'version_id' => $result['plan']['current_version']['id'],
        'replayed' => $result['replayed'],
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
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
