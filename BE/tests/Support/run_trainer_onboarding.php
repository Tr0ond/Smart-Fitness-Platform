<?php

declare(strict_types=1);

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\NguoiDung;
use App\Services\Admin\TrainerOnboardingService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $adminId, $email, $key, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "Trainer onboarding barrier timeout.\n");
        exit(3);
    }
    usleep(10000);
}

try {
    $admin = NguoiDung::query()->findOrFail((int) $adminId);
    $result = $app->make(TrainerOnboardingService::class)->tao($admin, [
        'name' => 'Concurrent Trainer',
        'email' => $email,
        'phone' => null,
        'introduction' => 'Concurrent onboarding',
        'specialties' => 'Strength',
        'status' => 'HOAT_DONG',
        '_idempotency_key' => $key,
    ]);
    echo json_encode([
        'status' => 'success',
        'account_id' => $result['account']['id'],
        'profile_id' => $result['trainer_profile']['id'],
        'invitation' => $result['invitation'],
        'replayed' => $result['replayed'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (AuthWorkflowException $exception) {
    echo json_encode([
        'status' => 'error',
        'code' => $exception->safeCode,
        'http_status' => $exception->responseStatus,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
    exit(1);
}
