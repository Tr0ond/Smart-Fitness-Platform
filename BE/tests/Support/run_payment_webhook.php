<?php

declare(strict_types=1);

use App\Services\Payments\PayOSWebhookService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $payloadPath, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database, [
    'PAYOS_CLIENT_ID' => 'test-client-id',
    'PAYOS_API_KEY' => 'test-api-key',
    'PAYOS_CHECKSUM_KEY' => 'test-checksum-key-20260829',
    'PAYOS_RETURN_URL' => 'https://frontend.test/payment/return',
    'PAYOS_CANCEL_URL' => 'https://frontend.test/payment/cancel',
]);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$payload = json_decode((string) file_get_contents($payloadPath), true, 512, JSON_THROW_ON_ERROR);

$deadline = microtime(true) + 15;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Barrier timeout.'.PHP_EOL);
        exit(3);
    }
    usleep(10000);
}

try {
    $result = $app->make(PayOSWebhookService::class)->xuLy($payload);
    echo json_encode($result, JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
