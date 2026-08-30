<?php

declare(strict_types=1);

use App\Models\NguoiDung;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database] = $argv;
if (preg_match('/^smart_fitness_workout_test_/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, "Unsafe Workout concurrency database.\n");
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

$now = CarbonImmutable::now('UTC');
$suffix = strtoupper(bin2hex(random_bytes(4)));
$branch = DB::table('chi_nhanh')->insertGetId([
    'ma_chi_nhanh' => 'WO_CONC_'.$suffix, 'ten_chi_nhanh' => 'Workout concurrency', 'dia_chi' => 'Test',
    'so_dien_thoai' => null, 'mui_gio' => 'Asia/Ho_Chi_Minh', 'trang_thai' => 'HOAT_DONG', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$user = DB::table('nguoi_dung')->insertGetId([
    'chi_nhanh_id' => $branch, 'ho_ten' => 'Workout member', 'thu_dien_tu' => 'workout.'.$suffix.'@example.test',
    'so_dien_thoai' => null, 'mat_khau_bam' => Hash::make('Test!Password123'), 'anh_dai_dien' => null,
    'xac_minh_thu_luc' => null, 'trang_thai' => 'HOAT_DONG', 'dang_nhap_gan_nhat_luc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$role = DB::table('vai_tro')->where('ma_vai_tro', 'MEMBER')->value('id');
DB::table('phan_quyen_nguoi_dung')->insert([
    'nguoi_dung_id' => $user, 'vai_tro_id' => $role, 'nguoi_cap_id' => null, 'cap_luc' => $now,
    'thu_hoi_luc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$member = DB::table('ho_so_hoi_vien')->insertGetId([
    'nguoi_dung_id' => $user, 'ma_hoi_vien' => 'WO_'.$suffix, 'ngay_sinh' => null, 'gioi_tinh' => null,
    'muc_tieu_tap_luyen' => 'TANG_SUC_MANH', 'kinh_nghiem_tap_luyen' => 'MOI_BAT_DAU',
    'so_ngay_tap_mong_muon' => 1, 'thoi_luong_moi_buoi_phut' => 60, 'phien_ban_ho_so' => 1,
    'moc_thay_doi_ke_hoach' => 0, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$exercise = DB::table('bai_tap')->insertGetId([
    'ma_bai_tap' => 'WO_EX_'.$suffix, 'ten_bai_tap' => 'Concurrency exercise', 'do_kho' => 'TRUNG_BINH',
    'huong_dan' => 'Test', 'duong_dan_hinh_anh' => null, 'duong_dan_video' => null, 'thong_tin_bo_sung' => null,
    'phien_ban_noi_dung' => 1, 'trang_thai' => 'HOAT_DONG', 'nguoi_tao_id' => $user, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$local = CarbonImmutable::now('Asia/Ho_Chi_Minh');
$weekday = $local->dayOfWeekIso === 7 ? 8 : $local->dayOfWeekIso + 1;
$definition = static fn (string $name): array => [
    'name' => $name, 'goal' => 'TANG_SUC_MANH', 'effective_from' => $local->toDateString(),
    'days' => [[
        'order' => 1, 'weekday' => $weekday, 'name' => 'Concurrency day', 'estimated_minutes' => 60,
        'exercises' => [[
            'exercise_id' => $exercise, 'order' => 1, 'target_sets' => 3, 'min_reps' => 8,
            'max_reps' => 12, 'target_weight_kg' => 20, 'rest_seconds' => 90,
        ]],
    ]],
];
$memberUser = NguoiDung::query()->findOrFail($user);
$planA = $app->make(WorkoutPlanService::class)->taoMoi($memberUser, $definition('Plan A'), (string) Str::uuid(), true)->fresh('phienBanHienTai.ngayTrongKeHoachs');
$planB = $app->make(WorkoutPlanService::class)->taoMoi($memberUser, $definition('Plan B'), (string) Str::uuid(), false)->fresh('phienBanHienTai.ngayTrongKeHoachs');
$schedule = $app->make(WorkoutScheduleService::class)->taoMotBuoi(
    $member, $planA->getKey(), $planA->phienBanHienTai->getKey(), $planA->phienBanHienTai->ngayTrongKeHoachs->firstOrFail()->getKey(), $local->toDateString(),
);

echo json_encode([
    'database' => $database, 'user_id' => $user, 'member_id' => $member,
    'plan_a_id' => $planA->getKey(), 'plan_b_id' => $planB->getKey(),
    'version_id' => $planA->phienBanHienTai->getKey(), 'day_id' => $planA->phienBanHienTai->ngayTrongKeHoachs->firstOrFail()->getKey(),
    'schedule_id' => $schedule->getKey(), 'schedule_date' => $local->toDateString(), 'extra_date' => $local->addDay()->toDateString(),
    'start_key' => (string) Str::uuid(), 'complete_key' => (string) Str::uuid(),
], JSON_UNESCAPED_SLASHES).PHP_EOL;
