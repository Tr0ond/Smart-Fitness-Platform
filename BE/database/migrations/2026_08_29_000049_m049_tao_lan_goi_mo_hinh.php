<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `lan_goi_mo_hinh` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `yeu_cau_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `so_lan` SMALLINT UNSIGNED NOT NULL,
  `nha_cung_cap` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ten_mo_hinh` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_yeu_cau_nha_cung_cap` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `phien_ban_mau_lenh` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `phien_ban_cau_truc` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_bam_phan_hoi` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ket_qua_cau_truc` JSON NULL,
  `ket_qua_kiem_tra` JSON NULL,
  `so_don_vi_dau_vao` INT UNSIGNED NULL,
  `so_don_vi_dau_ra` INT UNSIGNED NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `bat_dau_luc` DATETIME(6) NOT NULL,
  `ket_thuc_luc` DATETIME(6) NULL,
  `ma_loi` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b49_01` UNIQUE (`yeu_cau_tro_ly_id`, `so_lan`),
  CONSTRAINT `duy_nhat_b49_02` UNIQUE (`nha_cung_cap`, `ma_yeu_cau_nha_cung_cap`),
  KEY `chi_muc_b49_01` (`yeu_cau_tro_ly_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b49_01` CHECK (trang_thai IN ('DANG_GOI', 'THANH_CONG', 'LOI_CAU_TRUC', 'LOI_NGHIEP_VU', 'QUA_HAN', 'THAT_BAI')),
  CONSTRAINT `kiem_tra_b49_02` CHECK (so_lan > 0 AND (ket_thuc_luc IS NULL OR ket_thuc_luc >= bat_dau_luc))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `lan_goi_mo_hinh`');
    }
};
