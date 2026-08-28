<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap` ADD CONSTRAINT `khoa_ngoai_don_b27_01` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_dung_cu` ADD CONSTRAINT `khoa_ngoai_don_b28_01` FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_dung_cu` ADD CONSTRAINT `khoa_ngoai_don_b28_02` FOREIGN KEY (`dung_cu_id`) REFERENCES `dung_cu` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_nhom_co` ADD CONSTRAINT `khoa_ngoai_don_b29_01` FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_nhom_co` ADD CONSTRAINT `khoa_ngoai_don_b29_02` FOREIGN KEY (`nhom_co_id`) REFERENCES `nhom_co` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `giao_an_mau` ADD CONSTRAINT `khoa_ngoai_don_b30_01` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ngay_trong_giao_an` ADD CONSTRAINT `khoa_ngoai_don_b31_01` FOREIGN KEY (`giao_an_mau_id`) REFERENCES `giao_an_mau` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_giao_an` ADD CONSTRAINT `khoa_ngoai_don_b32_01` FOREIGN KEY (`ngay_trong_giao_an_id`) REFERENCES `ngay_trong_giao_an` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_giao_an` ADD CONSTRAINT `khoa_ngoai_don_b32_02` FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `bai_tap_trong_giao_an` DROP FOREIGN KEY `khoa_ngoai_don_b32_02`');
    DB::statement('ALTER TABLE `bai_tap_trong_giao_an` DROP FOREIGN KEY `khoa_ngoai_don_b32_01`');
    DB::statement('ALTER TABLE `ngay_trong_giao_an` DROP FOREIGN KEY `khoa_ngoai_don_b31_01`');
    DB::statement('ALTER TABLE `giao_an_mau` DROP FOREIGN KEY `khoa_ngoai_don_b30_01`');
    DB::statement('ALTER TABLE `bai_tap_nhom_co` DROP FOREIGN KEY `khoa_ngoai_don_b29_02`');
    DB::statement('ALTER TABLE `bai_tap_nhom_co` DROP FOREIGN KEY `khoa_ngoai_don_b29_01`');
    DB::statement('ALTER TABLE `bai_tap_dung_cu` DROP FOREIGN KEY `khoa_ngoai_don_b28_02`');
    DB::statement('ALTER TABLE `bai_tap_dung_cu` DROP FOREIGN KEY `khoa_ngoai_don_b28_01`');
    DB::statement('ALTER TABLE `bai_tap` DROP FOREIGN KEY `khoa_ngoai_don_b27_01`');
    }
};
