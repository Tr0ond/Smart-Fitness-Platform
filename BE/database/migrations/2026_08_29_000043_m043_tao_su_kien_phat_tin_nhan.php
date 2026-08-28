<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `su_kien_phat_tin_nhan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tin_nhan_id` BIGINT UNSIGNED NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_lan_thu` INT UNSIGNED NOT NULL,
  `thu_lai_luc` DATETIME(6) NULL,
  `phat_luc` DATETIME(6) NULL,
  `loi_gan_nhat` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b43_01` UNIQUE (`tin_nhan_id`),
  KEY `chi_muc_b43_01` (`trang_thai`, `thu_lai_luc`),
  CONSTRAINT `kiem_tra_b43_01` CHECK (trang_thai IN ('CHO_PHAT', 'DA_PHAT', 'CHO_THU_LAI'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `su_kien_phat_tin_nhan`');
    }
};
