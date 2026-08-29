<?php

declare(strict_types=1);

use App\Services\MembershipActivationService;
use App\Services\MembershipProvisioningService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $mode, $database, $firstId, $secondId, $barrier] = $argv;
if (preg_match('/^smart_fitness_.*test/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, 'Unsafe concurrency database.'.PHP_EOL);
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

$deadline = microtime(true) + 15;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, 'Barrier timeout.'.PHP_EOL);
        exit(3);
    }
    usleep(10000);
}

try {
    $result = match ($mode) {
        'activate' => $app->make(MembershipActivationService::class)
            ->kichHoatNeuCan((int) $firstId),
        'provision' => $app->make(MembershipProvisioningService::class)
            ->capTuThanhToanDaXacNhan((int) $firstId, (int) $secondId),
        default => throw new RuntimeException('Unknown operation.'),
    };
    echo json_encode($result, JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
