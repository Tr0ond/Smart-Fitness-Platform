<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `dang_ky_goi_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `chi_nhanh_id` BIGINT UNSIGNED NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `lan_su_dung_dau_tien_id` BIGINT UNSIGNED NULL,
  `ngay_bat_dau` DATETIME(6) NULL,
  `ket_thuc_ghi_nhan_luc` DATETIME(6) NULL,
  `hoi_vien_chua_ket_thuc_id` BIGINT UNSIGNED AS (CASE WHEN trang_thai IN ('CHO_KICH_HOAT','DANG_HOAT_DONG') THEN hoi_vien_id ELSE NULL END) VIRTUAL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b17_01` UNIQUE (`lan_su_dung_dau_tien_id`),
  CONSTRAINT `duy_nhat_b17_02` UNIQUE (`hoi_vien_chua_ket_thuc_id`),
  CONSTRAINT `duy_nhat_b17_03` UNIQUE (`id`, `hoi_vien_id`),
  KEY `chi_muc_b17_01` (`hoi_vien_id`, `ngay_tao`, `id`),
  CONSTRAINT `kiem_tra_b17_01` CHECK (trang_thai IN ('CHO_KICH_HOAT', 'DANG_HOAT_DONG', 'HET_HAN', 'HUY')),
  CONSTRAINT `kiem_tra_b17_02` CHECK (((ngay_bat_dau IS NULL AND lan_su_dung_dau_tien_id IS NULL) OR (ngay_bat_dau IS NOT NULL AND lan_su_dung_dau_tien_id IS NOT NULL)) AND (trang_thai <> 'CHO_KICH_HOAT' OR ngay_bat_dau IS NULL) AND (trang_thai NOT IN ('DANG_HOAT_DONG','HET_HAN') OR ngay_bat_dau IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `dang_ky_goi_tap`');
    }
};
