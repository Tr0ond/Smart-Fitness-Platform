<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `hoi_thoai_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b44_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b45_01` FOREIGN KEY (`hoi_thoai_tro_ly_id`) REFERENCES `hoi_thoai_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b45_02` FOREIGN KEY (`yeu_cau_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b46_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b46_02` FOREIGN KEY (`hoi_thoai_tro_ly_id`) REFERENCES `hoi_thoai_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b46_03` FOREIGN KEY (`tin_nhan_dau_vao_id`) REFERENCES `tin_nhan_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b46_04` FOREIGN KEY (`ky_han_hoi_vien_id`) REFERENCES `ky_han_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_don_b46_05` FOREIGN KEY (`su_dung_quyen_loi_id`) REFERENCES `su_dung_quyen_loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_ung_vien` ADD CONSTRAINT `khoa_ngoai_don_b47_01` FOREIGN KEY (`yeu_cau_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_ung_vien` ADD CONSTRAINT `khoa_ngoai_don_b47_02` FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `giao_an_ung_vien` ADD CONSTRAINT `khoa_ngoai_don_b48_01` FOREIGN KEY (`yeu_cau_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `giao_an_ung_vien` ADD CONSTRAINT `khoa_ngoai_don_b48_02` FOREIGN KEY (`giao_an_mau_id`) REFERENCES `giao_an_mau` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lan_goi_mo_hinh` ADD CONSTRAINT `khoa_ngoai_don_b49_01` FOREIGN KEY (`yeu_cau_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_02` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_03` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_04` FOREIGN KEY (`yeu_cau_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_05` FOREIGN KEY (`ke_hoach_tap_id`) REFERENCES `ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_06` FOREIGN KEY (`phien_ban_co_so_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b50_07` FOREIGN KEY (`nguoi_quyet_dinh_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan_tro_ly` ADD CONSTRAINT `khoa_ngoai_ghep_b45_01` FOREIGN KEY (`yeu_cau_tro_ly_id`, `hoi_thoai_tro_ly_id`) REFERENCES `yeu_cau_tro_ly` (`id`, `hoi_thoai_tro_ly_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_ghep_b46_01` FOREIGN KEY (`hoi_thoai_tro_ly_id`, `hoi_vien_id`) REFERENCES `hoi_thoai_tro_ly` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_ghep_b46_02` FOREIGN KEY (`tin_nhan_dau_vao_id`, `hoi_thoai_tro_ly_id`) REFERENCES `tin_nhan_tro_ly` (`id`, `hoi_thoai_tro_ly_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_ghep_b46_03` FOREIGN KEY (`ky_han_hoi_vien_id`, `hoi_vien_id`) REFERENCES `ky_han_hoi_vien` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_tro_ly` ADD CONSTRAINT `khoa_ngoai_ghep_b46_04` FOREIGN KEY (`su_dung_quyen_loi_id`, `hoi_vien_id`, `ky_han_hoi_vien_id`) REFERENCES `su_dung_quyen_loi` (`id`, `hoi_vien_id`, `ky_han_hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b50_01` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`, `hoi_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b50_02` FOREIGN KEY (`yeu_cau_tro_ly_id`, `hoi_vien_id`) REFERENCES `yeu_cau_tro_ly` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b50_03` FOREIGN KEY (`ke_hoach_tap_id`, `hoi_vien_id`) REFERENCES `ke_hoach_tap` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `de_xuat_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b50_04` FOREIGN KEY (`phien_ban_co_so_id`, `ke_hoach_tap_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`, `ke_hoach_tap_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b50_04`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b50_03`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b50_02`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b50_01`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_ghep_b46_04`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_ghep_b46_03`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_ghep_b46_02`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_ghep_b46_01`');
    DB::statement('ALTER TABLE `tin_nhan_tro_ly` DROP FOREIGN KEY `khoa_ngoai_ghep_b45_01`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_07`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_06`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_05`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_04`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_03`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_02`');
    DB::statement('ALTER TABLE `de_xuat_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b50_01`');
    DB::statement('ALTER TABLE `lan_goi_mo_hinh` DROP FOREIGN KEY `khoa_ngoai_don_b49_01`');
    DB::statement('ALTER TABLE `giao_an_ung_vien` DROP FOREIGN KEY `khoa_ngoai_don_b48_02`');
    DB::statement('ALTER TABLE `giao_an_ung_vien` DROP FOREIGN KEY `khoa_ngoai_don_b48_01`');
    DB::statement('ALTER TABLE `bai_tap_ung_vien` DROP FOREIGN KEY `khoa_ngoai_don_b47_02`');
    DB::statement('ALTER TABLE `bai_tap_ung_vien` DROP FOREIGN KEY `khoa_ngoai_don_b47_01`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b46_05`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b46_04`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b46_03`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b46_02`');
    DB::statement('ALTER TABLE `yeu_cau_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b46_01`');
    DB::statement('ALTER TABLE `tin_nhan_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b45_02`');
    DB::statement('ALTER TABLE `tin_nhan_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b45_01`');
    DB::statement('ALTER TABLE `hoi_thoai_tro_ly` DROP FOREIGN KEY `khoa_ngoai_don_b44_01`');
    }
};
