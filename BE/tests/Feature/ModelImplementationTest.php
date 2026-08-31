<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\BuoiTapDuKien;
use App\Models\ChiSoCoThe;
use App\Models\DangKyGoiTap;
use App\Models\DeXuatKeHoachTap;
use App\Models\GoiTap;
use App\Models\HoiThoai;
use App\Models\HoiThoaiTroLy;
use App\Models\HoSoHoiVien;
use App\Models\KeHoachTap;
use App\Models\KyHanHoiVien;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\PhienBanKeHoachTap;
use App\Models\PhienTap;
use App\Models\QuyenLoiGoiTap;
use App\Models\TheTruyCap;
use App\Models\TinNhan;
use App\Models\TinNhanTroLy;
use App\Models\YeuCauChongLap;
use App\Models\YeuCauTroLy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

final class ModelImplementationTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $db;

    /** @var array<string, string> */
    private array $models = [
        'chi_nhanh' => 'ChiNhanh',
        'nguoi_dung' => 'NguoiDung',
        'vai_tro' => 'VaiTro',
        'phan_quyen_nguoi_dung' => 'PhanQuyenNguoiDung',
        'ho_so_hoi_vien' => 'HoSoHoiVien',
        'ho_so_huan_luyen_vien' => 'HoSoHuanLuyenVien',
        'ngay_ranh_hoi_vien' => 'NgayRanhHoiVien',
        'dung_cu_hoi_vien' => 'DungCuHoiVien',
        'chi_so_co_the' => 'ChiSoCoThe',
        'the_truy_cap' => 'TheTruyCap',
        'yeu_cau_dat_lai_mat_khau' => 'YeuCauDatLaiMatKhau',
        'goi_tap' => 'GoiTap',
        'quyen_loi_goi_tap' => 'QuyenLoiGoiTap',
        'don_mua_goi' => 'DonMuaGoi',
        'lan_thanh_toan' => 'LanThanhToan',
        'su_kien_thanh_toan' => 'SuKienThanhToan',
        'dang_ky_goi_tap' => 'DangKyGoiTap',
        'ky_han_hoi_vien' => 'KyHanHoiVien',
        'su_dung_quyen_loi' => 'SuDungQuyenLoi',
        'ma_vao_phong_tap' => 'MaVaoPhongTap',
        'lich_su_vao_phong_tap' => 'LichSuVaoPhongTap',
        'phan_cong_huan_luyen_vien' => 'PhanCongHuanLuyenVien',
        'lich_su_su_dung_huan_luyen_vien' => 'LichSuSuDungHuanLuyenVien',
        'ghi_chu_huan_luyen' => 'GhiChuHuanLuyen',
        'dung_cu' => 'DungCu',
        'nhom_co' => 'NhomCo',
        'bai_tap' => 'BaiTap',
        'bai_tap_dung_cu' => 'BaiTapDungCu',
        'bai_tap_nhom_co' => 'BaiTapNhomCo',
        'giao_an_mau' => 'GiaoAnMau',
        'ngay_trong_giao_an' => 'NgayTrongGiaoAn',
        'bai_tap_trong_giao_an' => 'BaiTapTrongGiaoAn',
        'ke_hoach_tap' => 'KeHoachTap',
        'phien_ban_ke_hoach_tap' => 'PhienBanKeHoachTap',
        'ngay_trong_ke_hoach' => 'NgayTrongKeHoach',
        'bai_tap_trong_ke_hoach' => 'BaiTapTrongKeHoach',
        'buoi_tap_du_kien' => 'BuoiTapDuKien',
        'phien_tap' => 'PhienTap',
        'bai_tap_trong_phien' => 'BaiTapTrongPhien',
        'hiep_tap' => 'HiepTap',
        'hoi_thoai' => 'HoiThoai',
        'tin_nhan' => 'TinNhan',
        'su_kien_phat_tin_nhan' => 'SuKienPhatTinNhan',
        'hoi_thoai_tro_ly' => 'HoiThoaiTroLy',
        'tin_nhan_tro_ly' => 'TinNhanTroLy',
        'yeu_cau_tro_ly' => 'YeuCauTroLy',
        'bai_tap_ung_vien' => 'BaiTapUngVien',
        'giao_an_ung_vien' => 'GiaoAnUngVien',
        'lan_goi_mo_hinh' => 'LanGoiMoHinh',
        'de_xuat_ke_hoach_tap' => 'DeXuatKeHoachTap',
        'nhat_ky_he_thong' => 'NhatKyHeThong',
        'yeu_cau_chong_lap' => 'YeuCauChongLap',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
        $this->db = DB::connection('mysql')->getConfig();
        $this->assertSame('mysql', $this->db['driver'] ?? null, 'Model tests must use the mysql connection.');
        DB::connection('mysql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = DB::connection('mysql');
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        parent::tearDown();
    }

    public function test_all_52_core_models_map_to_existing_tables(): void
    {
        $this->assertCount(52, $this->models);

        foreach ($this->models as $table => $shortName) {
            $class = 'App\\Models\\'.$shortName;
            $this->assertTrue(class_exists($class), $class.' must exist.');
            $model = new $class;

            $this->assertSame($table, $model->getTable());
            $this->assertSame('id', $model->getKeyName());
            $this->assertTrue($model->getIncrementing());
            $this->assertSame('int', $model->getKeyType());
            $this->assertTrue(Schema::connection('mysql')->hasTable($table));

            $columns = array_map(
                static fn (object $column): string => $column->column_name,
                DB::connection('mysql')->select(
                    'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
                    [$this->db['database'], $table],
                ),
            );
            $this->assertEmpty(array_diff(array_keys($model->getCasts()), $columns), $shortName.' contains a cast for a missing column.');

            $generated = DB::connection('mysql')->select(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND extra LIKE '%GENERATED%'",
                [$this->db['database'], $table],
            );
            foreach ($generated as $column) {
                $this->assertFalse($model->isFillable($column->column_name), $shortName.' exposes generated column '.$column->column_name.' for mass assignment.');
            }
        }
    }

    public function test_timestamps_and_generated_column_configuration_match_schema(): void
    {
        foreach ($this->models as $table => $shortName) {
            $model = new ('App\\Models\\'.$shortName)();
            $columns = array_column(DB::connection('mysql')->select(
                'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
                [$this->db['database'], $table],
            ), 'column_name');
            $hasCreated = in_array('ngay_tao', $columns, true);
            $hasUpdated = in_array('ngay_cap_nhat', $columns, true);
            $this->assertSame($hasCreated && $hasUpdated, $model->usesTimestamps(), $shortName.' timestamp setting mismatch.');
            if ($hasCreated && $hasUpdated) {
                $this->assertSame('ngay_tao', $model->getCreatedAtColumn());
                $this->assertSame('ngay_cap_nhat', $model->getUpdatedAtColumn());
            }
        }

        $this->assertFalse((new DangKyGoiTap)->isFillable('hoi_vien_chua_ket_thuc_id'));
        $this->assertFalse((new PhanCongHuanLuyenVien)->isFillable('hoi_vien_dang_phan_cong_id'));
        $this->assertFalse((new KeHoachTap)->isFillable('hoi_vien_dang_su_dung_id'));
        $this->assertFalse((new BuoiTapDuKien)->isFillable('ma_buoi_con_hieu_luc'));
        $this->assertFalse((new BuoiTapDuKien)->isFillable('ngay_tap_con_hieu_luc'));
    }

    public function test_representative_cast_types_match_physical_columns(): void
    {
        $this->assertSame('array', (new TheTruyCap)->getCasts()['pham_vi_truy_cap']);
        $this->assertSame('array', (new BaiTap)->getCasts()['thong_tin_bo_sung']);
        $this->assertSame('boolean', (new QuyenLoiGoiTap)->getCasts()['cho_phep_vao_phong_tap']);
        $this->assertSame('datetime', (new NguoiDung)->getCasts()['ngay_tao']);
        $this->assertSame('date', (new BuoiTapDuKien)->getCasts()['ngay_tap']);
        $this->assertSame('decimal:2', (new ChiSoCoThe)->getCasts()['can_nang_kg']);
    }

    public function test_critical_relationship_methods_return_correct_eloquent_relations(): void
    {
        $cases = [
            [new NguoiDung, 'chiNhanh', BelongsTo::class],
            [new HoSoHoiVien, 'nguoiDung', BelongsTo::class],
            [new GoiTap, 'quyenLoiGoiTap', HasOne::class],
            [new DangKyGoiTap, 'hoiVien', BelongsTo::class],
            [new KyHanHoiVien, 'donMuaGoi', BelongsTo::class],
            [new PhanCongHuanLuyenVien, 'hoiVien', BelongsTo::class],
            [new KeHoachTap, 'phienBanHienTai', BelongsTo::class],
            [new PhienBanKeHoachTap, 'keHoachTap', BelongsTo::class],
            [new BuoiTapDuKien, 'phienBanKeHoachTap', BelongsTo::class],
            [new PhienTap, 'buoiTapDuKien', BelongsTo::class],
            [new HoiThoai, 'phanCongHuanLuyenVien', BelongsTo::class],
            [new TinNhan, 'hoiThoai', BelongsTo::class],
            [new HoiThoaiTroLy, 'hoiVien', BelongsTo::class],
            [new TinNhanTroLy, 'hoiThoaiTroLy', BelongsTo::class],
            [new YeuCauTroLy, 'hoiThoaiTroLy', BelongsTo::class],
            [new DeXuatKeHoachTap, 'keHoachTap', BelongsTo::class],
            [new NhatKyHeThong, 'nguoiThucHien', BelongsTo::class],
            [new YeuCauChongLap, 'nguoiDung', BelongsTo::class],
            [new PhienBanKeHoachTap, 'phienBanKeHoachTaps', HasMany::class],
        ];

        foreach ($cases as [$model, $method, $relationClass]) {
            $this->assertInstanceOf($relationClass, $model->{$method}(), get_class($model).'::'.$method.'()');
        }
    }

    public function test_representative_relationships_load_from_mariadb(): void
    {
        $ids = $this->seedRepresentativeGraph();

        $member = HoSoHoiVien::query()->findOrFail($ids['ho_so_hoi_vien'][1]);
        $this->assertSame($ids['nguoi_dung'][1], $member->nguoiDung->getKey());
        $this->assertSame('CN-TEST', $member->nguoiDung->chiNhanh->ma_chi_nhanh);

        $package = GoiTap::query()->findOrFail($ids['goi_tap'][1]);
        $this->assertSame($ids['quyen_loi_goi_tap'][1], $package->quyenLoiGoiTap->getKey());
        $this->assertSame($ids['don_mua_goi'][1], $package->donMuaGois->firstOrFail()->getKey());

        $period = KyHanHoiVien::query()->findOrFail($ids['ky_han_hoi_vien'][1]);
        $this->assertSame($ids['don_mua_goi'][1], $period->donMuaGoi->getKey());
        $this->assertSame($ids['dang_ky_goi_tap'][1], $period->dangKyGoiTap->getKey());

        $assignment = PhanCongHuanLuyenVien::query()->findOrFail($ids['phan_cong_huan_luyen_vien'][1]);
        $this->assertSame($ids['ho_so_hoi_vien'][1], $assignment->hoiVien->getKey());
        $this->assertSame($ids['ho_so_huan_luyen_vien'][2], $assignment->huanLuyenVien->getKey());

        $plan = KeHoachTap::query()->findOrFail($ids['ke_hoach_tap'][1]);
        $this->assertSame($ids['phien_ban_ke_hoach_tap'][1], $plan->phienBanKeHoachTaps->firstOrFail()->getKey());
        $schedule = BuoiTapDuKien::query()->findOrFail($ids['buoi_tap_du_kien'][1]);
        $this->assertSame($ids['phien_tap'][1], $schedule->phienTap->getKey());
        $session = PhienTap::query()->findOrFail($ids['phien_tap'][1]);
        $this->assertSame($ids['bai_tap_trong_phien'][1], $session->baiTapTrongPhiens->firstOrFail()->getKey());
        $this->assertSame($ids['buoi_tap_du_kien'][1], $session->buoiTapDuKien->getKey());

        $message = TinNhan::query()->findOrFail($ids['tin_nhan'][1]);
        $this->assertSame($ids['hoi_thoai'][1], $message->hoiThoai->getKey());
        $assistantRequest = YeuCauTroLy::query()->findOrFail($ids['yeu_cau_tro_ly'][1]);
        $this->assertSame($ids['hoi_thoai_tro_ly'][1], $assistantRequest->hoiThoaiTroLy->getKey());
        $this->assertSame($ids['tin_nhan_tro_ly'][1], $assistantRequest->tinNhanDauVao->getKey());
        $this->assertSame($ids['tin_nhan_tro_ly'][2], $assistantRequest->tinNhanTroLys->firstOrFail()->getKey());

        $this->assertSame($ids['nguoi_dung'][1], NhatKyHeThong::query()->findOrFail($ids['nhat_ky_he_thong'][1])->nguoiThucHien->getKey());
        $this->assertSame($ids['nguoi_dung'][1], YeuCauChongLap::query()->findOrFail($ids['yeu_cau_chong_lap'][1])->nguoiDung->getKey());
    }

    /**
     * Build a relationship graph with generated primary keys only.
     * Logical aliases are kept outside the inserted rows and resolved to the
     * generated IDs so the fixture is independent of existing auto-increment
     * state and can safely run after seeded master data.
     *
     * @return array<string, array<int, int>>
     */
    private function seedRepresentativeGraph(): array
    {
        $now = '2026-08-29 10:00:00.000000';
        $db = DB::connection('mysql');
        /** @var array<string, array<int, int>> $ids */
        $ids = [];
        $foreignTables = [
            'chi_nhanh_id' => 'chi_nhanh',
            'nguoi_dung_id' => 'nguoi_dung',
            'nguoi_tao_id' => 'nguoi_dung',
            'nguoi_thuc_hien_id' => 'nguoi_dung',
            'nguoi_xac_nhan_id' => 'nguoi_dung',
            'nguoi_gui_id' => 'nguoi_dung',
            'nguoi_phan_cong_id' => 'nguoi_dung',
            'hoi_vien_id' => 'ho_so_hoi_vien',
            'huan_luyen_vien_id' => 'ho_so_huan_luyen_vien',
            'goi_tap_id' => 'goi_tap',
            'don_mua_goi_id' => 'don_mua_goi',
            'lan_thanh_toan_id' => 'lan_thanh_toan',
            'dang_ky_goi_tap_id' => 'dang_ky_goi_tap',
            'ky_han_hoi_vien_id' => 'ky_han_hoi_vien',
            'su_dung_quyen_loi_id' => 'su_dung_quyen_loi',
            'ma_vao_phong_tap_id' => 'ma_vao_phong_tap',
            'phan_cong_huan_luyen_vien_id' => 'phan_cong_huan_luyen_vien',
            'ke_hoach_tap_id' => 'ke_hoach_tap',
            'phien_ban_ke_hoach_tap_id' => 'phien_ban_ke_hoach_tap',
            'ngay_trong_ke_hoach_id' => 'ngay_trong_ke_hoach',
            'bai_tap_id' => 'bai_tap',
            'phien_tap_id' => 'phien_tap',
            'bai_tap_trong_ke_hoach_id' => 'bai_tap_trong_ke_hoach',
            'buoi_tap_du_kien_id' => 'buoi_tap_du_kien',
            'hoi_thoai_id' => 'hoi_thoai',
            'tin_nhan_id' => 'tin_nhan',
            'hoi_thoai_tro_ly_id' => 'hoi_thoai_tro_ly',
            'tin_nhan_dau_vao_id' => 'tin_nhan_tro_ly',
            'yeu_cau_tro_ly_id' => 'yeu_cau_tro_ly',
        ];
        $insert = static function (string $table, int $logicalId, array $row) use ($db, &$ids, $foreignTables): int {
            foreach ($foreignTables as $column => $parentTable) {
                if (! array_key_exists($column, $row) || $row[$column] === null) {
                    continue;
                }
                $logicalParentId = (int) $row[$column];
                if (! isset($ids[$parentTable][$logicalParentId])) {
                    throw new \LogicException("Fixture alias {$parentTable}:{$logicalParentId} is not defined before {$table}.{$column}.");
                }
                $row[$column] = $ids[$parentTable][$logicalParentId];
            }
            $actualId = (int) $db->table($table)->insertGetId($row);
            $ids[$table][$logicalId] = $actualId;

            return $actualId;
        };

        $insert('chi_nhanh', 1, ['ma_chi_nhanh' => 'CN-TEST', 'ten_chi_nhanh' => 'Chi nhanh test', 'dia_chi' => 'Dia chi test', 'so_dien_thoai' => null, 'mui_gio' => 'Asia/Bangkok', 'trang_thai' => 'HOAT_DONG', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('nguoi_dung', 1, ['chi_nhanh_id' => 1, 'ho_ten' => 'Member test', 'thu_dien_tu' => 'member@example.test', 'so_dien_thoai' => null, 'mat_khau_bam' => 'hash', 'anh_dai_dien' => null, 'xac_minh_thu_luc' => null, 'trang_thai' => 'HOAT_DONG', 'dang_nhap_gan_nhat_luc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('nguoi_dung', 2, ['chi_nhanh_id' => 1, 'ho_ten' => 'PT test', 'thu_dien_tu' => 'pt@example.test', 'so_dien_thoai' => null, 'mat_khau_bam' => 'hash', 'anh_dai_dien' => null, 'xac_minh_thu_luc' => null, 'trang_thai' => 'HOAT_DONG', 'dang_nhap_gan_nhat_luc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('ho_so_hoi_vien', 1, ['nguoi_dung_id' => 1, 'ma_hoi_vien' => 'HV-TEST', 'ngay_sinh' => null, 'gioi_tinh' => null, 'muc_tieu_tap_luyen' => null, 'kinh_nghiem_tap_luyen' => null, 'so_ngay_tap_mong_muon' => 3, 'thoi_luong_moi_buoi_phut' => 60, 'phien_ban_ho_so' => 1, 'moc_thay_doi_ke_hoach' => 0, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('ho_so_huan_luyen_vien', 2, ['nguoi_dung_id' => 2, 'ma_huan_luyen_vien' => 'PT-TEST', 'gioi_thieu' => null, 'chuyen_mon' => null, 'trang_thai' => 'HOAT_DONG', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);

        $insert('goi_tap', 1, ['chi_nhanh_id' => 1, 'ma_goi' => 'GOI-TEST', 'ten_goi' => 'Goi test', 'gia' => 100000, 'thoi_han_ngay' => 30, 'mo_ta' => null, 'trang_thai' => 'DANG_BAN', 'phien_ban_cau_hinh' => 1, 'nguoi_tao_id' => 1, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('quyen_loi_goi_tap', 1, ['goi_tap_id' => 1, 'cho_phep_vao_phong_tap' => 1, 'cho_phep_tro_ly_tap_luyen' => 1, 'gioi_han_luot_tro_ly' => 10, 'cho_phep_tro_chuyen_huan_luyen_vien' => 1, 'so_buoi_huan_luyen_vien' => 4, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('don_mua_goi', 1, ['hoi_vien_id' => 1, 'goi_tap_id' => 1, 'ma_don' => 'DON-TEST-1', 'ma_yeu_cau' => '11111111-1111-4111-8111-111111111111', 'so_tien_phai_thu' => 100000, 'don_vi_tien' => 'VND', 'trang_thai' => 'CHO_THANH_TOAN', 'chot_gia_luc' => $now, 'het_han_thanh_toan_luc' => '2026-08-30 10:00:00.000000', 'thanh_toan_luc' => null, 'huy_luc' => null, 'ly_do_huy' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('lan_thanh_toan', 1, ['don_mua_goi_id' => 1, 'so_lan' => 1, 'ma_kenh_thanh_toan' => 'payos-test', 'ma_don_cong_thanh_toan' => 1000001, 'ma_lien_ket_thanh_toan' => null, 'duong_dan_thanh_toan' => null, 'so_tien_yeu_cau' => 100000, 'don_vi_tien' => 'VND', 'trang_thai' => 'CHO_THANH_TOAN', 'ma_tham_chieu_duoc_chap_nhan' => null, 'so_tien_da_nhan' => null, 'thanh_toan_luc' => null, 'xac_nhan_luc' => null, 'het_han_luc' => '2026-08-30 10:00:00.000000', 'ma_loi' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('dang_ky_goi_tap', 1, ['hoi_vien_id' => 1, 'chi_nhanh_id' => 1, 'trang_thai' => 'CHO_KICH_HOAT', 'lan_su_dung_dau_tien_id' => null, 'ngay_bat_dau' => null, 'ket_thuc_ghi_nhan_luc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('ky_han_hoi_vien', 1, ['hoi_vien_id' => 1, 'don_mua_goi_id' => 1, 'lan_thanh_toan_id' => 1, 'dang_ky_goi_tap_id' => 1, 'so_thu_tu' => 1, 'trang_thai' => 'DANG_HOAT_DONG', 'ten_goi' => 'Goi test', 'phien_ban_goi' => 1, 'gia_da_mua' => 100000, 'thoi_han_ngay' => 30, 'cho_phep_vao_phong_tap' => 1, 'cho_phep_tro_ly_tap_luyen' => 1, 'gioi_han_luot_tro_ly' => 10, 'cho_phep_tro_chuyen_huan_luyen_vien' => 1, 'so_buoi_huan_luyen_vien' => 4, 'so_buoi_huan_luyen_vien_da_dung' => 0, 'so_luot_tro_ly_da_dung' => 0, 'so_luot_tro_ly_giu_cho' => 0, 'mua_luc' => $now, 'ngay_bat_dau' => $now, 'ngay_ket_thuc' => '2026-09-28 10:00:00.000000', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('su_dung_quyen_loi', 1, ['hoi_vien_id' => 1, 'ky_han_hoi_vien_id' => 1, 'nguoi_thuc_hien_id' => 1, 'loai_su_dung' => 'VAO_PHONG_TAP', 'ma_hanh_dong' => '22222222-2222-4222-8222-222222222222', 'chap_nhan_luc' => $now, 'ngay_tao' => $now]);
        $insert('ma_vao_phong_tap', 1, ['hoi_vien_id' => 1, 'chi_nhanh_id' => 1, 'ma_bam_bi_mat' => str_repeat('a', 64), 'phat_hanh_luc' => $now, 'het_han_luc' => '2026-08-30 10:00:00.000000', 'thu_hoi_luc' => null, 'da_su_dung_luc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('lich_su_vao_phong_tap', 1, ['ma_vao_phong_tap_id' => 1, 'hoi_vien_id' => 1, 'chi_nhanh_id' => 1, 'ky_han_hoi_vien_id' => 1, 'su_dung_quyen_loi_id' => 1, 'nguoi_xac_nhan_id' => 1, 'vao_phong_luc' => $now, 'ngay_tao' => $now]);

        $insert('phan_cong_huan_luyen_vien', 1, ['hoi_vien_id' => 1, 'huan_luyen_vien_id' => 2, 'nguoi_phan_cong_id' => 1, 'ngay_bat_dau' => $now, 'ngay_ket_thuc' => null, 'ly_do_ket_thuc' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('hoi_thoai', 1, ['hoi_vien_id' => 1, 'huan_luyen_vien_id' => 2, 'phan_cong_huan_luyen_vien_id' => 1, 'so_thu_tu_cuoi' => 1, 'hoi_vien_doc_den_so' => 0, 'huan_luyen_vien_doc_den_so' => 0, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('tin_nhan', 1, ['hoi_thoai_id' => 1, 'so_thu_tu' => 1, 'nguoi_gui_id' => 1, 'phan_cong_huan_luyen_vien_id' => 1, 'su_dung_quyen_loi_id' => null, 'ma_tin_nhan_phia_gui' => '33333333-3333-4333-8333-333333333333', 'noi_dung' => 'Xin chao PT', 'gui_luc' => $now, 'hoi_vien_id' => 1, 'huan_luyen_vien_id' => 2, 'ngay_tao' => $now]);

        $insert('bai_tap', 1, ['ma_bai_tap' => 'BAI-TEST', 'ten_bai_tap' => 'Push up', 'do_kho' => 'CO BAN', 'huong_dan' => 'Huong dan', 'duong_dan_hinh_anh' => null, 'duong_dan_video' => null, 'thong_tin_bo_sung' => null, 'phien_ban_noi_dung' => 1, 'trang_thai' => 'HOAT_DONG', 'nguoi_tao_id' => 1, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('ke_hoach_tap', 1, ['hoi_vien_id' => 1, 'ten_ke_hoach' => 'Ke hoach test', 'trang_thai' => 'DANG_SU_DUNG', 'phien_ban_hien_tai_id' => null, 'nguoi_tao_id' => 1, 'ma_lan_tao' => '44444444-4444-4444-8444-444444444444', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('phien_ban_ke_hoach_tap', 1, ['ke_hoach_tap_id' => 1, 'so_phien_ban' => 1, 'phien_ban_truoc_id' => null, 'giao_an_mau_id' => null, 'ten_giao_an_da_chon' => null, 'de_xuat_ke_hoach_tap_id' => null, 'nguon_tao' => 'HOI_VIEN', 'nguoi_tao_id' => 1, 'muc_tieu' => 'Tang suc ben', 'ap_dung_tu_ngay' => '2026-08-29', 'ly_do_thay_doi' => null, 'ma_bam_noi_dung' => str_repeat('b', 64), 'ngay_tao' => $now]);
        $insert('ngay_trong_ke_hoach', 1, ['phien_ban_ke_hoach_tap_id' => 1, 'ma_ngay_logic' => '55555555-5555-4555-8555-555555555555', 'so_thu_tu' => 1, 'thu_trong_tuan' => 2, 'ten_ngay' => 'Thu 2', 'thoi_luong_du_kien_phut' => 60, 'ngay_tao' => $now]);
        $insert('bai_tap_trong_ke_hoach', 1, ['ngay_trong_ke_hoach_id' => 1, 'bai_tap_id' => 1, 'ma_bai_logic' => '66666666-6666-4666-8666-666666666666', 'so_thu_tu' => 1, 'ten_bai_tap' => 'Push up', 'huong_dan' => 'Huong dan', 'dung_cu_yeu_cau' => '[]', 'so_hiep_muc_tieu' => 3, 'so_lan_lap_toi_thieu' => 8, 'so_lan_lap_toi_da' => 12, 'khoi_luong_muc_tieu_kg' => null, 'thoi_gian_nghi_giay' => 60, 'ghi_chu' => null, 'ngay_tao' => $now]);
        $insert('buoi_tap_du_kien', 1, ['hoi_vien_id' => 1, 'ke_hoach_tap_id' => 1, 'phien_ban_ke_hoach_tap_id' => 1, 'ngay_trong_ke_hoach_id' => 1, 'ma_buoi_logic' => '77777777-7777-4777-8777-777777777777', 'ngay_tap' => '2026-09-01', 'gio_bat_dau_du_kien' => null, 'gio_ket_thuc_du_kien' => null, 'trang_thai' => 'CHUA_TAP', 'thay_the_buoi_tap_id' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('phien_tap', 1, ['hoi_vien_id' => 1, 'buoi_tap_du_kien_id' => 1, 'ma_lan_bat_dau' => '88888888-8888-4888-8888-888888888888', 'ten_buoi_tap' => 'Push day', 'bat_dau_luc' => $now, 'ket_thuc_luc' => null, 'trang_thai' => 'DANG_TAP', 'ghi_chu' => null, 'phien_ban_du_lieu' => 1, 'ma_lan_hoan_thanh' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('bai_tap_trong_phien', 1, ['phien_tap_id' => 1, 'bai_tap_id' => 1, 'bai_tap_trong_ke_hoach_id' => 1, 'ma_bai_thuc_hien' => '99999999-9999-4999-8999-999999999999', 'so_thu_tu' => 1, 'ten_bai_tap' => 'Push up', 'huong_dan' => 'Huong dan', 'dung_cu_su_dung' => '[]', 'so_hiep_du_kien' => 3, 'so_lan_lap_du_kien_toi_thieu' => 8, 'so_lan_lap_du_kien_toi_da' => 12, 'khoi_luong_du_kien_kg' => null, 'thoi_gian_nghi_du_kien_giay' => 60, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);

        $insert('hoi_thoai_tro_ly', 1, ['hoi_vien_id' => 1, 'tieu_de' => 'Tro ly', 'trang_thai' => 'DANG_MO', 'so_thu_tu_cuoi' => 1, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('tin_nhan_tro_ly', 1, ['hoi_thoai_tro_ly_id' => 1, 'so_thu_tu' => 1, 'nguon_tin' => 'HOI_VIEN', 'ma_tin_nhan_phia_gui' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'yeu_cau_tro_ly_id' => null, 'noi_dung' => 'Tao lich tap', 'gui_luc' => $now, 'ngay_tao' => $now]);
        $insert('yeu_cau_tro_ly', 1, ['hoi_vien_id' => 1, 'hoi_thoai_tro_ly_id' => 1, 'tin_nhan_dau_vao_id' => 1, 'ky_han_hoi_vien_id' => null, 'su_dung_quyen_loi_id' => null, 'ma_yeu_cau' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'loai_yeu_cau' => 'TAO_KE_HOACH', 'yeu_cau_chuan_hoa' => null, 'ngu_canh_da_chot' => null, 'phien_ban_quy_tac' => null, 'trang_thai' => 'TIEP_NHAN', 'trang_thai_han_muc' => 'KHONG_AP_DUNG', 'bat_dau_xu_ly_luc' => null, 'hoan_tat_luc' => null, 'ma_loi' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);
        $insert('tin_nhan_tro_ly', 2, ['hoi_thoai_tro_ly_id' => 1, 'so_thu_tu' => 2, 'nguon_tin' => 'TRO_LY', 'ma_tin_nhan_phia_gui' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'yeu_cau_tro_ly_id' => 1, 'noi_dung' => 'Ket qua', 'gui_luc' => $now, 'ngay_tao' => $now]);

        $insert('nhat_ky_he_thong', 1, ['nguoi_thuc_hien_id' => 1, 'loai_tac_nhan' => 'NGUOI_DUNG', 'hanh_dong' => 'TAO', 'loai_doi_tuong' => 'bai_tap', 'dinh_danh_doi_tuong' => 1, 'khoa_tuong_quan' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'du_lieu_truoc' => null, 'du_lieu_sau' => null, 'ket_qua' => 'THANH_CONG', 'thuc_hien_luc' => $now, 'ngay_tao' => $now]);
        $insert('yeu_cau_chong_lap', 1, ['nguoi_dung_id' => 1, 'pham_vi' => 'model_test', 'khoa_yeu_cau' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'ma_bam_noi_dung' => str_repeat('f', 64), 'trang_thai' => 'DANG_XU_LY', 'ma_phan_hoi' => null, 'ket_qua_da_loc' => null, 'het_han_luc' => '2026-08-30 10:00:00.000000', 'ngay_tao' => $now, 'ngay_cap_nhat' => $now]);

        return $ids;
    }
}
