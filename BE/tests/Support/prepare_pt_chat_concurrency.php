<?php

declare(strict_types=1);

use App\Models\NguoiDung;
use App\Services\MembershipProvisioningService;
use App\Services\MembershipSnapshotService;
use App\Services\Pt\Chat\PtChatConversationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Support\TestDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $mode] = $argv;
TestDatabaseGuard::khoiTaoTienTrinhCon($database, ['BROADCAST_CONNECTION' => 'null']);

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$now = CarbonImmutable::now('UTC');
$suffix = strtoupper(bin2hex(random_bytes(4)));
$branch = DB::table('chi_nhanh')->insertGetId([
    'ma_chi_nhanh' => 'CHAT_'.$suffix,
    'ten_chi_nhanh' => 'PT Chat concurrency '.$mode,
    'dia_chi' => 'Test only',
    'so_dien_thoai' => null,
    'mui_gio' => 'Asia/Ho_Chi_Minh',
    'trang_thai' => 'HOAT_DONG',
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);

$roles = [];
foreach (['ADMIN', 'MEMBER', 'PT'] as $role) {
    $roles[$role] = DB::table('vai_tro')->where('ma_vai_tro', $role)->value('id');
    if ($roles[$role] === null) {
        $roles[$role] = DB::table('vai_tro')->insertGetId([
            'ma_vai_tro' => $role,
            'ten_vai_tro' => $role,
            'mo_ta' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
    }
}

$taoNguoiDung = static function (string $code, string $role) use ($branch, $roles, $now, $suffix): int {
    $user = DB::table('nguoi_dung')->insertGetId([
        'chi_nhanh_id' => $branch,
        'ho_ten' => $code,
        'thu_dien_tu' => strtolower($code).'.'.strtolower($suffix).'@example.test',
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

$adminUser = $taoNguoiDung('CHAT_ADMIN_'.$mode, 'ADMIN');
$memberUser = $taoNguoiDung('CHAT_MEMBER_'.$mode, 'MEMBER');
$ptUser = $taoNguoiDung('CHAT_PT_'.$mode, 'PT');
$memberProfile = DB::table('ho_so_hoi_vien')->insertGetId([
    'nguoi_dung_id' => $memberUser,
    'ma_hoi_vien' => 'HV_CHAT_'.$suffix,
    'ngay_sinh' => null,
    'gioi_tinh' => null,
    'muc_tieu_tap_luyen' => null,
    'kinh_nghiem_tap_luyen' => null,
    'so_ngay_tap_mong_muon' => null,
    'thoi_luong_moi_buoi_phut' => null,
    'phien_ban_ho_so' => 1,
    'moc_thay_doi_ke_hoach' => 0,
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);
$ptProfile = DB::table('ho_so_huan_luyen_vien')->insertGetId([
    'nguoi_dung_id' => $ptUser,
    'ma_huan_luyen_vien' => 'PT_CHAT_'.$suffix,
    'gioi_thieu' => null,
    'chuyen_mon' => null,
    'trang_thai' => 'HOAT_DONG',
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);

$package = DB::table('goi_tap')->insertGetId([
    'chi_nhanh_id' => $branch,
    'ma_goi' => 'GOI_CHAT_'.$suffix,
    'ten_goi' => 'Goi Chat concurrency',
    'gia' => 100000,
    'thoi_han_ngay' => 30,
    'mo_ta' => null,
    'trang_thai' => 'DANG_BAN',
    'phien_ban_cau_hinh' => 1,
    'nguoi_tao_id' => $adminUser,
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);
DB::table('quyen_loi_goi_tap')->insert([
    'goi_tap_id' => $package,
    'cho_phep_vao_phong_tap' => false,
    'cho_phep_tro_ly_tap_luyen' => false,
    'gioi_han_luot_tro_ly' => 0,
    'cho_phep_tro_chuyen_huan_luyen_vien' => true,
    'so_buoi_huan_luyen_vien' => 0,
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);
$order = DB::table('don_mua_goi')->insertGetId([
    'hoi_vien_id' => $memberProfile,
    'goi_tap_id' => $package,
    'ma_don' => 'DON_CHAT_'.$suffix,
    'ma_yeu_cau' => (string) Str::uuid(),
    'so_tien_phai_thu' => 100000,
    'don_vi_tien' => 'VND',
    'trang_thai' => 'CHO_THANH_TOAN',
    'chot_gia_luc' => $now,
    'het_han_thanh_toan_luc' => $now->addHour(),
    'thanh_toan_luc' => null,
    'huy_luc' => null,
    'ly_do_huy' => null,
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);
$term = $app->make(MembershipSnapshotService::class)->taoChoDon($order);
$payment = DB::table('lan_thanh_toan')->insertGetId([
    'don_mua_goi_id' => $order,
    'so_lan' => 1,
    'ma_kenh_thanh_toan' => 'TEST',
    'ma_don_cong_thanh_toan' => random_int(100000000, 999999999),
    'ma_lien_ket_thanh_toan' => 'LINK_CHAT_'.$suffix,
    'duong_dan_thanh_toan' => null,
    'so_tien_yeu_cau' => 100000,
    'don_vi_tien' => 'VND',
    'trang_thai' => 'THANH_CONG',
    'ma_tham_chieu_duoc_chap_nhan' => 'REF_CHAT_'.$suffix,
    'so_tien_da_nhan' => 100000,
    'thanh_toan_luc' => $now,
    'xac_nhan_luc' => $now,
    'het_han_luc' => $now->addHour(),
    'ma_loi' => null,
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);
DB::table('don_mua_goi')->where('id', $order)->update([
    'trang_thai' => 'DA_THANH_TOAN',
    'thanh_toan_luc' => $now,
    'ngay_cap_nhat' => $now,
]);
$app->make(MembershipProvisioningService::class)->capTuThanhToanDaXacNhan($order, $payment);
$assignment = DB::table('phan_cong_huan_luyen_vien')->insertGetId([
    'hoi_vien_id' => $memberProfile,
    'huan_luyen_vien_id' => $ptProfile,
    'nguoi_phan_cong_id' => $adminUser,
    'ngay_bat_dau' => $now->subMinute(),
    'ngay_ket_thuc' => null,
    'ly_do_ket_thuc' => null,
    'ngay_tao' => $now,
    'ngay_cap_nhat' => $now,
]);

$conversationId = null;
if ($mode !== 'conversation') {
    $conversation = $app->make(PtChatConversationService::class)
        ->hienTai(NguoiDung::query()->findOrFail($memberUser));
    $conversationId = $conversation['id'];
}

echo json_encode([
    'database' => $database,
    'mode' => $mode,
    'admin_user_id' => $adminUser,
    'member_user_id' => $memberUser,
    'member_profile_id' => $memberProfile,
    'pt_user_id' => $ptUser,
    'pt_profile_id' => $ptProfile,
    'assignment_id' => $assignment,
    'conversation_id' => $conversationId,
    'term_id' => $term->getKey(),
], JSON_UNESCAPED_SLASHES).PHP_EOL;
