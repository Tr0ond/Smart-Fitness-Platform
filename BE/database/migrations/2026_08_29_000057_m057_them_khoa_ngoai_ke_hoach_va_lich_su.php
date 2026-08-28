<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b33_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b33_02` FOREIGN KEY (`phien_ban_hien_tai_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b33_03` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_ban_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b34_01` FOREIGN KEY (`ke_hoach_tap_id`) REFERENCES `ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_ban_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b34_02` FOREIGN KEY (`phien_ban_truoc_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_ban_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b34_03` FOREIGN KEY (`giao_an_mau_id`) REFERENCES `giao_an_mau` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_ban_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b34_04` FOREIGN KEY (`de_xuat_ke_hoach_tap_id`) REFERENCES `de_xuat_ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_ban_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_don_b34_05` FOREIGN KEY (`nguoi_tao_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ngay_trong_ke_hoach` ADD CONSTRAINT `khoa_ngoai_don_b35_01` FOREIGN KEY (`phien_ban_ke_hoach_tap_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_ke_hoach` ADD CONSTRAINT `khoa_ngoai_don_b36_01` FOREIGN KEY (`ngay_trong_ke_hoach_id`) REFERENCES `ngay_trong_ke_hoach` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_ke_hoach` ADD CONSTRAINT `khoa_ngoai_don_b36_02` FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_don_b37_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_don_b37_02` FOREIGN KEY (`ke_hoach_tap_id`) REFERENCES `ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_don_b37_03` FOREIGN KEY (`phien_ban_ke_hoach_tap_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_don_b37_04` FOREIGN KEY (`ngay_trong_ke_hoach_id`) REFERENCES `ngay_trong_ke_hoach` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_don_b37_05` FOREIGN KEY (`thay_the_buoi_tap_id`) REFERENCES `buoi_tap_du_kien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_tap` ADD CONSTRAINT `khoa_ngoai_don_b38_01` FOREIGN KEY (`hoi_vien_id`) REFERENCES `ho_so_hoi_vien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_tap` ADD CONSTRAINT `khoa_ngoai_don_b38_02` FOREIGN KEY (`buoi_tap_du_kien_id`) REFERENCES `buoi_tap_du_kien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_phien` ADD CONSTRAINT `khoa_ngoai_don_b39_01` FOREIGN KEY (`phien_tap_id`) REFERENCES `phien_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_phien` ADD CONSTRAINT `khoa_ngoai_don_b39_02` FOREIGN KEY (`bai_tap_id`) REFERENCES `bai_tap` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `bai_tap_trong_phien` ADD CONSTRAINT `khoa_ngoai_don_b39_03` FOREIGN KEY (`bai_tap_trong_ke_hoach_id`) REFERENCES `bai_tap_trong_ke_hoach` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `hiep_tap` ADD CONSTRAINT `khoa_ngoai_don_b40_01` FOREIGN KEY (`bai_tap_trong_phien_id`) REFERENCES `bai_tap_trong_phien` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b33_01` FOREIGN KEY (`phien_ban_hien_tai_id`, `id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`, `ke_hoach_tap_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_ban_ke_hoach_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b34_01` FOREIGN KEY (`phien_ban_truoc_id`, `ke_hoach_tap_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`, `ke_hoach_tap_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_ghep_b37_01` FOREIGN KEY (`ke_hoach_tap_id`, `hoi_vien_id`) REFERENCES `ke_hoach_tap` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_ghep_b37_02` FOREIGN KEY (`phien_ban_ke_hoach_tap_id`, `ke_hoach_tap_id`) REFERENCES `phien_ban_ke_hoach_tap` (`id`, `ke_hoach_tap_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `buoi_tap_du_kien` ADD CONSTRAINT `khoa_ngoai_ghep_b37_03` FOREIGN KEY (`ngay_trong_ke_hoach_id`, `phien_ban_ke_hoach_tap_id`) REFERENCES `ngay_trong_ke_hoach` (`id`, `phien_ban_ke_hoach_tap_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `phien_tap` ADD CONSTRAINT `khoa_ngoai_ghep_b38_01` FOREIGN KEY (`buoi_tap_du_kien_id`, `hoi_vien_id`) REFERENCES `buoi_tap_du_kien` (`id`, `hoi_vien_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `phien_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b38_01`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_ghep_b37_03`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_ghep_b37_02`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_ghep_b37_01`');
    DB::statement('ALTER TABLE `phien_ban_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b34_01`');
    DB::statement('ALTER TABLE `ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_ghep_b33_01`');
    DB::statement('ALTER TABLE `hiep_tap` DROP FOREIGN KEY `khoa_ngoai_don_b40_01`');
    DB::statement('ALTER TABLE `bai_tap_trong_phien` DROP FOREIGN KEY `khoa_ngoai_don_b39_03`');
    DB::statement('ALTER TABLE `bai_tap_trong_phien` DROP FOREIGN KEY `khoa_ngoai_don_b39_02`');
    DB::statement('ALTER TABLE `bai_tap_trong_phien` DROP FOREIGN KEY `khoa_ngoai_don_b39_01`');
    DB::statement('ALTER TABLE `phien_tap` DROP FOREIGN KEY `khoa_ngoai_don_b38_02`');
    DB::statement('ALTER TABLE `phien_tap` DROP FOREIGN KEY `khoa_ngoai_don_b38_01`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_don_b37_05`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_don_b37_04`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_don_b37_03`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_don_b37_02`');
    DB::statement('ALTER TABLE `buoi_tap_du_kien` DROP FOREIGN KEY `khoa_ngoai_don_b37_01`');
    DB::statement('ALTER TABLE `bai_tap_trong_ke_hoach` DROP FOREIGN KEY `khoa_ngoai_don_b36_02`');
    DB::statement('ALTER TABLE `bai_tap_trong_ke_hoach` DROP FOREIGN KEY `khoa_ngoai_don_b36_01`');
    DB::statement('ALTER TABLE `ngay_trong_ke_hoach` DROP FOREIGN KEY `khoa_ngoai_don_b35_01`');
    DB::statement('ALTER TABLE `phien_ban_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b34_05`');
    DB::statement('ALTER TABLE `phien_ban_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b34_04`');
    DB::statement('ALTER TABLE `phien_ban_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b34_03`');
    DB::statement('ALTER TABLE `phien_ban_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b34_02`');
    DB::statement('ALTER TABLE `phien_ban_ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b34_01`');
    DB::statement('ALTER TABLE `ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b33_03`');
    DB::statement('ALTER TABLE `ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b33_02`');
    DB::statement('ALTER TABLE `ke_hoach_tap` DROP FOREIGN KEY `khoa_ngoai_don_b33_01`');
    }
};
