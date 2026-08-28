<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `su_dung_quyen_loi` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `ky_han_hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `nguoi_thuc_hien_id` BIGINT UNSIGNED NOT NULL,
  `loai_su_dung` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_hanh_dong` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `chap_nhan_luc` DATETIME(6) NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b19_01` UNIQUE (`hoi_vien_id`, `loai_su_dung`, `ma_hanh_dong`),
  CONSTRAINT `duy_nhat_b19_02` UNIQUE (`id`, `hoi_vien_id`, `ky_han_hoi_vien_id`),
  CONSTRAINT `duy_nhat_b19_03` UNIQUE (`id`, `hoi_vien_id`),
  KEY `chi_muc_b19_01` (`ky_han_hoi_vien_id`, `loai_su_dung`, `chap_nhan_luc`),
  CONSTRAINT `kiem_tra_b19_01` CHECK (loai_su_dung IN ('VAO_PHONG_TAP', 'YEU_CAU_TRO_LY', 'BUOI_HUAN_LUYEN', 'TRO_CHUYEN_HUAN_LUYEN'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `su_dung_quyen_loi`');
    }
};
