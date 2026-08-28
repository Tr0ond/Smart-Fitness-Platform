<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `yeu_cau_dat_lai_mat_khau` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` BIGINT UNSIGNED NOT NULL,
  `ma_bam_xac_nhan` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `het_han_luc` DATETIME(6) NOT NULL,
  `da_su_dung_luc` DATETIME(6) NULL,
  `thu_hoi_luc` DATETIME(6) NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b11_01` UNIQUE (`ma_bam_xac_nhan`),
  KEY `chi_muc_b11_01` (`nguoi_dung_id`, `het_han_luc`),
  CONSTRAINT `kiem_tra_b11_01` CHECK (het_han_luc > ngay_tao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `yeu_cau_dat_lai_mat_khau`');
    }
};
