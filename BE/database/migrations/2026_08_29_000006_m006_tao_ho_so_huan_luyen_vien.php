<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `ho_so_huan_luyen_vien` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nguoi_dung_id` BIGINT UNSIGNED NOT NULL,
  `ma_huan_luyen_vien` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `gioi_thieu` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `chuyen_mon` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b06_01` UNIQUE (`nguoi_dung_id`),
  CONSTRAINT `duy_nhat_b06_02` UNIQUE (`ma_huan_luyen_vien`),
  CONSTRAINT `kiem_tra_b06_01` CHECK (trang_thai IN ('HOAT_DONG', 'NGUNG_NHAN_PHAN_CONG'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `ho_so_huan_luyen_vien`');
    }
};
