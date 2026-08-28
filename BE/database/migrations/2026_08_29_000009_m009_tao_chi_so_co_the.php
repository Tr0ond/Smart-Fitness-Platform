<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `chi_so_co_the` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `do_luc` DATETIME(6) NOT NULL,
  `can_nang_kg` DECIMAL(6,2) NOT NULL,
  `chieu_cao_cm` DECIMAL(5,2) NOT NULL,
  `vong_eo_cm` DECIMAL(5,2) NULL,
  `ghi_chu` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ma_lan_ghi` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b09_01` UNIQUE (`hoi_vien_id`, `ma_lan_ghi`),
  KEY `chi_muc_b09_01` (`hoi_vien_id`, `do_luc`, `id`),
  CONSTRAINT `kiem_tra_b09_01` CHECK (can_nang_kg > 0 AND chieu_cao_cm > 0 AND (vong_eo_cm IS NULL OR vong_eo_cm > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `chi_so_co_the`');
    }
};
