<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `bai_tap_nhom_co` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `nhom_co_id` BIGINT UNSIGNED NOT NULL,
  `vai_tro_nhom_co` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b29_01` UNIQUE (`bai_tap_id`, `nhom_co_id`),
  CONSTRAINT `kiem_tra_b29_01` CHECK (vai_tro_nhom_co IN ('CHINH', 'PHU'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `bai_tap_nhom_co`');
    }
};
