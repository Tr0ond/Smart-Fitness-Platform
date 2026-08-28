<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ghi_chu_huan_luyen` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `ke_hoach_tap_id` BIGINT UNSIGNED NULL,
  `phien_tap_id` BIGINT UNSIGNED NULL,
  `noi_dung` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chi_muc_b24_01` (`hoi_vien_id`, `ngay_tao`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ghi_chu_huan_luyen`');
    }
};
