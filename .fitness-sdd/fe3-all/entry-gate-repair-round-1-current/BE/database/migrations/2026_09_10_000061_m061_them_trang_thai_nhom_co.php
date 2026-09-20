<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE `nhom_co`
  ADD COLUMN `trang_thai` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL DEFAULT 'HOAT_DONG' AFTER `mo_ta`,
  ADD CONSTRAINT `kiem_tra_b26_01` CHECK (`trang_thai` IN ('HOAT_DONG', 'NGUNG_SU_DUNG'))
SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
ALTER TABLE `nhom_co`
  DROP CONSTRAINT `kiem_tra_b26_01`,
  DROP COLUMN `trang_thai`
SQL);
    }
};
