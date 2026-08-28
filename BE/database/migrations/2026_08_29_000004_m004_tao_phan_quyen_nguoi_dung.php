<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `phan_quyen_nguoi_dung` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` BIGINT UNSIGNED NOT NULL,
  `vai_tro_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_cap_id` BIGINT UNSIGNED NULL,
  `cap_luc` DATETIME(6) NOT NULL,
  `thu_hoi_luc` DATETIME(6) NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b04_01` UNIQUE (`nguoi_dung_id`, `vai_tro_id`),
  KEY `chi_muc_b04_01` (`vai_tro_id`, `thu_hoi_luc`),
  CONSTRAINT `kiem_tra_b04_01` CHECK (thu_hoi_luc IS NULL OR thu_hoi_luc >= cap_luc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `phan_quyen_nguoi_dung`');
    }
};
