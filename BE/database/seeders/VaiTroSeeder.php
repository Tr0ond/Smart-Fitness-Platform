<?php

namespace Database\Seeders;

use App\Models\VaiTro;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class VaiTroSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = [
            'MEMBER' => ['ten_vai_tro' => 'Hoi vien', 'mo_ta' => 'Su dung ung dung di dong va Workout ca nhan.'],
            'PT' => ['ten_vai_tro' => 'Huan luyen vien', 'mo_ta' => 'Tu van va quan ly hoc vien duoc phan cong.'],
            'RECEPTIONIST' => ['ten_vai_tro' => 'Le tan', 'mo_ta' => 'Ho tro tiep don va van hanh tai quay.'],
            'ADMIN' => ['ten_vai_tro' => 'Quan tri vien', 'mo_ta' => 'Quan tri cau hinh va danh muc he thong.'],
        ];

        foreach ($roles as $maVaiTro => $duLieu) {
            VaiTro::query()->updateOrCreate(
                ['ma_vai_tro' => $maVaiTro],
                $duLieu,
            );
        }
    }
}
