<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
CREATE TABLE `yeu_cau_tro_ly` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hoi_vien_id` BIGINT UNSIGNED NOT NULL,
  `hoi_thoai_tro_ly_id` BIGINT UNSIGNED NOT NULL,
  `tin_nhan_dau_vao_id` BIGINT UNSIGNED NOT NULL,
  `ky_han_hoi_vien_id` BIGINT UNSIGNED NULL,
  `su_dung_quyen_loi_id` BIGINT UNSIGNED NULL,
  `ma_yeu_cau` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `loai_yeu_cau` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `yeu_cau_chuan_hoa` JSON NULL,
  `ngu_canh_da_chot` JSON NULL,
  `phien_ban_quy_tac` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `trang_thai_han_muc` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL,
  `bat_dau_xu_ly_luc` DATETIME(6) NULL,
  `hoan_tat_luc` DATETIME(6) NULL,
  `ma_loi` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NULL,
  `ngay_tao` DATETIME(6) NOT NULL,
  `ngay_cap_nhat` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `duy_nhat_b46_01` UNIQUE (`hoi_vien_id`, `ma_yeu_cau`),
  CONSTRAINT `duy_nhat_b46_02` UNIQUE (`tin_nhan_dau_vao_id`),
  CONSTRAINT `duy_nhat_b46_03` UNIQUE (`su_dung_quyen_loi_id`),
  CONSTRAINT `duy_nhat_b46_04` UNIQUE (`id`, `hoi_thoai_tro_ly_id`),
  CONSTRAINT `duy_nhat_b46_05` UNIQUE (`id`, `hoi_vien_id`),
  KEY `chi_muc_b46_01` (`ky_han_hoi_vien_id`, `trang_thai_han_muc`),
  KEY `chi_muc_b46_02` (`hoi_vien_id`, `ngay_tao`, `id`),
  KEY `chi_muc_b46_03` (`trang_thai`, `bat_dau_xu_ly_luc`),
  CONSTRAINT `kiem_tra_b46_01` CHECK (loai_yeu_cau IN ('TAO_KE_HOACH', 'DIEU_CHINH', 'THAY_BAI', 'GIAI_THICH', 'CHUA_XAC_DINH')),
  CONSTRAINT `kiem_tra_b46_02` CHECK (trang_thai IN ('TIEP_NHAN', 'CAN_BO_SUNG', 'DANG_XU_LY', 'THANH_CONG', 'THAT_BAI', 'BI_TU_CHOI')),
  CONSTRAINT `kiem_tra_b46_03` CHECK (trang_thai_han_muc IN ('KHONG_AP_DUNG', 'GIU_CHO', 'DA_TINH', 'DA_TRA')),
  CONSTRAINT `kiem_tra_b46_04` CHECK ((trang_thai_han_muc = 'KHONG_AP_DUNG' AND ky_han_hoi_vien_id IS NULL AND su_dung_quyen_loi_id IS NULL) OR (trang_thai_han_muc IN ('GIU_CHO','DA_TINH','DA_TRA') AND ky_han_hoi_vien_id IS NOT NULL AND su_dung_quyen_loi_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE `yeu_cau_tro_ly`');
    }
};
