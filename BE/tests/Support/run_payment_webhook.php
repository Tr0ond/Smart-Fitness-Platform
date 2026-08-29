<?php

declare(strict_types=1);

use App\Services\Payments\PayOSWebhookService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $payloadPath, $barrier] = $argv;
if (preg_match('/\Asmart_fitness_[a-z0-9_]*test(?:_[a-z0-9_]+)?\z/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, 'Unsafe payment concurrency database.'.PHP_EOL);
    exit(2);
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
putenv('PAYOS_CLIENT_ID=test-client-id');
putenv('PAYOS_API_KEY=test-api-key');
putenv('PAYOS_CHECKSUM_KEY=test-checksum-key-20260829');
putenv('PAYOS_RETURN_URL=https://frontend.test/payment/return');
putenv('PAYOS_CANCEL_URL=https://frontend.test/payment/cancel');
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$_ENV['PAYOS_CLIENT_ID'] = 'test-client-id';
$_ENV['PAYOS_API_KEY'] = 'test-api-key';
$_ENV['PAYOS_CHECKSUM_KEY'] = 'test-checksum-key-20260829';

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
