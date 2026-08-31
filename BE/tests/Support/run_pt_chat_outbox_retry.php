<?php

declare(strict_types=1);

use App\Events\PtChatMessageSent;
use App\Services\Pt\Chat\PtChatDeliveryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Events\Dispatcher;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $messageId, $startBarrier, $dispatchMarker] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database, ['BROADCAST_CONNECTION' => 'null']);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$dispatcher = $app->make(Dispatcher::class);
$dispatcher->listen(PtChatMessageSent::class, function () use ($dispatchMarker): void {
    file_put_contents($dispatchMarker, getmypid().PHP_EOL, FILE_APPEND | LOCK_EX);
});

$deadline = microtime(true) + 20;
while (! is_file($startBarrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'PT Chat retry barrier timeout.'.PHP_EOL);
        exit(3);
    }
    usleep(10000);
}

try {
    $status = (new PtChatDeliveryService($dispatcher))->phat((int) $messageId);
    echo json_encode(['status' => $status], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
