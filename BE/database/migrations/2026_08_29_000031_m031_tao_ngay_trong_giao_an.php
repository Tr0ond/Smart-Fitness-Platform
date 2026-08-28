<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ngay_trong_giao_an` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `giao_an_mau_id` BIGINT UNSIGNED NOT NULL,
  `so_thu_tu` TINYINT UNSIGNED NOT NULL,
  `ten_ngay` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thoi_luong_du_kien_phut` SMALLINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b31_01` UNIQUE (`giao_an_mau_id`, `so_thu_tu`),
  CONSTRAINT `kiem_tra_b31_01` CHECK (so_thu_tu > 0 AND thoi_luong_du_kien_phut > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ngay_trong_giao_an`');
    }
};
