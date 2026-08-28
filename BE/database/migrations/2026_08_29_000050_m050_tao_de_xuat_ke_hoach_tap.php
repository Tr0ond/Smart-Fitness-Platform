<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `de_xuat_ke_hoach_tap` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `nguon_de_xuat` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `nguoi_tao_id` BIGINT UNSIGNED NOT NULL,
  `phan_cong_huan_luyen_vien_id` BIGINT UNSIGNED NULL,
  `yeu_cau_tro_ly_id` BIGINT UNSIGNED NULL,
  `ke_hoach_tap_id` BIGINT UNSIGNED NULL,
  `phien_ban_co_so_id` BIGINT UNSIGNED NULL,
  `moc_thay_doi_ke_hoach_co_so` INT UNSIGNED NOT NULL,
  `phien_ban_ho_so_co_so` INT UNSIGNED NOT NULL,
  `loai_thay_doi` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `tieu_de` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `giai_thich` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `noi_dung_de_xuat` JSON NOT NULL,
  `phien_ban_cau_truc` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ma_bam_noi_dung` CHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `ap_dung_tu_ngay` DATE NOT NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `het_han_luc` DATETIME(6) NOT NULL,
  `nguoi_quyet_dinh_id` BIGINT UNSIGNED NULL,
  `quyet_dinh_luc` DATETIME(6) NULL,
  `ap_dung_luc` DATETIME(6) NULL,
  `ly_do_ket_thuc` VARCHAR(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b50_01` UNIQUE (`yeu_cau_tro_ly_id`),
  KEY `chi_muc_b50_01` (`hoi_vien_id`, `trang_thai`, `ngay_tao`),
  KEY `chi_muc_b50_02` (`ke_hoach_tap_id`, `trang_thai`),
  CONSTRAINT `kiem_tra_b50_01` CHECK (nguon_de_xuat IN ('TRO_LY', 'HUAN_LUYEN_VIEN')),
  CONSTRAINT `kiem_tra_b50_02` CHECK (loai_thay_doi IN ('TAO_MOI', 'DIEU_CHINH', 'THAY_BAI')),
  CONSTRAINT `kiem_tra_b50_03` CHECK (trang_thai IN ('CHO_XAC_NHAN', 'DA_TU_CHOI', 'HET_HAN', 'XUNG_DOT', 'DA_AP_DUNG')),
  CONSTRAINT `kiem_tra_b50_04` CHECK (het_han_luc > ngay_tao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `de_xuat_ke_hoach_tap`');
    }
};
