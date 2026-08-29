<?php

namespace Tests\Concerns;

use App\Models\NguoiDung;
use App\Models\TheTruyCap;
use App\Support\EmailCanonicalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;

trait CreatesAuthenticationFixtures
{
    protected const MAT_KHAU_HOP_LE = 'AuthTest!Password123';

    protected function batDauGiaoDichAuthCoLap(): void
    {
        $databaseCauHinh = (string) config('database.connections.mysql.database');
        $databaseThucTe = (string) (DB::selectOne('SELECT DATABASE() AS ten_database')->ten_database ?? '');

        $this->assertSame('mysql', (string) config('database.default'));
        $this->assertSame($databaseCauHinh, $databaseThucTe);
        $this->assertMatchesRegularExpression(
            '/^smart_fitness_.*test/i',
            $databaseThucTe,
            'Auth tests require a disposable smart_fitness_*test schema.',
        );
        $this->assertNotSame('smart_fitness', strtolower($databaseThucTe));

        DB::beginTransaction();
    }

    protected function ketThucGiaoDichAuthCoLap(): void
    {
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }

        CarbonImmutable::setTestNow();
    }

    /**
     * @param  array<int, string>  $cacVaiTro
     * @return array{user: NguoiDung, password: string, branch_id: int, role_ids: array<string, int>}
     */
    protected function taoNguoiDungAuth(
        array $cacVaiTro = ['MEMBER'],
        string $trangThai = 'HOAT_DONG',
        ?string $thuDienTu = null,
    ): array {
        $hauTo = strtolower(bin2hex(random_bytes(6)));
        $hienTai = CarbonImmutable::now('UTC');
        $chiNhanhId = DB::table('chi_nhanh')->insertGetId([
            'ma_chi_nhanh' => 'AUTH_'.strtoupper($hauTo),
            'ten_chi_nhanh' => 'Chi nhanh test auth '.$hauTo,
            'dia_chi' => 'Test only',
            'so_dien_thoai' => null,
            'mui_gio' => 'Asia/Ho_Chi_Minh',
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);

        $thuDienTu ??= 'auth.'.$hauTo.'@example.com';
        $thuDienTu = app(EmailCanonicalizer::class)->chuanHoa($thuDienTu);
        $nguoiDung = NguoiDung::query()->create([
            'chi_nhanh_id' => $chiNhanhId,
            'ho_ten' => 'Nguoi dung test auth',
            'thu_dien_tu' => $thuDienTu,
            'so_dien_thoai' => null,
            'mat_khau_bam' => Hash::make(self::MAT_KHAU_HOP_LE),
            'anh_dai_dien' => null,
            'xac_minh_thu_luc' => null,
            'trang_thai' => $trangThai,
            'dang_nhap_gan_nhat_luc' => null,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);

        $roleIds = [];
        foreach ($cacVaiTro as $maVaiTro) {
            $vaiTroId = DB::table('vai_tro')->where('ma_vai_tro', $maVaiTro)->value('id');
            if ($vaiTroId === null) {
                $vaiTroId = DB::table('vai_tro')->insertGetId([
                    'ma_vai_tro' => $maVaiTro,
                    'ten_vai_tro' => 'Vai tro test '.$maVaiTro,
                    'mo_ta' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
            }
            DB::table('phan_quyen_nguoi_dung')->insert([
                'nguoi_dung_id' => $nguoiDung->getKey(),
                'vai_tro_id' => $vaiTroId,
                'nguoi_cap_id' => null,
                'cap_luc' => $hienTai,
                'thu_hoi_luc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            $roleIds[$maVaiTro] = $vaiTroId;
        }

        return [
            'user' => $nguoiDung,
            'password' => self::MAT_KHAU_HOP_LE,
            'branch_id' => $chiNhanhId,
            'role_ids' => $roleIds,
        ];
    }

    protected function dangNhapApi(
        string $thuDienTu,
        string $matKhau = self::MAT_KHAU_HOP_LE,
        string $tenThietBi = 'PHPUnit',
        ?string $diaChiIp = null,
    ): TestResponse {
        $diaChiIp ??= '192.0.2.'.random_int(1, 254);

        return $this->withServerVariables(['REMOTE_ADDR' => $diaChiIp])->postJson('/api/auth/login', [
            'email' => $thuDienTu,
            'password' => $matKhau,
            'device_name' => $tenThietBi,
        ]);
    }

    protected function bearer(string $rawToken): array
    {
        return ['Authorization' => 'Bearer '.$rawToken];
    }

    protected function taoTheTruyCapThuCong(
        NguoiDung $nguoiDung,
        CarbonImmutable $ngayTao,
        CarbonImmutable $hetHanLuc,
        ?CarbonImmutable $thuHoiLuc = null,
    ): string {
        $rawToken = bin2hex(random_bytes(32));
        TheTruyCap::query()->create([
            'nguoi_dung_id' => $nguoiDung->getKey(),
            'ten_thiet_bi' => 'PHPUnit manual token',
            'ma_bam_the' => hash('sha256', $rawToken),
            'pham_vi_truy_cap' => ['api'],
            'su_dung_gan_nhat_luc' => null,
            'het_han_luc' => $hetHanLuc,
            'thu_hoi_luc' => $thuHoiLuc,
            'ngay_tao' => $ngayTao,
            'ngay_cap_nhat' => $ngayTao,
        ]);

        return $rawToken;
    }

    /** @return array{hoi_vien_id: int, dang_ky_id: int} */
    protected function taoMembershipChoKichHoat(array $fixture): array
    {
        $hienTai = CarbonImmutable::now('UTC');
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $hoiVienId = DB::table('ho_so_hoi_vien')->insertGetId([
            'nguoi_dung_id' => $fixture['user']->getKey(),
            'ma_hoi_vien' => 'HV_AUTH_'.$hauTo,
            'ngay_sinh' => null,
            'gioi_tinh' => null,
            'muc_tieu_tap_luyen' => null,
            'kinh_nghiem_tap_luyen' => null,
            'so_ngay_tap_mong_muon' => null,
            'thoi_luong_moi_buoi_phut' => null,
            'phien_ban_ho_so' => 1,
            'moc_thay_doi_ke_hoach' => 0,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
        $dangKyId = DB::table('dang_ky_goi_tap')->insertGetId([
            'hoi_vien_id' => $hoiVienId,
            'chi_nhanh_id' => $fixture['branch_id'],
            'trang_thai' => 'CHO_KICH_HOAT',
            'lan_su_dung_dau_tien_id' => null,
            'ngay_bat_dau' => null,
            'ket_thuc_ghi_nhan_luc' => null,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);

        return ['hoi_vien_id' => $hoiVienId, 'dang_ky_id' => $dangKyId];
    }
}
