<?php

declare(strict_types=1);

use App\Exceptions\Pt\PtWorkflowException;
use App\Models\NguoiDung;
use App\Services\Pt\PtAssignmentService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $adminId, $memberId, $trainerId, $startAt, $barrier, $output] = $argv;
if (preg_match('/^smart_fitness_.*test/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, "Unsafe PT assignment concurrency database.\n");
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
        fwrite(STDERR, "Start barrier timeout.\n");
        exit(3);
    }
    usleep(10000);
}

try {
    $user = NguoiDung::query()->findOrFail((int) $adminId);
    $result = $app->make(PtAssignmentService::class)->tao($user, [
        'member_id' => (int) $memberId,
        'trainer_id' => (int) $trainerId,
        'start_at' => $startAt,
    ]);
    $line = ['status' => 'success', 'assignment_id' => $result['id']];
} catch (PtWorkflowException $exception) {
    $line = ['status' => 'error', 'code' => $exception->safeCode, 'http_status' => $exception->responseStatus];
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
    exit(1);
}

file_put_contents($output, json_encode($line, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
