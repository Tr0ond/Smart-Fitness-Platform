<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `yeu_cau_chong_lap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` BIGINT UNSIGNED NOT NULL,
  `pham_vi` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `khoa_yeu_cau` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_bam_noi_dung` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_phan_hoi` SMALLINT UNSIGNED NULL,
  `ket_qua_da_loc` JSON NULL,
  `het_han_luc` DATETIME(6) NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b52_01` UNIQUE (`nguoi_dung_id`, `pham_vi`, `khoa_yeu_cau`),
  KEY `chi_muc_b52_01` (`trang_thai`, `ngay_tao`),
  KEY `chi_muc_b52_02` (`het_han_luc`),
  CONSTRAINT `kiem_tra_b52_01` CHECK (trang_thai IN ('DANG_XU_LY', 'DA_HOAN_TAT', 'THAT_BAI')),
  CONSTRAINT `kiem_tra_b52_02` CHECK (het_han_luc > ngay_tao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `yeu_cau_chong_lap`');
    }
};
