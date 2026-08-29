<?php

declare(strict_types=1);

use App\Services\MembershipProvisioningService;
use App\Services\MembershipSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $mode] = $argv;
if (preg_match('/^smart_fitness_.*test/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, "Unsafe PT fixture database.\n");
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
$branch = DB::table('chi_nhanh')->insertGetId([
    'ma_chi_nhanh' => 'PT_CONC_'.$mode,
    'ten_chi_nhanh' => 'PT concurrency',
    'dia_chi' => 'Test',
    'so_dien_thoai' => null,
    'mui_gio' => 'Asia/Ho_Chi_Minh',
    'trang_thai' => 'HOAT_DONG',
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);

$roles = [];
foreach (['ADMIN', 'MEMBER', 'PT'] as $role) {
    $roles[$role] = DB::table('vai_tro')->insertGetId([
        'ma_vai_tro' => $role,
        'ten_vai_tro' => $role,
        'mo_ta' => null,
        'ngay_tao' => $now,
        'ngay_cap_nhat' => $now,
    ]);
}

$taoNguoiDung = static function (string $code, string $role) use ($branch, $roles, $now): int {
    $user = DB::table('nguoi_dung')->insertGetId([
        'chi_nhanh_id' => $branch,
        'ho_ten' => $code,
        'thu_dien_tu' => strtolower($code).'@example.test',
        'so_dien_thoai' => null,
        'mat_khau_bam' => Hash::make('Test!Password123'),
        'anh_dai_dien' => null,
        'xac_minh_thu_luc' => null,
        'trang_thai' => 'HOAT_DONG',
        'dang_nhap_gan_nhat_luc' => null,
        'ngay_tao' => $now,
        'ngay_cap_nhat' => $now,
    ]);
    DB::table('phan_quyen_nguoi_dung')->insert([
        'nguoi_dung_id' => $user,
        'vai_tro_id' => $roles[$role],
        'nguoi_cap_id' => null,
        'cap_luc' => $now,
        'thu_hoi_luc' => null,
        'ngay_tao' => $now,
        'ngay_cap_nhat' => $now,
    ]);

    return $user;
};

$admin = $taoNguoiDung('PT_ADMIN_'.$mode, 'ADMIN');
$member = $taoNguoiDung('PT_MEMBER_'.$mode, 'MEMBER');
$ptAUser = $taoNguoiDung('PT_A_'.$mode, 'PT');
$ptBUser = $taoNguoiDung('PT_B_'.$mode, 'PT');
$memberProfile = DB::table('ho_so_hoi_vien')->insertGetId([
    'nguoi_dung_id' => $member, 'ma_hoi_vien' => 'HV_'.$mode, 'ngay_sinh' => null,
    'gioi_tinh' => null, 'muc_tieu_tap_luyen' => null, 'kinh_nghiem_tap_luyen' => null,
    'so_ngay_tap_mong_muon' => null, 'thoi_luong_moi_buoi_phut' => null, 'phien_ban_ho_so' => 1,
    'moc_thay_doi_ke_hoach' => 0, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$ptA = DB::table('ho_so_huan_luyen_vien')->insertGetId([
    'nguoi_dung_id' => $ptAUser, 'ma_huan_luyen_vien' => 'PT_'.$mode.'_A', 'gioi_thieu' => null,
    'chuyen_mon' => null, 'trang_thai' => 'HOAT_DONG', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$ptB = DB::table('ho_so_huan_luyen_vien')->insertGetId([
    'nguoi_dung_id' => $ptBUser, 'ma_huan_luyen_vien' => 'PT_'.$mode.'_B', 'gioi_thieu' => null,
    'chuyen_mon' => null, 'trang_thai' => 'HOAT_DONG', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);

$goi = DB::table('goi_tap')->insertGetId([
    'chi_nhanh_id' => $branch, 'ma_goi' => 'GOI_'.$mode, 'ten_goi' => 'Goi PT '.$mode,
    'gia' => 100000, 'thoi_han_ngay' => 30, 'mo_ta' => null, 'trang_thai' => 'DANG_BAN',
    'phien_ban_cau_hinh' => 1, 'nguoi_tao_id' => $admin, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
DB::table('quyen_loi_goi_tap')->insert([
    'goi_tap_id' => $goi, 'cho_phep_vao_phong_tap' => false, 'cho_phep_tro_ly_tap_luyen' => false,
    'gioi_han_luot_tro_ly' => 0, 'cho_phep_tro_chuyen_huan_luyen_vien' => false,
    'so_buoi_huan_luyen_vien' => 1, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$don = DB::table('don_mua_goi')->insertGetId([
    'hoi_vien_id' => $memberProfile, 'goi_tap_id' => $goi, 'ma_don' => 'DON_'.$mode,
    'ma_yeu_cau' => (string) Str::uuid(), 'so_tien_phai_thu' => 100000, 'don_vi_tien' => 'VND',
    'trang_thai' => 'CHO_THANH_TOAN', 'chot_gia_luc' => $now, 'het_han_thanh_toan_luc' => $now->addHour(),
    'thanh_toan_luc' => null, 'huy_luc' => null, 'ly_do_huy' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
$snapshot = $app->make(MembershipSnapshotService::class)->taoChoDon($don);
$payment = DB::table('lan_thanh_toan')->insertGetId([
    'don_mua_goi_id' => $don, 'so_lan' => 1, 'ma_kenh_thanh_toan' => 'TEST',
    'ma_don_cong_thanh_toan' => random_int(100000, 999999), 'ma_lien_ket_thanh_toan' => 'LINK_'.$mode,
    'duong_dan_thanh_toan' => null, 'so_tien_yeu_cau' => 100000, 'don_vi_tien' => 'VND',
    'trang_thai' => 'THANH_CONG', 'ma_tham_chieu_duoc_chap_nhan' => 'REF_'.$mode,
    'so_tien_da_nhan' => 100000, 'thanh_toan_luc' => $now, 'xac_nhan_luc' => $now,
    'het_han_luc' => $now->addHour(), 'ma_loi' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
]);
DB::table('don_mua_goi')->where('id', $don)->update(['trang_thai' => 'DA_THANH_TOAN', 'thanh_toan_luc' => $now]);
$app->make(MembershipProvisioningService::class)->capTuThanhToanDaXacNhan($don, $payment);

$assignment = null;
if ($mode !== 'assign_empty') {
    $assignment = DB::table('phan_cong_huan_luyen_vien')->insertGetId([
        'hoi_vien_id' => $memberProfile, 'huan_luyen_vien_id' => $ptA, 'nguoi_phan_cong_id' => $admin,
        'ngay_bat_dau' => $now->subMinute(), 'ngay_ket_thuc' => null, 'ly_do_ket_thuc' => null,
        'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
    ]);
}

echo json_encode([
    'admin_id' => $admin, 'member_id' => $memberProfile, 'pt_a_user_id' => $ptAUser,
    'pt_b_user_id' => $ptBUser, 'pt_a_id' => $ptA, 'pt_b_id' => $ptB,
    'assignment_id' => $assignment, 'start_at' => $now->addMinutes(1)->toISOString(),
    'term_id' => $snapshot->getKey(), 'database' => $database,
], JSON_UNESCAPED_SLASHES).PHP_EOL;
