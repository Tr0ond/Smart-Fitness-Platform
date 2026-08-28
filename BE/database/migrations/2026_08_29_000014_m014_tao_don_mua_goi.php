<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `don_mua_goi` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `goi_tap_id` BIGINT UNSIGNED NOT NULL,
  `ma_don` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_yeu_cau` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_tien_phai_thu` DECIMAL(15,0) NOT NULL,
  `don_vi_tien` CHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `chot_gia_luc` DATETIME(6) NOT NULL,
  `het_han_thanh_toan_luc` DATETIME(6) NOT NULL,
  `thanh_toan_luc` DATETIME(6) NULL,
  `huy_luc` DATETIME(6) NULL,
  `ly_do_huy` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b14_01` UNIQUE (`ma_don`),
  CONSTRAINT `duy_nhat_b14_02` UNIQUE (`hoi_vien_id`, `ma_yeu_cau`),
  CONSTRAINT `duy_nhat_b14_03` UNIQUE (`id`, `hoi_vien_id`),
  KEY `chi_muc_b14_01` (`hoi_vien_id`, `ngay_tao`, `id`),
  KEY `chi_muc_b14_02` (`trang_thai`, `het_han_thanh_toan_luc`),
  CONSTRAINT `kiem_tra_b14_01` CHECK (trang_thai IN ('CHO_THANH_TOAN', 'DA_THANH_TOAN', 'HET_HAN', 'HUY', 'CAN_DOI_SOAT')),
  CONSTRAINT `kiem_tra_b14_02` CHECK (so_tien_phai_thu > 0 AND don_vi_tien = 'VND'),
  CONSTRAINT `kiem_tra_b14_03` CHECK (het_han_thanh_toan_luc > chot_gia_luc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `don_mua_goi`');
    }
};
