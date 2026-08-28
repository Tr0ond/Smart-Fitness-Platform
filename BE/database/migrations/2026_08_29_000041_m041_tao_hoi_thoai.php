<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `hoi_thoai` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `so_thu_tu_cuoi` BIGINT UNSIGNED NOT NULL,
  `hoi_vien_doc_den_so` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_doc_den_so` BIGINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b41_01` UNIQUE (`phan_cong_huan_luyen_vien_id`),
  CONSTRAINT `duy_nhat_b41_02` UNIQUE (`id`, `phan_cong_huan_luyen_vien_id`),
  KEY `chi_muc_b41_01` (`hoi_vien_id`, `ngay_cap_nhat`),
  KEY `chi_muc_b41_02` (`huan_luyen_vien_id`, `ngay_cap_nhat`),
  CONSTRAINT `kiem_tra_b41_01` CHECK (hoi_vien_doc_den_so >= 0 AND huan_luyen_vien_doc_den_so >= 0 AND hoi_vien_doc_den_so <= so_thu_tu_cuoi AND huan_luyen_vien_doc_den_so <= so_thu_tu_cuoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `hoi_thoai`');
    }
};
