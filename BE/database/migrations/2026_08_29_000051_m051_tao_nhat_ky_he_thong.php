<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `nhat_ky_he_thong` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_thuc_hien_id` BIGINT UNSIGNED NULL,
  `loai_tac_nhan` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `hanh_dong` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `loai_doi_tuong` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `dinh_danh_doi_tuong` BIGINT UNSIGNED NULL,
  `khoa_tuong_quan` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `du_lieu_truoc` JSON NULL,
  `du_lieu_sau` JSON NULL,
  `ket_qua` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `thuc_hien_luc` DATETIME(6) NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_muc_b51_01` (`loai_doi_tuong`, `dinh_danh_doi_tuong`, `thuc_hien_luc`),
  KEY `chi_muc_b51_02` (`khoa_tuong_quan`),
  CONSTRAINT `kiem_tra_b51_01` CHECK (loai_tac_nhan IN ('NGUOI_DUNG', 'HE_THONG', 'CONG_THANH_TOAN')),
  CONSTRAINT `kiem_tra_b51_02` CHECK (ket_qua IN ('THANH_CONG', 'BI_TU_CHOI', 'THAT_BAI'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `nhat_ky_he_thong`');
    }
};
