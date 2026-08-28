<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `nguoi_dung` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `chi_nhanh_id` BIGINT UNSIGNED NOT NULL,
  `ho_ten` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thu_dien_tu` VARCHAR(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_dien_thoai` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `mat_khau_bam` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `anh_dai_dien` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `xac_minh_thu_luc` DATETIME(6) NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `dang_nhap_gan_nhat_luc` DATETIME(6) NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b02_01` UNIQUE (`thu_dien_tu`),
  KEY `chi_muc_b02_01` (`chi_nhanh_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b02_01` CHECK (trang_thai IN ('HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `nguoi_dung`');
    }
};
