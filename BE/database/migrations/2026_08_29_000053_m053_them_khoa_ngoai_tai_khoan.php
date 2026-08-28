<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `nguoi_dung` ADD CONSTRAINT `khoa_ngoai_don_b02_01` FOREIGN KEY (`chi_nhanh_id`) REFERENCES `chi_nhanh` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phan_quyen_nguoi_dung` ADD CONSTRAINT `khoa_ngoai_don_b04_01` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phan_quyen_nguoi_dung` ADD CONSTRAINT `khoa_ngoai_don_b04_02` FOREIGN KEY (`vai_tro_id`) REFERENCES `vai_tro` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phan_quyen_nguoi_dung` ADD CONSTRAINT `khoa_ngoai_don_b04_03` FOREIGN KEY (`nguoi_cap_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ho_so_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b05_01` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ho_so_huan_luyen_vien` ADD CONSTRAINT `khoa_ngoai_don_b06_01` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ngay_ranh_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b07_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `dung_cu_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b08_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `dung_cu_hoi_vien` ADD CONSTRAINT `khoa_ngoai_don_b08_02` FOREIGN KEY (`dung_cu_id`) REFERENCES `dung_cu` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `chi_so_co_the` ADD CONSTRAINT `khoa_ngoai_don_b09_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `the_truy_cap` ADD CONSTRAINT `khoa_ngoai_don_b10_01` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_dat_lai_mat_khau` ADD CONSTRAINT `khoa_ngoai_don_b11_01` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `yeu_cau_dat_lai_mat_khau` DROP FOREIGN KEY `khoa_ngoai_don_b11_01`');
    DB::statement('ALTER TABLE `the_truy_cap` DROP FOREIGN KEY `khoa_ngoai_don_b10_01`');
    DB::statement('ALTER TABLE `chi_so_co_the` DROP FOREIGN KEY `khoa_ngoai_don_b09_01`');
    DB::statement('ALTER TABLE `dung_cu_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b08_02`');
    DB::statement('ALTER TABLE `dung_cu_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b08_01`');
    DB::statement('ALTER TABLE `ngay_ranh_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b07_01`');
    DB::statement('ALTER TABLE `ho_so_huan_luyen_vien` DROP FOREIGN KEY `khoa_ngoai_don_b06_01`');
    DB::statement('ALTER TABLE `ho_so_hoi_vien` DROP FOREIGN KEY `khoa_ngoai_don_b05_01`');
    DB::statement('ALTER TABLE `phan_quyen_nguoi_dung` DROP FOREIGN KEY `khoa_ngoai_don_b04_03`');
    DB::statement('ALTER TABLE `phan_quyen_nguoi_dung` DROP FOREIGN KEY `khoa_ngoai_don_b04_02`');
    DB::statement('ALTER TABLE `phan_quyen_nguoi_dung` DROP FOREIGN KEY `khoa_ngoai_don_b04_01`');
    DB::statement('ALTER TABLE `nguoi_dung` DROP FOREIGN KEY `khoa_ngoai_don_b02_01`');
    }
};
