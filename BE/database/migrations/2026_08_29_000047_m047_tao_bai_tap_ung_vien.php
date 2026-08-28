<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `bai_tap_ung_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `yeu_cau_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `bai_tap_id` BIGINT UNSIGNED NOT NULL,
  `phien_ban_noi_dung` INT UNSIGNED NOT NULL,
  `du_lieu_da_chot` JSON NOT NULL,
  `ly_do_phu_hop` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b47_01` UNIQUE (`yeu_cau_tro_ly_id`, `bai_tap_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `bai_tap_ung_vien`');
    }
};
