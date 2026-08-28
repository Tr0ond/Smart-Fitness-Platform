<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `bai_tap_trong_giao_an` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ngay_trong_giao_an_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `so_thu_tu` SMALLINT UNSIGNED NOT NULL,
  `so_hiep_muc_tieu` SMALLINT UNSIGNED NOT NULL,
  `so_lan_lap_toi_thieu` SMALLINT UNSIGNED NOT NULL,
  `so_lan_lap_toi_da` SMALLINT UNSIGNED NOT NULL,
  `thoi_gian_nghi_giay` SMALLINT UNSIGNED NOT NULL,
  `ghi_chu` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b32_01` UNIQUE (`ngay_trong_giao_an_id`, `so_thu_tu`),
  CONSTRAINT `kiem_tra_b32_01` CHECK (so_thu_tu > 0 AND so_hiep_muc_tieu > 0 AND so_lan_lap_toi_thieu > 0 AND so_lan_lap_toi_da > 0 AND so_lan_lap_toi_thieu <= so_lan_lap_toi_da)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `bai_tap_trong_giao_an`');
    }
};
