<?php

namespace Database\Seeders;

use App\Models\ChiNhanh;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChiNhanhSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        ChiNhanh::query()->updateOrCreate(
            ['ma_chi_nhanh' => 'CHI_NHANH_MVP'],
            [
                'ten_chi_nhanh' => 'Smart Fitness - Co so MVP',
                'dia_chi' => 'Ha Noi, Viet Nam',
                'so_dien_thoai' => null,
                'mui_gio' => 'Asia/Ho_Chi_Minh',
                'trang_thai' => 'HOAT_DONG',
            ],
        );
    }
}
