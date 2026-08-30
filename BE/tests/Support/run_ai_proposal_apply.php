<?php

declare(strict_types=1);

use App\Exceptions\Ai\AiWorkflowException;
use App\Models\NguoiDung;
use App\Services\Ai\AiProposalApplyService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $userId, $proposalId, $idempotencyKey, $barrier] = $argv;
if (preg_match('/^smart_fitness_ai_apply_test_/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, 'Unsafe AI Apply concurrency database.'.PHP_EOL);
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
