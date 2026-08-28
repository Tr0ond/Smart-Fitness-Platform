<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ma_vao_phong_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `chi_nhanh_id` BIGINT UNSIGNED NOT NULL,
  `ma_bam_bi_mat` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `phat_hanh_luc` DATETIME(6) NOT NULL,
  `het_han_luc` DATETIME(6) NOT NULL,
  `thu_hoi_luc` DATETIME(6) NULL,
  `da_su_dung_luc` DATETIME(6) NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b20_01` UNIQUE (`ma_bam_bi_mat`),
  CONSTRAINT `duy_nhat_b20_02` UNIQUE (`id`, `hoi_vien_id`, `chi_nhanh_id`),
  KEY `chi_muc_b20_01` (`hoi_vien_id`, `het_han_luc`),
  CONSTRAINT `kiem_tra_b20_01` CHECK (het_han_luc > phat_hanh_luc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ma_vao_phong_tap`');
    }
};
