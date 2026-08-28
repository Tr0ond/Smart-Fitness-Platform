<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `phien_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `buoi_tap_du_kien_id` BIGINT UNSIGNED NOT NULL,
  `ma_lan_bat_dau` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ten_buoi_tap` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bat_dau_luc` DATETIME(6) NOT NULL,
  `ket_thuc_luc` DATETIME(6) NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ghi_chu` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `phien_ban_du_lieu` INT UNSIGNED NOT NULL,
  `ma_lan_hoan_thanh` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b38_01` UNIQUE (`buoi_tap_du_kien_id`),
  CONSTRAINT `duy_nhat_b38_02` UNIQUE (`hoi_vien_id`, `ma_lan_bat_dau`),
  CONSTRAINT `duy_nhat_b38_03` UNIQUE (`hoi_vien_id`, `ma_lan_hoan_thanh`),
  CONSTRAINT `duy_nhat_b38_04` UNIQUE (`id`, `hoi_vien_id`),
  KEY `chi_muc_b38_01` (`hoi_vien_id`, `bat_dau_luc`, `id`),
  KEY `chi_muc_b38_02` (`hoi_vien_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b38_01` CHECK (trang_thai IN ('DANG_TAP', 'HOAN_THANH', 'HUY')),
  CONSTRAINT `kiem_tra_b38_02` CHECK (ket_thuc_luc IS NULL OR ket_thuc_luc >= bat_dau_luc),
  CONSTRAINT `kiem_tra_b38_03` CHECK (trang_thai <> 'HOAN_THANH' OR (ket_thuc_luc IS NOT NULL AND ma_lan_hoan_thanh IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `phien_tap`');
    }
};
