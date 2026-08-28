<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `tin_nhan` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_thoai_id` BIGINT UNSIGNED NOT NULL,
  `so_thu_tu` BIGINT UNSIGNED NOT NULL,
  `nguoi_gui_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `su_dung_quyen_loi_id` BIGINT UNSIGNED NULL,
  `ma_tin_nhan_phia_gui` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `noi_dung` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gui_luc` DATETIME(6) NOT NULL,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `huan_luyen_vien_id` BIGINT UNSIGNED NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b42_01` UNIQUE (`hoi_thoai_id`, `so_thu_tu`),
  CONSTRAINT `duy_nhat_b42_02` UNIQUE (`hoi_thoai_id`, `nguoi_gui_id`, `ma_tin_nhan_phia_gui`),
  CONSTRAINT `duy_nhat_b42_03` UNIQUE (`su_dung_quyen_loi_id`),
  KEY `chi_muc_b42_01` (`hoi_thoai_id`, `so_thu_tu`),
  CONSTRAINT `kiem_tra_b42_01` CHECK (so_thu_tu > 0),
  CONSTRAINT `kiem_tra_b42_02` CHECK (CHAR_LENGTH(noi_dung) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `tin_nhan`');
    }
};
