<?php

declare(strict_types=1);

$root = 'E:/Fitness';
if (strtolower(str_replace('\\', '/', PHP_BINARY)) !== strtolower($root.'/.tools/php/php.exe')) {
    throw new RuntimeException('Only the approved portable PHP runtime is allowed.');
}
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'smart_fitness_test', 'SMART_FITNESS_TEST_DATABASE' => 'smart_fitness_test'] as $name => $expected) {
    if (getenv($name) !== $expected) {
        throw new RuntimeException('Unexpected test environment: '.$name);
    }
}
require $root.'/BE/vendor/autoload.php';
$app = require $root.'/BE/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Support\TestDatabaseGuard::damBaoDatabaseHienTai();
if (config('database.connections.mysql.database') !== 'smart_fitness_test') {
    throw new RuntimeException('Unexpected configured database.');
}
echo 'GUARD PASS: '.PHP_BINARY.'; PHP '.PHP_VERSION.'; testing/mysql/smart_fitness_test'.PHP_EOL;
