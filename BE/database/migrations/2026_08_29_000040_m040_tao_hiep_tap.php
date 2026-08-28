<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `hiep_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bai_tap_trong_phien_id` BIGINT UNSIGNED NOT NULL,
  `so_thu_tu` SMALLINT UNSIGNED NOT NULL,
  `ma_hiep_thuc_hien` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_lan_lap` SMALLINT UNSIGNED NOT NULL,
  `khoi_luong_kg` DECIMAL(7,2) NULL,
  `thoi_gian_nghi_thuc_te_giay` SMALLINT UNSIGNED NULL,
  `hoan_thanh_luc` DATETIME(6) NOT NULL,
  `phien_ban_du_lieu` INT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b40_01` UNIQUE (`bai_tap_trong_phien_id`, `so_thu_tu`),
  CONSTRAINT `duy_nhat_b40_02` UNIQUE (`bai_tap_trong_phien_id`, `ma_hiep_thuc_hien`),
  CONSTRAINT `kiem_tra_b40_01` CHECK (khoi_luong_kg IS NULL OR khoi_luong_kg >= 0),
  CONSTRAINT `kiem_tra_b40_02` CHECK (so_thu_tu > 0 AND phien_ban_du_lieu >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `hiep_tap`');
    }
};
