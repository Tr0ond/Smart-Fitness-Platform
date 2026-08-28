<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `ma_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b20_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ma_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b20_02` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b21_01` FOREIGN KEY (`ma_vao_phong_tap_id`) REFERENCES `ma_vao_phong_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b21_02` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b21_03` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b21_04` FOREIGN KEY (`ky_han_hoi_vien_id`) REFERENCES `ky_han_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b21_05` FOREIGN KEY (`su_dung_quyen_loi_id`) REFERENCES `su_dung_quyen_loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_don_b21_06` FOREIGN KEY (`nguoi_xac_nhan_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phan_cong_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b22_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phan_cong_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b22_02` FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phan_cong_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b22_03` FOREIGN KEY (`nguoi_phan_cong_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b23_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b23_02` FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b23_03` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b23_04` FOREIGN KEY (`ky_han_hoi_vien_id`) REFERENCES `ky_han_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b23_05` FOREIGN KEY (`su_dung_quyen_loi_id`) REFERENCES `su_dung_quyen_loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_don_b24_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_don_b24_02` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_don_b24_03` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_don_b24_04` FOREIGN KEY (`ke_hoach_tap_id`) REFERENCES `ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_don_b24_05` FOREIGN KEY (`phien_tap_id`) REFERENCES `phien_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b21_01` FOREIGN KEY (`ma_vao_phong_tap_id`, `hoi_vien_id`, `chi_nhanh_id`) REFERENCES `ma_vao_phong_tap` (`id`, `hoi_vien_id`, `chi_nhanh_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_vao_phong_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b21_02` FOREIGN KEY (`su_dung_quyen_loi_id`, `hoi_vien_id`, `ky_han_hoi_vien_id`) REFERENCES `su_dung_quyen_loi` (`id`, `hoi_vien_id`, `ky_han_hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_ghep_b23_01` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`, `hoi_vien_id`, `huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`, `hoi_vien_id`, `huan_luyen_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `lich_su_su_dung_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_ghep_b23_02` FOREIGN KEY (`su_dung_quyen_loi_id`, `hoi_vien_id`, `ky_han_hoi_vien_id`) REFERENCES `su_dung_quyen_loi` (`id`, `hoi_vien_id`, `ky_han_hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_ghep_b24_01` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`, `hoi_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_ghep_b24_02` FOREIGN KEY (`ke_hoach_tap_id`, `hoi_vien_id`) REFERENCES `ke_hoach_tap` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ghi_chu_huan_luyen` ADD CONSTRAINT `khoa_ngoai_ghep_b24_03` FOREIGN KEY (`phien_tap_id`, `hoi_vien_id`) REFERENCES `phien_tap` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_ghep_b24_03`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_ghep_b24_02`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_ghep_b24_01`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_ghep_b23_02`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_ghep_b23_01`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b21_02`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b21_01`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_don_b24_05`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_don_b24_04`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_don_b24_03`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_don_b24_02`');
    DB::statement('ALTER TABLE `ghi_chu_huan_luyen` DROP FOREIGN KEY `khoa_ngoai_don_b24_01`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b23_05`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b23_04`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b23_03`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b23_02`');
    DB::statement('ALTER TABLE `lich_su_su_dung_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b23_01`');
    DB::statement('ALTER TABLE `phan_cong_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b22_03`');
    DB::statement('ALTER TABLE `phan_cong_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b22_02`');
    DB::statement('ALTER TABLE `phan_cong_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b22_01`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b21_06`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b21_05`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b21_04`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b21_03`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b21_02`');
    DB::statement('ALTER TABLE `lich_su_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b21_01`');
    DB::statement('ALTER TABLE `ma_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b20_02`');
    DB::statement('ALTER TABLE `ma_vao_phong_tap` DROP FOREIGN KEY `khoa_ngoai_don_b20_01`');
    }
};
