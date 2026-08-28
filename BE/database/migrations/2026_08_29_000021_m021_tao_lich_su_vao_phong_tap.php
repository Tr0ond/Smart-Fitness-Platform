<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `lich_su_vao_phong_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_vao_phong_tap_id` BIGINT UNSIGNED NOT NULL,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `chi_nhanh_id` BIGINT UNSIGNED NOT NULL,
  `ky_han_hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `su_dung_quyen_loi_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_xac_nhan_id` BIGINT UNSIGNED NOT NULL,
  `vao_phong_luc` DATETIME(6) NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b21_01` UNIQUE (`ma_vao_phong_tap_id`),
  CONSTRAINT `duy_nhat_b21_02` UNIQUE (`su_dung_quyen_loi_id`),
  KEY `chi_muc_b21_01` (`hoi_vien_id`, `vao_phong_luc`, `id`),
  KEY `chi_muc_b21_02` (`chi_nhanh_id`, `vao_phong_luc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `lich_su_vao_phong_tap`');
    }
};
