<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `quyen_loi_goi_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `goi_tap_id` BIGINT UNSIGNED NOT NULL,
  `cho_phep_vao_phong_tap` BOOLEAN NOT NULL,
  `cho_phep_tro_ly_tap_luyen` BOOLEAN NOT NULL,
  `gioi_han_luot_tro_ly` INT UNSIGNED NULL,
  `cho_phep_tro_chuyen_huan_luyen_vien` BOOLEAN NOT NULL,
  `so_buoi_huan_luyen_vien` SMALLINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b13_01` UNIQUE (`goi_tap_id`),
  CONSTRAINT `kiem_tra_b13_01` CHECK (cho_phep_vao_phong_tap IN (0,1) AND cho_phep_tro_ly_tap_luyen IN (0,1) AND cho_phep_tro_chuyen_huan_luyen_vien IN (0,1)),
  CONSTRAINT `kiem_tra_b13_02` CHECK ((cho_phep_tro_ly_tap_luyen = 0 AND gioi_han_luot_tro_ly IS NOT NULL AND gioi_han_luot_tro_ly = 0) OR (cho_phep_tro_ly_tap_luyen = 1 AND (gioi_han_luot_tro_ly IS NULL OR gioi_han_luot_tro_ly > 0))),
  CONSTRAINT `kiem_tra_b13_03` CHECK ((cho_phep_vao_phong_tap = 1 OR cho_phep_tro_ly_tap_luyen = 1 OR cho_phep_tro_chuyen_huan_luyen_vien = 1 OR so_buoi_huan_luyen_vien > 0) AND so_buoi_huan_luyen_vien >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `quyen_loi_goi_tap`');
    }
};
