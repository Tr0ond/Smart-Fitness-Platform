<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `tin_nhan_tro_ly` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_thoai_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `so_thu_tu` BIGINT UNSIGNED NOT NULL,
  `nguon_tin` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_tin_nhan_phia_gui` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `yeu_cau_tro_ly_id` BIGINT UNSIGNED NULL,
  `noi_dung` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gui_luc` DATETIME(6) NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b45_01` UNIQUE (`hoi_thoai_tro_ly_id`, `so_thu_tu`),
  CONSTRAINT `duy_nhat_b45_02` UNIQUE (`hoi_thoai_tro_ly_id`, `ma_tin_nhan_phia_gui`),
  CONSTRAINT `duy_nhat_b45_03` UNIQUE (`id`, `hoi_thoai_tro_ly_id`),
  KEY `chi_muc_b45_01` (`hoi_thoai_tro_ly_id`, `so_thu_tu`),
  CONSTRAINT `kiem_tra_b45_01` CHECK (nguon_tin IN ('HOI_VIEN', 'TRO_LY')),
  CONSTRAINT `kiem_tra_b45_02` CHECK (so_thu_tu > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `tin_nhan_tro_ly`');
    }
};
