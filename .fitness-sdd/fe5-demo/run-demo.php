<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/check-target.php';

$runStep = static function (string $command, array $arguments, string $label): void {
    $status = Artisan::call($command, $arguments);
    if ($status !== 0) {
        throw new RuntimeException($command.' failed: '.Artisan::output());
    }
    echo $label.".\n";
};

$runStep('migrate', ['--force' => true], 'Migrations ready');
$hasDemoUsers = DB::table('nguoi_dung')->where('thu_dien_tu', 'dev.member01@smartfitness.local')->exists();
$hasExerciseCatalog = DB::table('bai_tap')->exists();
if (! $hasDemoUsers && ! $hasExerciseCatalog) {
    $runStep('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder', '--force' => true], 'Base demo accounts and catalog ready');
} elseif (! $hasDemoUsers || ! $hasExerciseCatalog) {
    throw new RuntimeException('Partial base seed found; refusing to overwrite existing demo data.');
} else {
    echo "Kept existing demo accounts and catalog.\n";
}
$runStep('db:seed', ['--class' => 'Database\\Seeders\\Fe5WorkspaceDemoSeeder', '--force' => true], 'FE5 workspace fixture ready');

$memberId = DB::table('ho_so_hoi_vien')
    ->join('nguoi_dung', 'nguoi_dung.id', '=', 'ho_so_hoi_vien.nguoi_dung_id')
    ->where('nguoi_dung.thu_dien_tu', 'dev.member01@smartfitness.local')
    ->value('ho_so_hoi_vien.id');
if ($memberId === null || ! DB::table('phan_cong_huan_luyen_vien')->where('hoi_vien_id', $memberId)->exists()
    || ! DB::table('ke_hoach_tap')->where('hoi_vien_id', $memberId)->exists()
    || ! DB::table('phien_tap')->where('hoi_vien_id', $memberId)->where('trang_thai', 'HOAN_THANH')->exists()
    || ! DB::table('ghi_chu_huan_luyen')->where('hoi_vien_id', $memberId)->exists()) {
    throw new RuntimeException('FE5 fixture verification failed; the demo is not ready.');
}

echo "Verified PT assignment, official plan, completed session and note for member ID {$memberId}.\n";
