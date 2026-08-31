<?php

declare(strict_types=1);

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\NguoiDung;
use App\Services\Admin\RoleManagementService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $actorId, $targetId, $role, $action, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "Role barrier timeout.\n");
        exit(3);
    }
    usleep(10000);
}

try {
    $actor = NguoiDung::query()->findOrFail((int) $actorId);
    $service = $app->make(RoleManagementService::class);
    $result = $action === 'revoke'
        ? $service->thuHoi($actor, (int) $targetId, $role)
        : $service->gan($actor, (int) $targetId, $role);
    echo json_encode([
        'status' => 'success',
        'transition' => $result['transition'],
        'assignment_id' => $result['assignment_id'],
        'active' => $result['active'],
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
