<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `su_kien_thanh_toan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lan_thanh_toan_id` BIGINT UNSIGNED NULL,
  `ma_kenh_thanh_toan` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_don_cong_thanh_toan` BIGINT UNSIGNED NULL,
  `ma_lien_ket_thanh_toan` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ma_tham_chieu` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `so_tien` DECIMAL(15,0) NULL,
  `don_vi_tien` CHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ma_ket_qua` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `chu_ky_hop_le` BOOLEAN NOT NULL,
  `khoa_chong_lap` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ma_bam_noi_dung` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `du_lieu_da_loc` JSON NULL,
  `trang_thai_xu_ly` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_lan_nhan` INT UNSIGNED NOT NULL,
  `nhan_dau_luc` DATETIME(6) NOT NULL,
  `nhan_cuoi_luc` DATETIME(6) NOT NULL,
  `xu_ly_luc` DATETIME(6) NULL,
  `ly_do` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b16_01` UNIQUE (`khoa_chong_lap`),
  KEY `chi_muc_b16_01` (`trang_thai_xu_ly`, `nhan_dau_luc`),
  KEY `chi_muc_b16_02` (`lan_thanh_toan_id`, `nhan_dau_luc`),
  CONSTRAINT `kiem_tra_b16_01` CHECK (trang_thai_xu_ly IN ('CHO_XU_LY', 'DA_XU_LY', 'BI_TU_CHOI', 'CAN_DOI_SOAT', 'CHO_THU_LAI')),
  CONSTRAINT `kiem_tra_b16_02` CHECK (so_lan_nhan >= 1 AND chu_ky_hop_le IN (0,1)),
  CONSTRAINT `kiem_tra_b16_03` CHECK (chu_ky_hop_le = 1 OR khoa_chong_lap IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `su_kien_thanh_toan`');
    }
};
