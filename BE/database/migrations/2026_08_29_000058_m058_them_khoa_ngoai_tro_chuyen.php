<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `hoi_thoai` ADD CONSTRAINT `khoa_ngoai_don_b41_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `hoi_thoai` ADD CONSTRAINT `khoa_ngoai_don_b41_02` FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `hoi_thoai` ADD CONSTRAINT `khoa_ngoai_don_b41_03` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b42_01` FOREIGN KEY (`hoi_thoai_id`) REFERENCES `hoi_thoai` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b42_02` FOREIGN KEY (`nguoi_gui_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b42_03` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b42_04` FOREIGN KEY (`su_dung_quyen_loi_id`) REFERENCES `su_dung_quyen_loi` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b42_05` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b42_06` FOREIGN KEY (`huan_luyen_vien_id`) REFERENCES `ho_so_huan_luyen_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `su_kien_phat_tin_nhan` ADD CONSTRAINT `khoa_ngoai_don_b43_01` FOREIGN KEY (`tin_nhan_id`) REFERENCES `tin_nhan` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `hoi_thoai` ADD CONSTRAINT `khoa_ngoai_ghep_b41_01` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`, `hoi_vien_id`, `huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`, `hoi_vien_id`, `huan_luyen_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_ghep_b42_01` FOREIGN KEY (`hoi_thoai_id`, `phan_cong_huan_luyen_vien_id`) REFERENCES `hoi_thoai` (`id`, `phan_cong_huan_luyen_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_ghep_b42_02` FOREIGN KEY (`phan_cong_huan_luyen_vien_id`, `hoi_vien_id`, `huan_luyen_vien_id`) REFERENCES `phan_cong_huan_luyen_vien` (`id`, `hoi_vien_id`, `huan_luyen_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `tin_nhan` ADD CONSTRAINT `khoa_ngoai_ghep_b42_03` FOREIGN KEY (`su_dung_quyen_loi_id`, `hoi_vien_id`) REFERENCES `su_dung_quyen_loi` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_ghep_b42_03`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_ghep_b42_02`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_ghep_b42_01`');
    DB::statement('ALTER TABLE `hoi_thoai` DROP FOREIGN KEY `khoa_ngoai_ghep_b41_01`');
    DB::statement('ALTER TABLE `su_kien_phat_tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b43_01`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b42_06`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b42_05`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b42_04`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b42_03`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b42_02`');
    DB::statement('ALTER TABLE `tin_nhan` DROP FOREIGN KEY `khoa_ngoai_don_b42_01`');
    DB::statement('ALTER TABLE `hoi_thoai` DROP FOREIGN KEY `khoa_ngoai_don_b41_03`');
    DB::statement('ALTER TABLE `hoi_thoai` DROP FOREIGN KEY `khoa_ngoai_don_b41_02`');
    DB::statement('ALTER TABLE `hoi_thoai` DROP FOREIGN KEY `khoa_ngoai_don_b41_01`');
    }
};
