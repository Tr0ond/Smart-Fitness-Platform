<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `bai_tap_trong_ke_hoach` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ngay_trong_ke_hoach_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `ma_bai_logic` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_thu_tu` SMALLINT UNSIGNED NOT NULL,
  `ten_bai_tap` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `huong_dan` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dung_cu_yeu_cau` JSON NOT NULL,
  `so_hiep_muc_tieu` SMALLINT UNSIGNED NOT NULL,
  `so_lan_lap_toi_thieu` SMALLINT UNSIGNED NOT NULL,
  `so_lan_lap_toi_da` SMALLINT UNSIGNED NOT NULL,
  `khoi_luong_muc_tieu_kg` DECIMAL(7,2) NULL,
  `thoi_gian_nghi_giay` SMALLINT UNSIGNED NOT NULL,
  `ghi_chu` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b36_01` UNIQUE (`ngay_trong_ke_hoach_id`, `so_thu_tu`),
  CONSTRAINT `duy_nhat_b36_02` UNIQUE (`ngay_trong_ke_hoach_id`, `ma_bai_logic`),
  CONSTRAINT `kiem_tra_b36_01` CHECK (so_thu_tu > 0 AND so_hiep_muc_tieu > 0 AND so_lan_lap_toi_thieu > 0 AND so_lan_lap_toi_da > 0 AND so_lan_lap_toi_thieu <= so_lan_lap_toi_da),
  CONSTRAINT `kiem_tra_b36_02` CHECK (khoi_luong_muc_tieu_kg IS NULL OR khoi_luong_muc_tieu_kg >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `bai_tap_trong_ke_hoach`');
    }
};
