<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `giao_an_mau` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_giao_an` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ten_giao_an` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mo_ta` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `muc_tieu` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trinh_do` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `so_buoi_moi_tuan` TINYINT UNSIGNED NOT NULL,
  `phien_ban_noi_dung` INT UNSIGNED NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b30_01` UNIQUE (`ma_giao_an`),
  KEY `chi_muc_b30_01` (`trang_thai`, `muc_tieu`, `trinh_do`),
  CONSTRAINT `kiem_tra_b30_01` CHECK (trang_thai IN ('HOAT_DONG', 'NGUNG_SU_DUNG')),
  CONSTRAINT `kiem_tra_b30_02` CHECK (so_buoi_moi_tuan BETWEEN 1 AND 7 AND phien_ban_noi_dung >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `giao_an_mau`');
    }
};
