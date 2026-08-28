<?php

namespace Tests\Feature;

use App\Models\BaiTap;
use App\Models\BaiTapDungCu;
use App\Models\BaiTapNhomCo;
use Database\Seeders\ExerciseDatasetSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederImplementationTest extends TestCase
{
    private function requireIsolatedDatabase(): void
    {
        $database = (string) config('database.connections.mysql.database');
        $this->assertSame('mysql', (string) config('database.default'));
        $this->assertMatchesRegularExpression(
            '/^smart_fitness_.*test/i',
            $database,
            'Seeder tests require a dedicated smart_fitness_*test database; refusing to run against a development database.',
        );

        // A test process may start with a freshly migrated schema or with a
        // schema prepared by the suite runner. Provision the deterministic
        // baseline in the isolated database when it is absent so test order
        // never decides whether these read-only assertions have fixtures.
        if (!DB::table('bai_tap')->exists()) {
            Artisan::call('db:seed', ['--force' => true]);
        }
    }

    public function test_seeder_inventory_and_master_data(): void
    {
        $this->requireIsolatedDatabase();
        foreach ([
            'ChiNhanhSeeder',
            'VaiTroSeeder',
            'GoiTapSeeder',
            'QuyenLoiGoiTapSeeder',
            'DemoNguoiDungSeeder',
            'ExerciseDatasetSeeder',
        ] as $seeder) {
            $this->assertTrue(class_exists('Database\\Seeders\\' . $seeder));
        }
        $this->assertSame(1, DB::table('chi_nhanh')->count());
        $this->assertSame(4, DB::table('vai_tro')->count());
    }

    public function test_demo_accounts_roles_and_profiles_are_minimal(): void
    {
        $this->requireIsolatedDatabase();
        $this->assertSame(8, DB::table('nguoi_dung')->count());
        $this->assertSame(8, DB::table('phan_quyen_nguoi_dung')->count());
        $this->assertSame(4, DB::table('ho_so_hoi_vien')->count());
        $this->assertSame(2, DB::table('ho_so_huan_luyen_vien')->count());
        $this->assertSame(0, DB::table('dang_ky_goi_tap')->count());
        $this->assertSame(0, DB::table('phien_tap')->count());
        $this->assertSame(0, DB::table('tin_nhan')->count());
    }

    public function test_external_dataset_mapping_and_relations(): void
    {
        $this->requireIsolatedDatabase();
        $source = dirname(base_path()) . DIRECTORY_SEPARATOR . '.tmp' . DIRECTORY_SEPARATOR . 'exercises-dataset';
        $records = json_decode((string) file_get_contents($source . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'exercises.json'), true, 512, JSON_THROW_ON_ERROR);
        $normalizer = app(ExerciseDatasetSeeder::class);
        $equipment = [];
        $muscles = [];
        foreach ($records as $record) {
            $equipment[$normalizer->normalizeForLookup((string) ($record['equipment'] ?? ''))] = true;
            foreach ([(string) ($record['target'] ?? ''), (string) ($record['muscle_group'] ?? ''), ...array_map('strval', (array) ($record['secondary_muscles'] ?? []))] as $muscle) {
                if (trim($muscle) !== '') {
                    $muscles[$normalizer->normalizeForLookup($muscle)] = true;
                }
            }
        }
        $this->assertSame(count($records), DB::table('bai_tap')->count());
        $this->assertSame(count($equipment), DB::table('dung_cu')->count());
        $this->assertSame(count($muscles), DB::table('nhom_co')->count());
        $this->assertSame(count($records), DB::table('bai_tap_dung_cu')->count());
        $this->assertGreaterThanOrEqual(count($records), DB::table('bai_tap_nhom_co')->count());

        $exercise = BaiTap::query()->where('ma_bai_tap', 'EX_0001')->firstOrFail();
        $this->assertSame('0001', $exercise->thong_tin_bo_sung['external_id']);
        $this->assertSame(1, $exercise->phien_ban_noi_dung);
        $this->assertSame('HOAT_DONG', $exercise->trang_thai);
    }

    public function test_media_paths_are_copied_without_binary_database_storage(): void
    {
        $this->requireIsolatedDatabase();
        $source = dirname(base_path()) . DIRECTORY_SEPARATOR . '.tmp' . DIRECTORY_SEPARATOR . 'exercises-dataset' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'exercises.json';
        $expected = count(json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame($expected, iterator_count(new \FilesystemIterator(storage_path('app/public/exercises/images'))));
        $this->assertSame($expected, iterator_count(new \FilesystemIterator(storage_path('app/public/exercises/videos'))));
        $exercise = BaiTap::query()->where('ma_bai_tap', 'EX_0001')->firstOrFail();
        $this->assertStringEndsWith('.jpg', $exercise->duong_dan_hinh_anh);
        $this->assertStringEndsWith('.gif', $exercise->duong_dan_video);
        $this->assertStringNotContainsString('base64', strtolower((string) $exercise->thong_tin_bo_sung['attribution']));
    }

    public function test_missing_media_keeps_exercise_importable(): void
    {
        $this->requireIsolatedDatabase();
        $sourcePath = app(ExerciseDatasetSeeder::class)->sourcePath();
        $record = json_decode((string) file_get_contents($sourcePath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'exercises.json'), true, 512, JSON_THROW_ON_ERROR)[0];
        $record['id'] = 'TEST_MISSING_MEDIA';
        $record['image'] = 'images/does-not-exist.jpg';
        $record['gif_url'] = 'videos/does-not-exist.gif';

        DB::beginTransaction();
        try {
            app(ExerciseDatasetSeeder::class)->importFromRecords([$record], (int) DB::table('nguoi_dung')->where('thu_dien_tu', 'dev.admin@smartfitness.local')->value('id'), $sourcePath);
            $this->assertDatabaseHas('bai_tap', ['ma_bai_tap' => 'EX_TEST_MISSING_MEDIA']);
        } finally {
            DB::rollBack();
        }
        $this->assertDatabaseMissing('bai_tap', ['ma_bai_tap' => 'EX_TEST_MISSING_MEDIA']);
    }

    public function test_second_seed_has_zero_row_delta(): void
    {
        $this->requireIsolatedDatabase();
        $before = [];
        foreach (['chi_nhanh', 'vai_tro', 'nguoi_dung', 'phan_quyen_nguoi_dung', 'dung_cu', 'nhom_co', 'bai_tap', 'bai_tap_dung_cu', 'bai_tap_nhom_co'] as $table) {
            $before[$table] = DB::table($table)->count();
        }
        Artisan::call('db:seed', ['--class' => ExerciseDatasetSeeder::class, '--force' => true]);
        foreach ($before as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), $table . ' changed on rerun');
        }
    }

    public function test_generated_columns_are_never_written_by_seeders(): void
    {
        $this->requireIsolatedDatabase();
        $this->assertNotContains('hoi_vien_chua_ket_thuc_id', (new \App\Models\DangKyGoiTap())->getFillable());
        $this->assertNotContains('ma_buoi_con_hieu_luc', (new \App\Models\BuoiTapDuKien())->getFillable());
        $this->assertNotContains('ngay_tap_con_hieu_luc', (new \App\Models\BuoiTapDuKien())->getFillable());
    }

    public function test_junction_unique_constraint_rejects_duplicate_relation(): void
    {
        $this->requireIsolatedDatabase();
        $relation = BaiTapDungCu::query()->firstOrFail();
        $this->expectException(QueryException::class);
        DB::table('bai_tap_dung_cu')->insert([
            'bai_tap_id' => $relation->bai_tap_id,
            'dung_cu_id' => $relation->dung_cu_id,
            'ngay_tao' => now(),
            'ngay_cap_nhat' => now(),
        ]);
    }

    public function test_muscle_roles_are_limited_to_approved_values(): void
    {
        $this->requireIsolatedDatabase();
        $this->assertSame(0, DB::table('bai_tap_nhom_co')->whereNotIn('vai_tro_nhom_co', ['CHINH', 'PHU'])->count());
    }
}
