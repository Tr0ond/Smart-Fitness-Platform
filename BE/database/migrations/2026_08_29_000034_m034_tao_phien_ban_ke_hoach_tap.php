<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `phien_ban_ke_hoach_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ke_hoach_tap_id` BIGINT UNSIGNED NOT NULL,
  `so_phien_ban` INT UNSIGNED NOT NULL,
  `phien_ban_truoc_id` BIGINT UNSIGNED NULL,
  `giao_an_mau_id` BIGINT UNSIGNED NULL,
  `ten_giao_an_da_chon` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `de_xuat_ke_hoach_tap_id` BIGINT UNSIGNED NULL,
  `nguon_tao` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `muc_tieu` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ap_dung_tu_ngay` DATE NOT NULL,
  `ly_do_thay_doi` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ma_bam_noi_dung` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b34_01` UNIQUE (`ke_hoach_tap_id`, `so_phien_ban`),
  CONSTRAINT `duy_nhat_b34_02` UNIQUE (`de_xuat_ke_hoach_tap_id`),
  CONSTRAINT `duy_nhat_b34_03` UNIQUE (`id`, `ke_hoach_tap_id`),
  KEY `chi_muc_b34_01` (`ke_hoach_tap_id`, `ngay_tao`),
  CONSTRAINT `kiem_tra_b34_01` CHECK (nguon_tao IN ('HOI_VIEN', 'HUAN_LUYEN_VIEN', 'TRO_LY')),
  CONSTRAINT `kiem_tra_b34_02` CHECK (so_phien_ban >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `phien_ban_ke_hoach_tap`');
    }
};
