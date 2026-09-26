<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/BE/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/BE/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== 'smart_fitness') {
    throw new RuntimeException('Only the local smart_fitness connection may prepare the FE5 demo database.');
}

$name = 'smart_fitness_fe5_demo';
$existing = DB::selectOne('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$name]);
if ($existing === null) {
    DB::statement('CREATE DATABASE `smart_fitness_fe5_demo` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Created {$name}.\n";
} else {
    echo "Kept existing {$name}; no schema was replaced.\n";
}
