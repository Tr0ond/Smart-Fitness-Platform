<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ky_han_hoi_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `don_mua_goi_id` BIGINT UNSIGNED NOT NULL,
  `lan_thanh_toan_id` BIGINT UNSIGNED NULL,
  `dang_ky_goi_tap_id` BIGINT UNSIGNED NULL,
  `so_thu_tu` INT UNSIGNED NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ten_goi` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phien_ban_goi` INT UNSIGNED NOT NULL,
  `gia_da_mua` DECIMAL(15,0) NOT NULL,
  `thoi_han_ngay` SMALLINT UNSIGNED NOT NULL,
  `cho_phep_vao_phong_tap` BOOLEAN NOT NULL,
  `cho_phep_tro_ly_tap_luyen` BOOLEAN NOT NULL,
  `gioi_han_luot_tro_ly` INT UNSIGNED NULL,
  `cho_phep_tro_chuyen_huan_luyen_vien` BOOLEAN NOT NULL,
  `so_buoi_huan_luyen_vien` SMALLINT UNSIGNED NOT NULL,
  `so_buoi_huan_luyen_vien_da_dung` SMALLINT UNSIGNED NOT NULL,
  `so_luot_tro_ly_da_dung` INT UNSIGNED NOT NULL,
  `so_luot_tro_ly_giu_cho` INT UNSIGNED NOT NULL,
  `mua_luc` DATETIME(6) NULL,
  `ngay_bat_dau` DATETIME(6) NULL,
  `ngay_ket_thuc` DATETIME(6) NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b18_01` UNIQUE (`don_mua_goi_id`),
  CONSTRAINT `duy_nhat_b18_02` UNIQUE (`lan_thanh_toan_id`),
  CONSTRAINT `duy_nhat_b18_03` UNIQUE (`dang_ky_goi_tap_id`, `so_thu_tu`),
  CONSTRAINT `duy_nhat_b18_04` UNIQUE (`id`, `hoi_vien_id`),
  KEY `chi_muc_b18_01` (`hoi_vien_id`, `ngay_bat_dau`, `ngay_ket_thuc`),
  KEY `chi_muc_b18_02` (`dang_ky_goi_tap_id`, `so_thu_tu`),
  CONSTRAINT `kiem_tra_b18_01` CHECK (trang_thai IN ('CHO_THANH_TOAN', 'CHO_KICH_HOAT', 'CHO_DEN_LUOT', 'DANG_HOAT_DONG', 'HET_HAN', 'HUY')),
  CONSTRAINT `kiem_tra_b18_02` CHECK (cho_phep_vao_phong_tap IN (0,1) AND cho_phep_tro_ly_tap_luyen IN (0,1) AND cho_phep_tro_chuyen_huan_luyen_vien IN (0,1)),
  CONSTRAINT `kiem_tra_b18_03` CHECK ((cho_phep_tro_ly_tap_luyen = 0 AND gioi_han_luot_tro_ly IS NOT NULL AND gioi_han_luot_tro_ly = 0) OR (cho_phep_tro_ly_tap_luyen = 1 AND (gioi_han_luot_tro_ly IS NULL OR gioi_han_luot_tro_ly > 0))),
  CONSTRAINT `kiem_tra_b18_04` CHECK ((cho_phep_vao_phong_tap = 1 OR cho_phep_tro_ly_tap_luyen = 1 OR cho_phep_tro_chuyen_huan_luyen_vien = 1 OR so_buoi_huan_luyen_vien > 0) AND so_buoi_huan_luyen_vien >= 0),
  CONSTRAINT `kiem_tra_b18_05` CHECK (thoi_han_ngay > 0 AND gia_da_mua > 0 AND (so_thu_tu IS NULL OR so_thu_tu > 0)),
  CONSTRAINT `kiem_tra_b18_06` CHECK ((ngay_bat_dau IS NULL AND ngay_ket_thuc IS NULL) OR (ngay_bat_dau IS NOT NULL AND ngay_ket_thuc IS NOT NULL AND DATE_ADD(ngay_bat_dau, INTERVAL thoi_han_ngay DAY) IS NOT NULL AND ngay_ket_thuc = DATE_ADD(ngay_bat_dau, INTERVAL thoi_han_ngay DAY))),
  CONSTRAINT `kiem_tra_b18_07` CHECK (so_buoi_huan_luyen_vien_da_dung >= 0 AND so_buoi_huan_luyen_vien_da_dung <= so_buoi_huan_luyen_vien),
  CONSTRAINT `kiem_tra_b18_08` CHECK (so_luot_tro_ly_da_dung >= 0 AND so_luot_tro_ly_giu_cho >= 0 AND (gioi_han_luot_tro_ly IS NULL OR so_luot_tro_ly_da_dung + so_luot_tro_ly_giu_cho <= gioi_han_luot_tro_ly) AND (cho_phep_tro_ly_tap_luyen = 1 OR (so_luot_tro_ly_da_dung = 0 AND so_luot_tro_ly_giu_cho = 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ky_han_hoi_vien`');
    }
};
