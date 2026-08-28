<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `bai_tap_trong_phien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phien_tap_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_trong_ke_hoach_id` BIGINT UNSIGNED NULL,
  `ma_bai_thuc_hien` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_thu_tu` SMALLINT UNSIGNED NOT NULL,
  `ten_bai_tap` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `huong_dan` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dung_cu_su_dung` JSON NOT NULL,
  `so_hiep_du_kien` SMALLINT UNSIGNED NOT NULL,
  `so_lan_lap_du_kien_toi_thieu` SMALLINT UNSIGNED NOT NULL,
  `so_lan_lap_du_kien_toi_da` SMALLINT UNSIGNED NOT NULL,
  `khoi_luong_du_kien_kg` DECIMAL(7,2) NULL,
  `thoi_gian_nghi_du_kien_giay` SMALLINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b39_01` UNIQUE (`phien_tap_id`, `ma_bai_thuc_hien`),
  CONSTRAINT `duy_nhat_b39_02` UNIQUE (`phien_tap_id`, `so_thu_tu`),
  KEY `chi_muc_b39_01` (`bai_tap_id`, `phien_tap_id`),
  CONSTRAINT `kiem_tra_b39_01` CHECK (so_thu_tu > 0 AND so_hiep_du_kien > 0 AND so_lan_lap_du_kien_toi_thieu > 0 AND so_lan_lap_du_kien_toi_da > 0 AND so_lan_lap_du_kien_toi_thieu <= so_lan_lap_du_kien_toi_da),
  CONSTRAINT `kiem_tra_b39_02` CHECK (khoi_luong_du_kien_kg IS NULL OR khoi_luong_du_kien_kg >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `bai_tap_trong_phien`');
    }
};
