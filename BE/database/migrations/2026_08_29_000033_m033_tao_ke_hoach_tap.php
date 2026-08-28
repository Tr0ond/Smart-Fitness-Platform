<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ke_hoach_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `ten_ke_hoach` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `phien_ban_hien_tai_id` BIGINT UNSIGNED NULL,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `ma_lan_tao` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `hoi_vien_dang_su_dung_id` BIGINT UNSIGNED AS (CASE WHEN trang_thai = 'DANG_SU_DUNG' THEN hoi_vien_id ELSE NULL END) VIRTUAL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b33_01` UNIQUE (`hoi_vien_id`, `ma_lan_tao`),
  CONSTRAINT `duy_nhat_b33_02` UNIQUE (`id`, `hoi_vien_id`),
  CONSTRAINT `duy_nhat_b33_03` UNIQUE (`phien_ban_hien_tai_id`),
  CONSTRAINT `duy_nhat_b33_04` UNIQUE (`hoi_vien_dang_su_dung_id`),
  KEY `chi_muc_b33_01` (`hoi_vien_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b33_01` CHECK (trang_thai IN ('DANG_SU_DUNG', 'LUU_TRU'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ke_hoach_tap`');
    }
};
