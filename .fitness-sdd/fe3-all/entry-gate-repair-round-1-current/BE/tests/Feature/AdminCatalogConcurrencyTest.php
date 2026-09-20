<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use App\Services\Admin\ExerciseCatalogAdminService;
use App\Services\Admin\PackageCatalogAdminService;
use App\Services\Admin\WorkoutTemplateCatalogAdminService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class AdminCatalogConcurrencyTest extends TestCase
{
    public function test_actual_process_catalog_mutations_serialize_without_lost_partial_updates(): void
    {
        TestDatabaseGuard::damBaoDatabaseHienTai();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $fixture = $this->createFixture();
        $revisionTemplateId = null;

        try {
            $benefitResults = $this->runProcesses($database, [
                ['benefits', $fixture['package_id'], [
                    'gym_access' => true,
                    'fitness_assistant' => true,
                    'fitness_assistant_limit' => 12,
                    'trainer_chat' => false,
                    'direct_trainer_sessions' => 2,
                ]],
                ['benefits', $fixture['package_id'], [
                    'gym_access' => false,
                    'fitness_assistant' => true,
                    'fitness_assistant_limit' => null,
                    'trainer_chat' => true,
                    'direct_trainer_sessions' => 5,
                ]],
            ], $fixture['admin_id']);
            $this->assertSame([2, 3], collect($benefitResults)->pluck('data.configuration_version')->sort()->values()->all());
            $this->assertSame(3, (int) DB::table('goi_tap')->where('id', $fixture['package_id'])->value('phien_ban_cau_hinh'));
            $finalBenefit = DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $fixture['package_id'])->sole();
            $this->assertContains((int) $finalBenefit->so_buoi_huan_luyen_vien, [2, 5]);

            $exerciseResults = $this->runProcesses($database, [
                ['exercise', $fixture['exercise_id'], ['name' => 'Tên sau race']],
                ['exercise', $fixture['exercise_id'], ['status' => 'NGUNG_SU_DUNG']],
            ], $fixture['admin_id']);
            $this->assertContains(2, collect($exerciseResults)->pluck('data.content_version')->all());
            $exercise = DB::table('bai_tap')->where('id', $fixture['exercise_id'])->sole();
            $this->assertSame('Tên sau race', $exercise->ten_bai_tap);
            $this->assertSame('NGUNG_SU_DUNG', $exercise->trang_thai);
            $this->assertSame(2, (int) $exercise->phien_ban_noi_dung);

            $templateResults = $this->runProcesses($database, [
                ['template', $fixture['template_id'], ['goal' => 'MUC_TIEU_RACE']],
                ['template', $fixture['template_id'], ['level' => 'TRINH_DO_RACE']],
            ], $fixture['admin_id']);
            $this->assertSame([2, 3], collect($templateResults)->pluck('data.content_version')->sort()->values()->all());
            $template = DB::table('giao_an_mau')->where('id', $fixture['template_id'])->sole();
            $this->assertSame('MUC_TIEU_RACE', $template->muc_tieu);
            $this->assertSame('TRINH_DO_RACE', $template->trinh_do);
            $this->assertSame(3, (int) $template->phien_ban_noi_dung);

            $revisionDays = [[
                'order' => 1,
                'name' => 'Day revision race',
                'estimated_minutes' => 60,
                'exercises' => [[
                    'exercise_id' => $fixture['revision_exercise_id'],
                    'order' => 1,
                    'target_sets' => 4,
                    'min_reps' => 8,
                    'max_reps' => 12,
                    'rest_seconds' => 90,
                ]],
            ]];
            $revisionResults = $this->runProcesses($database, [
                ['template_revision', $fixture['template_id'], [
                    'new_code' => 'TPL_REV_A_'.strtoupper(bin2hex(random_bytes(4))),
                    'expected_content_version' => 3,
                    'name' => 'Revision race A',
                    'goal' => 'GOAL_RACE_A',
                    'level' => 'LEVEL_RACE_A',
                    'sessions_per_week' => 1,
                    'status' => 'HOAT_DONG',
                    'days' => $revisionDays,
                ]],
                ['template_revision', $fixture['template_id'], [
                    'new_code' => 'TPL_REV_B_'.strtoupper(bin2hex(random_bytes(4))),
                    'expected_content_version' => 3,
                    'name' => 'Revision race B',
                    'goal' => 'GOAL_RACE_B',
                    'level' => 'LEVEL_RACE_B',
                    'sessions_per_week' => 1,
                    'status' => 'HOAT_DONG',
                    'days' => $revisionDays,
                ]],
            ], $fixture['admin_id']);
            $this->assertSame(
                ['error', 'success'],
                collect($revisionResults)->pluck('status')->sort()->values()->all(),
                json_encode($revisionResults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
            $this->assertSame('WORKOUT_TEMPLATE_STALE', collect($revisionResults)->firstWhere('status', 'error')['code']);
            $winner = collect($revisionResults)->firstWhere('status', 'success');
            $revisionTemplateId = (int) $winner['data']['new_template_id'];
            $this->assertSame('NGUNG_SU_DUNG', DB::table('giao_an_mau')->where('id', $fixture['template_id'])->value('trang_thai'));
            $this->assertSame(1, DB::table('giao_an_mau')->where('id', $revisionTemplateId)->count());
        } finally {
            $this->cleanupFixture($fixture, $revisionTemplateId);
        }
    }

    public function test_muscle_group_process_race_serializes_different_admins_and_audits_once(): void
    {
        TestDatabaseGuard::damBaoDatabaseHienTai();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $suffix = strtoupper(bin2hex(random_bytes(5)));
        $adminA = $this->createRaceAdmin('A_'.$suffix);
        $adminB = $this->createRaceAdmin('B_'.$suffix);
        $now = CarbonImmutable::now('UTC');
        $groupId = DB::table('nhom_co')->insertGetId([
            'ma_nhom_co' => 'RACE_MUSCLE_'.$suffix,
            'ten_nhom_co' => 'Tên ban đầu',
            'mo_ta' => null,
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);

        try {
            $results = $this->runProcesses($database, [
                ['muscle_group', $groupId, ['name' => 'Tên từ Admin A'], $adminA['admin_id']],
                ['muscle_group', $groupId, ['status' => 'NGUNG_SU_DUNG'], $adminB['admin_id']],
            ], $adminA['admin_id']);
            $this->assertSame(['success', 'success'], collect($results)->pluck('status')->all());
            $this->assertSame('Tên từ Admin A', DB::table('nhom_co')->where('id', $groupId)->value('ten_nhom_co'));
            $this->assertSame('NGUNG_SU_DUNG', DB::table('nhom_co')->where('id', $groupId)->value('trang_thai'));
            $this->assertSame(1, DB::table('nhat_ky_he_thong')
                ->where('hanh_dong', 'CAP_NHAT_TRANG_THAI_NHOM_CO')
                ->where('dinh_danh_doi_tuong', $groupId)->count());
        } finally {
            DB::table('nhat_ky_he_thong')->where('dinh_danh_doi_tuong', $groupId)->delete();
            DB::table('nhom_co')->where('id', $groupId)->delete();
            $this->cleanupRaceAdmin($adminA);
            $this->cleanupRaceAdmin($adminB);
        }
    }

    public function test_muscle_group_deactivation_race_with_new_relation_is_linearizable(): void
    {
        TestDatabaseGuard::damBaoDatabaseHienTai();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $suffix = strtoupper(bin2hex(random_bytes(5)));
        $adminA = $this->createRaceAdmin('REL_A_'.$suffix);
        $adminB = $this->createRaceAdmin('REL_B_'.$suffix);
        $now = CarbonImmutable::now('UTC');
        $groupId = DB::table('nhom_co')->insertGetId([
            'ma_nhom_co' => 'RACE_REL_MUSCLE_'.$suffix,
            'ten_nhom_co' => 'Nhóm cơ race relation',
            'mo_ta' => null,
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $oldExerciseId = $this->insertRaceExercise($adminA['admin_id'], 'RACE_OLD_'.$suffix, $now);
        $newExerciseId = $this->insertRaceExercise($adminA['admin_id'], 'RACE_NEW_'.$suffix, $now);
        $oldPivotId = DB::table('bai_tap_nhom_co')->insertGetId([
            'bai_tap_id' => $oldExerciseId,
            'nhom_co_id' => $groupId,
            'vai_tro_nhom_co' => 'CHINH',
            'ngay_tao' => $now->copy()->subMinute(),
            'ngay_cap_nhat' => $now->copy()->subMinute(),
        ]);
        $oldPivotBefore = (array) DB::table('bai_tap_nhom_co')->where('id', $oldPivotId)->sole();

        try {
            $results = $this->runProcesses($database, [
                ['exercise', $newExerciseId, [
                    'muscle_groups' => [['id' => $groupId, 'role' => 'CHINH']],
                ], $adminA['admin_id']],
                ['muscle_group', $groupId, ['status' => 'NGUNG_SU_DUNG'], $adminB['admin_id']],
            ], $adminA['admin_id']);
            $relationResult = $results[0];
            $deactivationResult = $results[1];
            $this->assertSame('success', $deactivationResult['status'], json_encode($results));
            $this->assertSame('NGUNG_SU_DUNG', DB::table('nhom_co')->where('id', $groupId)->value('trang_thai'));
            $this->assertSame($oldPivotBefore, (array) DB::table('bai_tap_nhom_co')->where('id', $oldPivotId)->sole());
            if ($relationResult['status'] === 'success') {
                $this->assertSame(1, DB::table('bai_tap_nhom_co')
                    ->where('bai_tap_id', $newExerciseId)->where('nhom_co_id', $groupId)->count());
            } else {
                $this->assertSame('error', $relationResult['status']);
                $this->assertSame('INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE', $relationResult['code']);
                $this->assertSame(0, DB::table('bai_tap_nhom_co')
                    ->where('bai_tap_id', $newExerciseId)->where('nhom_co_id', $groupId)->count());
            }
            $this->assertSame(1, DB::table('bai_tap')->where('id', $newExerciseId)->count());
        } finally {
            DB::table('bai_tap_nhom_co')->whereIn('bai_tap_id', [$oldExerciseId, $newExerciseId])->delete();
            DB::table('bai_tap')->whereIn('id', [$oldExerciseId, $newExerciseId])->delete();
            DB::table('nhat_ky_he_thong')->where('dinh_danh_doi_tuong', $groupId)->delete();
            DB::table('nhom_co')->where('id', $groupId)->delete();
            $this->cleanupRaceAdmin($adminA);
            $this->cleanupRaceAdmin($adminB);
        }
    }

    /** @param array<int, array{0:string,1:int,2:array<string,mixed>,3?:int}> $actions */
    private function runProcesses(string $database, array $actions, int $actorId): array
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'admin_catalog_start_'.bin2hex(random_bytes(8));
        $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        foreach ($actions as $actionData) {
            [$action, $targetId, $payload] = $actionData;
            $processActorId = (int) ($actionData[3] ?? $actorId);
            $pipes = [];
            $process = proc_open([
                PHP_BINARY,
                base_path('tests/Support/run_admin_catalog_action.php'),
                $database,
                (string) $processActorId,
                $action,
                (string) $targetId,
                json_encode($payload, JSON_THROW_ON_ERROR),
                $barrier,
            ], $descriptor, $pipes, base_path(), TestDatabaseGuard::moiTruongTienTrinhCon());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        touch($barrier);
        try {
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->assertSame(0, $exit, trim((string) $stderr));
                $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }

    /** @return array<string, int> */
    private function createFixture(): array
    {
        $suffix = strtoupper(bin2hex(random_bytes(5)));
        $now = CarbonImmutable::now('UTC');
        $branchId = DB::table('chi_nhanh')->insertGetId([
            'ma_chi_nhanh' => 'CAT_CONC_'.$suffix,
            'ten_chi_nhanh' => 'Catalog concurrency '.$suffix,
            'dia_chi' => 'Test only',
            'so_dien_thoai' => null,
            'mui_gio' => 'Asia/Ho_Chi_Minh',
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $adminId = DB::table('nguoi_dung')->insertGetId([
            'chi_nhanh_id' => $branchId,
            'ho_ten' => 'Admin catalog concurrency',
            'thu_dien_tu' => strtolower('catalog.'.$suffix.'@example.com'),
            'so_dien_thoai' => null,
            'mat_khau_bam' => Hash::make('Concurrent!Password123'),
            'anh_dai_dien' => null,
            'xac_minh_thu_luc' => null,
            'trang_thai' => 'HOAT_DONG',
            'dang_nhap_gan_nhat_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $adminRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        $assignmentId = DB::table('phan_quyen_nguoi_dung')->insertGetId([
            'nguoi_dung_id' => $adminId,
            'vai_tro_id' => $adminRoleId,
            'nguoi_cap_id' => null,
            'cap_luc' => $now,
            'thu_hoi_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $actor = NguoiDung::query()->findOrFail($adminId);
        $package = app(PackageCatalogAdminService::class)->tao($actor, [
            'code' => 'CAT_CONC_'.$suffix,
            'name' => 'Package concurrency',
            'price' => 300000,
            'duration_days' => 30,
            'status' => 'DANG_BAN',
            'benefits' => [
                'gym_access' => true,
                'fitness_assistant' => true,
                'fitness_assistant_limit' => 10,
                'trainer_chat' => true,
                'direct_trainer_sessions' => 1,
            ],
        ]);
        $exercise = app(ExerciseCatalogAdminService::class)->taoBaiTap($actor, [
            'code' => 'EX_CONC_'.$suffix,
            'name' => 'Exercise concurrency',
            'difficulty' => 'TRUNG_BINH',
            'instructions' => 'Initial instructions',
            'status' => 'HOAT_DONG',
            'equipment_ids' => [],
            'muscle_groups' => [],
        ]);
        $template = app(WorkoutTemplateCatalogAdminService::class)->tao($actor, [
            'code' => 'TPL_CONC_'.$suffix,
            'name' => 'Template concurrency',
            'goal' => 'GOAL_INITIAL',
            'level' => 'LEVEL_INITIAL',
            'sessions_per_week' => 1,
            'status' => 'HOAT_DONG',
            'days' => [[
                'order' => 1,
                'name' => 'Day 1',
                'estimated_minutes' => 60,
                'exercises' => [[
                    'exercise_id' => $exercise['id'],
                    'order' => 1,
                    'target_sets' => 3,
                    'min_reps' => 8,
                    'max_reps' => 12,
                    'rest_seconds' => 90,
                ]],
            ]],
        ]);
        $revisionExercise = app(ExerciseCatalogAdminService::class)->taoBaiTap($actor, [
            'code' => 'EX_REV_'.$suffix,
            'name' => 'Exercise revision concurrency',
            'difficulty' => 'TRUNG_BINH',
            'instructions' => 'Exercise kept active for revision race.',
            'status' => 'HOAT_DONG',
            'equipment_ids' => [],
            'muscle_groups' => [],
        ]);

        return [
            'branch_id' => $branchId,
            'admin_id' => $adminId,
            'assignment_id' => $assignmentId,
            'package_id' => $package['id'],
            'exercise_id' => $exercise['id'],
            'revision_exercise_id' => $revisionExercise['id'],
            'template_id' => $template['id'],
        ];
    }

    /** @return array{branch_id:int,admin_id:int,assignment_id:int} */
    private function createRaceAdmin(string $suffix): array
    {
        $now = CarbonImmutable::now('UTC');
        $branchId = DB::table('chi_nhanh')->insertGetId([
            'ma_chi_nhanh' => 'RACE_BRANCH_'.$suffix,
            'ten_chi_nhanh' => 'Race branch '.$suffix,
            'dia_chi' => 'Test only',
            'so_dien_thoai' => null,
            'mui_gio' => 'Asia/Ho_Chi_Minh',
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $adminId = DB::table('nguoi_dung')->insertGetId([
            'chi_nhanh_id' => $branchId,
            'ho_ten' => 'Race admin '.$suffix,
            'thu_dien_tu' => strtolower('race.'.$suffix.'@example.com'),
            'so_dien_thoai' => null,
            'mat_khau_bam' => Hash::make('Concurrent!Password123'),
            'anh_dai_dien' => null,
            'xac_minh_thu_luc' => null,
            'trang_thai' => 'HOAT_DONG',
            'dang_nhap_gan_nhat_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $assignmentId = DB::table('phan_quyen_nguoi_dung')->insertGetId([
            'nguoi_dung_id' => $adminId,
            'vai_tro_id' => DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id'),
            'nguoi_cap_id' => null,
            'cap_luc' => $now,
            'thu_hoi_luc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);

        return ['branch_id' => $branchId, 'admin_id' => $adminId, 'assignment_id' => $assignmentId];
    }

    private function insertRaceExercise(int $adminId, string $code, CarbonImmutable $now): int
    {
        return (int) DB::table('bai_tap')->insertGetId([
            'ma_bai_tap' => $code,
            'ten_bai_tap' => $code,
            'do_kho' => 'DE',
            'huong_dan' => 'Race test',
            'duong_dan_hinh_anh' => null,
            'duong_dan_video' => null,
            'thong_tin_bo_sung' => null,
            'phien_ban_noi_dung' => 1,
            'trang_thai' => 'HOAT_DONG',
            'nguoi_tao_id' => $adminId,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
    }

    /** @param array{branch_id:int,admin_id:int,assignment_id:int} $admin */
    private function cleanupRaceAdmin(array $admin): void
    {
        DB::table('phan_quyen_nguoi_dung')->where('id', $admin['assignment_id'])->delete();
        DB::table('nguoi_dung')->where('id', $admin['admin_id'])->delete();
        DB::table('chi_nhanh')->where('id', $admin['branch_id'])->delete();
    }

    /** @param array<string, int> $fixture */
    private function cleanupFixture(array $fixture, ?int $revisionTemplateId = null): void
    {
        if ($revisionTemplateId !== null) {
            $revisionDayIds = DB::table('ngay_trong_giao_an')->where('giao_an_mau_id', $revisionTemplateId)->pluck('id');
            DB::table('bai_tap_trong_giao_an')->whereIn('ngay_trong_giao_an_id', $revisionDayIds)->delete();
            DB::table('ngay_trong_giao_an')->whereIn('id', $revisionDayIds)->delete();
            DB::table('nhat_ky_he_thong')->where('dinh_danh_doi_tuong', $revisionTemplateId)->delete();
            DB::table('giao_an_mau')->where('id', $revisionTemplateId)->delete();
        }
        $dayIds = DB::table('ngay_trong_giao_an')->where('giao_an_mau_id', $fixture['template_id'])->pluck('id');
        DB::table('bai_tap_trong_giao_an')->whereIn('ngay_trong_giao_an_id', $dayIds)->delete();
        DB::table('ngay_trong_giao_an')->whereIn('id', $dayIds)->delete();
        DB::table('giao_an_mau')->where('id', $fixture['template_id'])->delete();
        DB::table('bai_tap_dung_cu')->where('bai_tap_id', $fixture['exercise_id'])->delete();
        DB::table('bai_tap_nhom_co')->where('bai_tap_id', $fixture['exercise_id'])->delete();
        DB::table('bai_tap')->where('id', $fixture['exercise_id'])->delete();
        DB::table('bai_tap')->where('id', $fixture['revision_exercise_id'])->delete();
        DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $fixture['package_id'])->delete();
        DB::table('goi_tap')->where('id', $fixture['package_id'])->delete();
        DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $fixture['admin_id'])->delete();
        DB::table('phan_quyen_nguoi_dung')->where('id', $fixture['assignment_id'])->delete();
        DB::table('nguoi_dung')->where('id', $fixture['admin_id'])->delete();
        DB::table('chi_nhanh')->where('id', $fixture['branch_id'])->delete();
    }
}
