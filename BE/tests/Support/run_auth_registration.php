<?php

declare(strict_types=1);

use App\Exceptions\Auth\AuthWorkflowException;
use App\Services\Auth\RegistrationService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $email, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "Registration barrier timeout.\n");
        exit(3);
    }
    usleep(10000);
}

try {
    $user = $app->make(RegistrationService::class)->dangKy([
        'name' => 'Concurrent Registration',
        'email' => $email,
        'phone' => null,
        'password' => 'Concurrent!Password123',
    ]);
    $result = ['status' => 'success', 'user_id' => (int) $user->getKey()];
} catch (AuthWorkflowException $exception) {
    $result = ['status' => 'error', 'code' => $exception->safeCode, 'http_status' => $exception->responseStatus];
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
    exit(1);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
