<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ngay_trong_ke_hoach` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phien_ban_ke_hoach_tap_id` BIGINT UNSIGNED NOT NULL,
  `ma_ngay_logic` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_thu_tu` TINYINT UNSIGNED NOT NULL,
  `thu_trong_tuan` TINYINT UNSIGNED NOT NULL,
  `ten_ngay` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thoi_luong_du_kien_phut` SMALLINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b35_01` UNIQUE (`phien_ban_ke_hoach_tap_id`, `ma_ngay_logic`),
  CONSTRAINT `duy_nhat_b35_02` UNIQUE (`phien_ban_ke_hoach_tap_id`, `so_thu_tu`),
  CONSTRAINT `duy_nhat_b35_03` UNIQUE (`id`, `phien_ban_ke_hoach_tap_id`),
  CONSTRAINT `kiem_tra_b35_01` CHECK (thu_trong_tuan BETWEEN 2 AND 8 AND so_thu_tu > 0 AND thoi_luong_du_kien_phut > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ngay_trong_ke_hoach`');
    }
};
