<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ho_so_hoi_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` BIGINT UNSIGNED NOT NULL,
  `ma_hoi_vien` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_sinh` DATE NULL,
  `gioi_tinh` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `muc_tieu_tap_luyen` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `kinh_nghiem_tap_luyen` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `so_ngay_tap_mong_muon` TINYINT UNSIGNED NULL,
  `thoi_luong_moi_buoi_phut` SMALLINT UNSIGNED NULL,
  `phien_ban_ho_so` INT UNSIGNED NOT NULL,
  `moc_thay_doi_ke_hoach` INT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b05_01` UNIQUE (`nguoi_dung_id`),
  CONSTRAINT `duy_nhat_b05_02` UNIQUE (`ma_hoi_vien`),
  CONSTRAINT `kiem_tra_b05_01` CHECK ((so_ngay_tap_mong_muon IS NULL OR so_ngay_tap_mong_muon BETWEEN 1 AND 7) AND (thoi_luong_moi_buoi_phut IS NULL OR thoi_luong_moi_buoi_phut > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ho_so_hoi_vien`');
    }
};
