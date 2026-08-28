<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
    DB::statement(<<<'SQL'
ALTER TABLE `nhat_ky_he_thong` ADD CONSTRAINT `khoa_ngoai_don_b51_01` FOREIGN KEY (`nguoi_thuc_hien_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    DB::statement(<<<'SQL'
ALTER TABLE `yeu_cau_chong_lap` ADD CONSTRAINT `khoa_ngoai_don_b52_01` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
SQL);
    }

    public function down(): void
    {
    DB::statement('ALTER TABLE `yeu_cau_chong_lap` DROP FOREIGN KEY `khoa_ngoai_don_b52_01`');
    DB::statement('ALTER TABLE `nhat_ky_he_thong` DROP FOREIGN KEY `khoa_ngoai_don_b51_01`');
    }
};
