<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `goi_tap` ADD CONSTRAINT `khoa_ngoai_don_b12_01` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `goi_tap` ADD CONSTRAINT `khoa_ngoai_don_b12_02` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `quyen_loi_goi_tap` ADD CONSTRAINT `khoa_ngoai_don_b13_01` FOREIGN KEY (`goi_tap_id`) REFERENCES `goi_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `don_mua_goi` ADD CONSTRAINT `khoa_ngoai_don_b14_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `don_mua_goi` ADD CONSTRAINT `khoa_ngoai_don_b14_02` FOREIGN KEY (`goi_tap_id`) REFERENCES `goi_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lan_thanh_toan` ADD CONSTRAINT `khoa_ngoai_don_b15_01` FOREIGN KEY (`don_mua_goi_id`) REFERENCES `don_mua_goi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `su_kien_thanh_toan` ADD CONSTRAINT `khoa_ngoai_don_b16_01` FOREIGN KEY (`lan_thanh_toan_id`) REFERENCES `lan_thanh_toan` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `dang_ky_goi_tap` ADD CONSTRAINT `khoa_ngoai_don_b17_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `dang_ky_goi_tap` ADD CONSTRAINT `khoa_ngoai_don_b17_02` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `dang_ky_goi_tap` ADD CONSTRAINT `khoa_ngoai_don_b17_03` FOREIGN KEY (`lan_su_dung_dau_tien_id`) REFERENCES `su_dung_quyen_loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b18_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b18_02` FOREIGN KEY (`don_mua_goi_id`) REFERENCES `don_mua_goi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b18_03` FOREIGN KEY (`lan_thanh_toan_id`) REFERENCES `lan_thanh_toan` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b18_04` FOREIGN KEY (`dang_ky_goi_tap_id`) REFERENCES `dang_ky_goi_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `su_dung_quyen_loi` ADD CONSTRAINT `khoa_ngoai_don_b19_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `su_dung_quyen_loi` ADD CONSTRAINT `khoa_ngoai_don_b19_02` FOREIGN KEY (`ky_han_hoi_vien_id`) REFERENCES `ky_han_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `su_dung_quyen_loi` ADD CONSTRAINT `khoa_ngoai_don_b19_03` FOREIGN KEY (`nguoi_thuc_hien_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `dang_ky_goi_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b17_01` FOREIGN KEY (`lan_su_dung_dau_tien_id`, `hoi_vien_id`) REFERENCES `su_dung_quyen_loi` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_ghep_b18_01` FOREIGN KEY (`don_mua_goi_id`, `hoi_vien_id`) REFERENCES `don_mua_goi` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_ghep_b18_02` FOREIGN KEY (`lan_thanh_toan_id`, `don_mua_goi_id`) REFERENCES `lan_thanh_toan` (`id`, `don_mua_goi_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ky_han_hoi_vien` ADD CONSTRAINT `khoa_ngoai_ghep_b18_03` FOREIGN KEY (`dang_ky_goi_tap_id`, `hoi_vien_id`) REFERENCES `dang_ky_goi_tap` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `su_dung_quyen_loi` ADD CONSTRAINT `khoa_ngoai_ghep_b19_01` FOREIGN KEY (`ky_han_hoi_vien_id`, `hoi_vien_id`) REFERENCES `ky_han_hoi_vien` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `su_dung_quyen_loi` DROP FOREIGN KEY `khoa_ngoai_ghep_b19_01`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_ghep_b18_03`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_ghep_b18_02`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_ghep_b18_01`');
    DB::statement('ALTER TABLE `dang_ky_goi_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b17_01`');
    DB::statement('ALTER TABLE `su_dung_quyen_loi` DROP FOREIGN KEY `khoa_ngoai_don_b19_03`');
    DB::statement('ALTER TABLE `su_dung_quyen_loi` DROP FOREIGN KEY `khoa_ngoai_don_b19_02`');
    DB::statement('ALTER TABLE `su_dung_quyen_loi` DROP FOREIGN KEY `khoa_ngoai_don_b19_01`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b18_04`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b18_03`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b18_02`');
    DB::statement('ALTER TABLE `ky_han_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b18_01`');
    DB::statement('ALTER TABLE `dang_ky_goi_tap` DROP FOREIGN KEY `khoa_ngoai_don_b17_03`');
    DB::statement('ALTER TABLE `dang_ky_goi_tap` DROP FOREIGN KEY `khoa_ngoai_don_b17_02`');
    DB::statement('ALTER TABLE `dang_ky_goi_tap` DROP FOREIGN KEY `khoa_ngoai_don_b17_01`');
    DB::statement('ALTER TABLE `su_kien_thanh_toan` DROP FOREIGN KEY `khoa_ngoai_don_b16_01`');
    DB::statement('ALTER TABLE `lan_thanh_toan` DROP FOREIGN KEY `khoa_ngoai_don_b15_01`');
    DB::statement('ALTER TABLE `don_mua_goi` DROP FOREIGN KEY `khoa_ngoai_don_b14_02`');
    DB::statement('ALTER TABLE `don_mua_goi` DROP FOREIGN KEY `khoa_ngoai_don_b14_01`');
    DB::statement('ALTER TABLE `quyen_loi_goi_tap` DROP FOREIGN KEY `khoa_ngoai_don_b13_01`');
    DB::statement('ALTER TABLE `goi_tap` DROP FOREIGN KEY `khoa_ngoai_don_b12_02`');
    DB::statement('ALTER TABLE `goi_tap` DROP FOREIGN KEY `khoa_ngoai_don_b12_01`');
    }
};
