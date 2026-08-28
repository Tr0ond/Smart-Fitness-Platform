<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `phan_cong_huan_luyen_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_phan_cong_id` BIGINT UNSIGNED NOT NULL,
  `ngay_bat_dau` DATETIME(6) NOT NULL,
  `ngay_ket_thuc` DATETIME(6) NULL,
  `ly_do_ket_thuc` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `hoi_vien_dang_phan_cong_id` BIGINT UNSIGNED AS (CASE WHEN ngay_ket_thuc IS NULL THEN hoi_vien_id ELSE NULL END) VIRTUAL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b22_01` UNIQUE (`hoi_vien_id`, `huan_luyen_vien_id`, `ngay_bat_dau`),
  CONSTRAINT `duy_nhat_b22_02` UNIQUE (`id`, `hoi_vien_id`, `huan_luyen_vien_id`),
  CONSTRAINT `duy_nhat_b22_03` UNIQUE (`id`, `hoi_vien_id`),
  CONSTRAINT `duy_nhat_b22_04` UNIQUE (`hoi_vien_dang_phan_cong_id`),
  KEY `chi_muc_b22_01` (`hoi_vien_id`, `ngay_bat_dau`, `ngay_ket_thuc`),
  KEY `chi_muc_b22_02` (`huan_luyen_vien_id`, `ngay_bat_dau`, `ngay_ket_thuc`),
  CONSTRAINT `kiem_tra_b22_01` CHECK (ngay_ket_thuc IS NULL OR ngay_ket_thuc > ngay_bat_dau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `phan_cong_huan_luyen_vien`');
    }
};
