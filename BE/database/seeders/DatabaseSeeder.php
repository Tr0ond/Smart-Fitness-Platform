<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed dữ liệu danh mục và tài khoản demo cho môi trường phát triển.
     * Các bảng vận hành/lịch sử vẫn để trống; ExerciseDatasetSeeder có thể
     * chạy riêng bằng `php artisan db:seed --class=ExerciseDatasetSeeder`.
     */
    public function run(): void
    {
        $this->call([
            ChiNhanhSeeder::class,
            VaiTroSeeder::class,
            // Tài khoản demo phải có trước vì bai_tap.nguoi_tao_id là bắt buộc.
            DemoNguoiDungSeeder::class,
            GoiTapSeeder::class,
            QuyenLoiGoiTapSeeder::class,
            ExerciseDatasetSeeder::class,
        ]);
    }
}
