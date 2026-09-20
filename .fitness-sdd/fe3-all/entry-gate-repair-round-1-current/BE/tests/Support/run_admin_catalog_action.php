<?php

declare(strict_types=1);

use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Models\NguoiDung;
use App\Services\Admin\ExerciseCatalogAdminService;
use App\Services\Admin\PackageCatalogAdminService;
use App\Services\Admin\WorkoutTemplateCatalogAdminService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $actorId, $action, $targetId, $payloadJson, $barrier] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "Catalog barrier timeout.\n");
        exit(3);
    }
    usleep(10000);
}

try {
    $actor = NguoiDung::query()->findOrFail((int) $actorId);
    $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
    $result = match ($action) {
        'benefits' => $app->make(PackageCatalogAdminService::class)
            ->capNhatQuyenLoi($actor, (int) $targetId, $payload),
        'exercise' => $app->make(ExerciseCatalogAdminService::class)
            ->capNhatBaiTap($actor, (int) $targetId, $payload),
        'muscle_group' => $app->make(ExerciseCatalogAdminService::class)
            ->capNhatNhomCo($actor, (int) $targetId, $payload),
        'template' => $app->make(WorkoutTemplateCatalogAdminService::class)
            ->capNhat($actor, (int) $targetId, $payload),
        'template_revision' => $app->make(WorkoutTemplateCatalogAdminService::class)
            ->taoPhienBanMoi($actor, (int) $targetId, $payload),
        default => throw new RuntimeException('Unknown catalog action.'),
    };
    echo json_encode(['status' => 'success', 'data' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (CatalogWorkflowException $exception) {
    echo json_encode([
        'status' => 'error',
        'code' => $exception->safeCode,
        'response_status' => $exception->responseStatus,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage()."\n");
    exit(1);
}
