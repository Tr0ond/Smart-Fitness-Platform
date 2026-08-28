<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `lan_thanh_toan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `don_mua_goi_id` BIGINT UNSIGNED NOT NULL,
  `so_lan` SMALLINT UNSIGNED NOT NULL,
  `ma_kenh_thanh_toan` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_don_cong_thanh_toan` BIGINT UNSIGNED NOT NULL,
  `ma_lien_ket_thanh_toan` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `duong_dan_thanh_toan` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `so_tien_yeu_cau` DECIMAL(15,0) NOT NULL,
  `don_vi_tien` CHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_tham_chieu_duoc_chap_nhan` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `so_tien_da_nhan` DECIMAL(15,0) NULL,
  `thanh_toan_luc` DATETIME(6) NULL,
  `xac_nhan_luc` DATETIME(6) NULL,
  `het_han_luc` DATETIME(6) NOT NULL,
  `ma_loi` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b15_01` UNIQUE (`don_mua_goi_id`, `so_lan`),
  CONSTRAINT `duy_nhat_b15_02` UNIQUE (`ma_kenh_thanh_toan`, `ma_don_cong_thanh_toan`),
  CONSTRAINT `duy_nhat_b15_03` UNIQUE (`ma_kenh_thanh_toan`, `ma_lien_ket_thanh_toan`),
  CONSTRAINT `duy_nhat_b15_04` UNIQUE (`ma_kenh_thanh_toan`, `ma_tham_chieu_duoc_chap_nhan`),
  CONSTRAINT `duy_nhat_b15_05` UNIQUE (`id`, `don_mua_goi_id`),
  KEY `chi_muc_b15_01` (`don_mua_goi_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b15_01` CHECK (trang_thai IN ('DANG_TAO', 'CHO_THANH_TOAN', 'THANH_CONG', 'THAT_BAI', 'HUY', 'HET_HAN', 'CAN_DOI_SOAT')),
  CONSTRAINT `kiem_tra_b15_02` CHECK (so_lan > 0 AND so_tien_yeu_cau > 0 AND (so_tien_da_nhan IS NULL OR so_tien_da_nhan >= 0) AND ma_don_cong_thanh_toan BETWEEN 1 AND 9007199254740991),
  CONSTRAINT `kiem_tra_b15_03` CHECK (don_vi_tien = 'VND')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `lan_thanh_toan`');
    }
};
