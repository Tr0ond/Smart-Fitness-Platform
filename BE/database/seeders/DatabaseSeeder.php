<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed catalog an toàn ở mọi môi trường; demo chỉ dành cho local/testing. */
    public function run(): void
    {
        $this->call([
            ChiNhanhSeeder::class,
            VaiTroSeeder::class,
            GoiTapSeeder::class,
            QuyenLoiGoiTapSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call([
                DemoNguoiDungSeeder::class,
                // Dataset hiện cần actor demo vì bai_tap.nguoi_tao_id là bắt buộc.
                ExerciseDatasetSeeder::class,
            ]);
        }
    }
}
