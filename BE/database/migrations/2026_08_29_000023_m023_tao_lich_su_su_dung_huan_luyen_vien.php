<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `lich_su_su_dung_huan_luyen_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `ky_han_hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `su_dung_quyen_loi_id` BIGINT UNSIGNED NOT NULL,
  `ma_buoi_huan_luyen` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `so_luot_su_dung` TINYINT UNSIGNED NOT NULL,
  `hoan_thanh_luc` DATETIME(6) NOT NULL,
  `xac_nhan_luc` DATETIME(6) NOT NULL,
  `nguon_thao_tac` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ghi_chu` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b23_01` UNIQUE (`hoi_vien_id`, `huan_luyen_vien_id`, `ma_buoi_huan_luyen`),
  CONSTRAINT `duy_nhat_b23_02` UNIQUE (`su_dung_quyen_loi_id`),
  KEY `chi_muc_b23_01` (`ky_han_hoi_vien_id`, `xac_nhan_luc`),
  KEY `chi_muc_b23_02` (`huan_luyen_vien_id`, `xac_nhan_luc`),
  CONSTRAINT `kiem_tra_b23_01` CHECK (trang_thai = 'HOAN_THANH' AND so_luot_su_dung = 1 AND nguon_thao_tac = 'WEB_HUAN_LUYEN_VIEN'),
  CONSTRAINT `kiem_tra_b23_02` CHECK (hoan_thanh_luc <= xac_nhan_luc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `lich_su_su_dung_huan_luyen_vien`');
    }
};
