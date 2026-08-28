<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `buoi_tap_du_kien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `ke_hoach_tap_id` BIGINT UNSIGNED NOT NULL,
  `phien_ban_ke_hoach_tap_id` BIGINT UNSIGNED NOT NULL,
  `ngay_trong_ke_hoach_id` BIGINT UNSIGNED NOT NULL,
  `ma_buoi_logic` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_tap` DATE NOT NULL,
  `gio_bat_dau_du_kien` TIME NULL,
  `gio_ket_thuc_du_kien` TIME NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `thay_the_buoi_tap_id` BIGINT UNSIGNED NULL,
  `ma_buoi_con_hieu_luc` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin AS (CASE WHEN trang_thai IN ('CHUA_TAP','DANG_TAP','HOAN_THANH','BO_QUA') THEN ma_buoi_logic ELSE NULL END) VIRTUAL,
  `ngay_tap_con_hieu_luc` DATE AS (CASE WHEN trang_thai IN ('CHUA_TAP','DANG_TAP','HOAN_THANH','BO_QUA') THEN ngay_tap ELSE NULL END) VIRTUAL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b37_01` UNIQUE (`phien_ban_ke_hoach_tap_id`, `ma_buoi_logic`),
  CONSTRAINT `duy_nhat_b37_02` UNIQUE (`thay_the_buoi_tap_id`),
  CONSTRAINT `duy_nhat_b37_03` UNIQUE (`hoi_vien_id`, `ma_buoi_con_hieu_luc`),
  CONSTRAINT `duy_nhat_b37_04` UNIQUE (`id`, `hoi_vien_id`),
  CONSTRAINT `duy_nhat_b37_05` UNIQUE (`hoi_vien_id`, `ngay_tap_con_hieu_luc`),
  KEY `chi_muc_b37_01` (`hoi_vien_id`, `ngay_tap`, `trang_thai`),
  KEY `chi_muc_b37_02` (`ke_hoach_tap_id`, `phien_ban_ke_hoach_tap_id`),
  CONSTRAINT `kiem_tra_b37_01` CHECK (trang_thai IN ('CHUA_TAP', 'DANG_TAP', 'HOAN_THANH', 'BO_QUA', 'HUY', 'DA_THAY_THE')),
  CONSTRAINT `kiem_tra_b37_02` CHECK ((gio_bat_dau_du_kien IS NULL AND gio_ket_thuc_du_kien IS NULL) OR (gio_bat_dau_du_kien IS NOT NULL AND gio_ket_thuc_du_kien IS NOT NULL AND gio_bat_dau_du_kien < gio_ket_thuc_du_kien))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `buoi_tap_du_kien`');
    }
};
