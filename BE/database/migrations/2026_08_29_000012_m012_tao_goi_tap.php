<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `goi_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chi_nhanh_id` BIGINT UNSIGNED NOT NULL,
  `ma_goi` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ten_goi` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gia` DECIMAL(15,0) NOT NULL,
  `thoi_han_ngay` SMALLINT UNSIGNED NOT NULL,
  `mo_ta` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `phien_ban_cau_hinh` INT UNSIGNED NOT NULL,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b12_01` UNIQUE (`chi_nhanh_id`, `ma_goi`),
  KEY `chi_muc_b12_01` (`chi_nhanh_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b12_01` CHECK (trang_thai IN ('DANG_BAN', 'NGUNG_BAN')),
  CONSTRAINT `kiem_tra_b12_02` CHECK (gia > 0 AND thoi_han_ngay > 0 AND phien_ban_cau_hinh >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `goi_tap`');
    }
};
