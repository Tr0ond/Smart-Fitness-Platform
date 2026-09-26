<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/BE/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/BE/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = DB::connection()->getDatabaseName();
if (! $app->environment('local') || config('database.default') !== 'mysql'
    || $database !== 'smart_fitness_fe5_demo') {
    throw new RuntimeException('Refusing FE5 demo operation on '.$database.'.');
}

echo "Verified local target: {$database}.\n";
