<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `the_truy_cap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` BIGINT UNSIGNED NOT NULL,
  `ten_thiet_bi` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ma_bam_the` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `pham_vi_truy_cap` JSON NOT NULL,
  `su_dung_gan_nhat_luc` DATETIME(6) NULL,
  `het_han_luc` DATETIME(6) NOT NULL,
  `thu_hoi_luc` DATETIME(6) NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b10_01` UNIQUE (`ma_bam_the`),
  KEY `chi_muc_b10_01` (`nguoi_dung_id`, `thu_hoi_luc`, `het_han_luc`),
  CONSTRAINT `kiem_tra_b10_01` CHECK (het_han_luc > ngay_tao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `the_truy_cap`');
    }
};
