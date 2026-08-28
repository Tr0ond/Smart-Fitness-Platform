<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `dung_cu` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ma_dung_cu` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ten_dung_cu` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mo_ta` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b25_01` UNIQUE (`ma_dung_cu`),
  CONSTRAINT `kiem_tra_b25_01` CHECK (trang_thai IN ('HOAT_DONG', 'NGUNG_SU_DUNG'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `dung_cu`');
    }
};
