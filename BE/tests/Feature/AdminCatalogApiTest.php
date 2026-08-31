<?php

namespace Tests\Feature;

use App\Models\GoiTap;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class AdminCatalogApiTest extends TestCase
{
    use CreatesWorkoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 09:00:00.000000', 'UTC'));
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_admin_can_create_and_manage_a_fresh_package_catalog_without_hard_delete(): void
    {
        [$admin, $headers] = $this->admin();
        $payload = $this->packagePayload('FRESH_'.strtoupper(bin2hex(random_bytes(4))));

        $created = $this->postJson('/api/admin/packages', $payload, $headers)
            ->assertCreated()
            ->assertJsonPath('data.branch_id', $admin['branch_id'])
            ->assertJsonPath('data.configuration_version', 1)
            ->assertJsonPath('data.benefits.trainer_chat', true)
            ->assertJsonPath('data.benefits.direct_trainer_sessions', 0);
        $packageId = (int) $created->json('data.id');
        $this->assertSame(1, DB::table('goi_tap')->where('id', $packageId)->count());
        $this->assertSame(1, DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $packageId)->count());

        $this->getJson('/api/admin/packages/'.$packageId, $headers)
            ->assertOk()->assertJsonPath('data.code', $payload['code']);
        $this->patchJson('/api/admin/packages/'.$packageId, [
            'price' => 650000,
            'duration_days' => 45,
            'status' => 'NGUNG_BAN',
        ], $headers)->assertOk()
            ->assertJsonPath('data.price', 650000)
            ->assertJsonPath('data.duration_days', 45)
            ->assertJsonPath('data.configuration_version', 2);
        $this->putJson('/api/admin/packages/'.$packageId.'/benefits', [
            'gym_access' => false,
            'fitness_assistant' => true,
            'fitness_assistant_limit' => null,
            'trainer_chat' => true,
            'direct_trainer_sessions' => 4,
        ], $headers)->assertOk()
            ->assertJsonPath('data.configuration_version', 3)
            ->assertJsonPath('data.benefits.fitness_assistant_limit', null);

        $this->deleteJson('/api/admin/packages/'.$packageId, [], $headers)->assertMethodNotAllowed();
        $this->assertDatabaseHas('goi_tap', ['id' => $packageId, 'trang_thai' => 'NGUNG_BAN']);
    }

    public function test_catalog_updates_never_rewrite_old_membership_snapshot_and_new_purchase_uses_new_values(): void
    {
        [$admin, $headers] = $this->admin();
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $member['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $member['branch_id'] = $admin['branch_id'];
        $memberId = $this->taoHoSoHoiVien($member);
        $created = $this->postJson(
            '/api/admin/packages',
            $this->packagePayload('SNAP_'.strtoupper(bin2hex(random_bytes(4)))),
            $headers,
        )->assertCreated();
        $packageId = (int) $created->json('data.id');
        $package = GoiTap::query()->findOrFail($packageId);
        $oldTerm = $this->taoDonVaSnapshotMembership($memberId, $package);
        $this->xacNhanVaCapMembership($oldTerm);
        $oldSnapshot = DB::table('ky_han_hoi_vien')->where('id', $oldTerm->getKey())->first();

        $this->patchJson('/api/admin/packages/'.$packageId, [
            'name' => 'Gói catalog mới',
            'price' => 990000,
            'duration_days' => 90,
        ], $headers)->assertOk();
        $this->putJson('/api/admin/packages/'.$packageId.'/benefits', [
            'gym_access' => false,
            'fitness_assistant' => true,
            'fitness_assistant_limit' => 3,
            'trainer_chat' => false,
            'direct_trainer_sessions' => 8,
        ], $headers)->assertOk();

        $oldAfter = DB::table('ky_han_hoi_vien')->where('id', $oldTerm->getKey())->first();
        foreach ([
            'ten_goi', 'phien_ban_goi', 'gia_da_mua', 'thoi_han_ngay',
            'cho_phep_vao_phong_tap', 'cho_phep_tro_ly_tap_luyen', 'gioi_han_luot_tro_ly',
            'cho_phep_tro_chuyen_huan_luyen_vien', 'so_buoi_huan_luyen_vien',
        ] as $column) {
            $this->assertSame($oldSnapshot->{$column}, $oldAfter->{$column}, $column);
        }

        $newTerm = $this->taoDonVaSnapshotMembership($memberId, $package->refresh());
        $this->xacNhanVaCapMembership($newTerm);
        $newSnapshot = DB::table('ky_han_hoi_vien')->where('id', $newTerm->getKey())->sole();
        $this->assertSame('Gói catalog mới', $newSnapshot->ten_goi);
        $this->assertSame(3, (int) $newSnapshot->phien_ban_goi);
        $this->assertSame(990000, (int) $newSnapshot->gia_da_mua);
        $this->assertSame(90, (int) $newSnapshot->thoi_han_ngay);
        $this->assertSame(0, (int) $newSnapshot->cho_phep_vao_phong_tap);
        $this->assertSame(3, (int) $newSnapshot->gioi_han_luot_tro_ly);
        $this->assertSame(0, (int) $newSnapshot->cho_phep_tro_chuyen_huan_luyen_vien);
        $this->assertSame(8, (int) $newSnapshot->so_buoi_huan_luyen_vien);
    }

    public function test_package_validation_duplicate_scope_and_mass_assignment_are_fail_closed(): void
    {
        [$admin, $headers] = $this->admin();
        [$otherAdmin, $otherHeaders] = $this->admin();
        $code = 'SEC_'.strtoupper(bin2hex(random_bytes(4)));
        $payload = $this->packagePayload($code);
        $packageId = (int) $this->postJson('/api/admin/packages', $payload, $headers)
            ->assertCreated()->json('data.id');

        $this->postJson('/api/admin/packages', $payload, $headers)
            ->assertConflict()->assertJsonPath('code', 'PACKAGE_CONFLICT');
        $this->getJson('/api/admin/packages/'.$packageId, $otherHeaders)
            ->assertNotFound()->assertJsonPath('code', 'PACKAGE_NOT_FOUND');
        $this->patchJson('/api/admin/packages/'.$packageId, ['price' => 2], $otherHeaders)
            ->assertNotFound()->assertJsonPath('code', 'PACKAGE_NOT_FOUND');

        $authorityPayload = $this->packagePayload('AUTHORITY_'.strtoupper(bin2hex(random_bytes(3))));
        $authorityPayload['branch_id'] = $otherAdmin['branch_id'];
        $authorityPayload['configuration_version'] = 99;
        $this->postJson('/api/admin/packages', $authorityPayload, $headers)->assertUnprocessable();
        $this->assertDatabaseMissing('goi_tap', ['ma_goi' => $authorityPayload['code']]);

        $invalidBenefits = $payload['benefits'];
        $invalidBenefits['fitness_assistant'] = false;
        $invalidBenefits['fitness_assistant_limit'] = null;
        $this->putJson('/api/admin/packages/'.$packageId.'/benefits', $invalidBenefits, $headers)
            ->assertUnprocessable()->assertJsonPath('code', 'INVALID_PACKAGE_BENEFITS');
        $none = [
            'gym_access' => false,
            'fitness_assistant' => false,
            'fitness_assistant_limit' => 0,
            'trainer_chat' => false,
            'direct_trainer_sessions' => 0,
        ];
        $this->putJson('/api/admin/packages/'.$packageId.'/benefits', $none, $headers)
            ->assertUnprocessable()->assertJsonPath('code', 'INVALID_PACKAGE_BENEFITS');
        $this->assertSame($admin['branch_id'], (int) DB::table('goi_tap')->where('id', $packageId)->value('chi_nhanh_id'));
    }

    public function test_admin_exercise_api_preserves_and_semantics_and_deactivates_without_delete(): void
    {
        [, $headers] = $this->admin();
        $equipmentA = $this->createEquipment($headers, 'BENCH');
        $equipmentB = $this->createEquipment($headers, 'BARBELL');
        $muscle = $this->createMuscle($headers, 'CHEST');
        $code = 'EX_'.strtoupper(bin2hex(random_bytes(4)));

        $created = $this->postJson('/api/admin/exercises', [
            'code' => $code,
            'name' => 'Bench Press Catalog',
            'difficulty' => 'TRUNG_BINH',
            'instructions' => 'Giữ hai dụng cụ và thực hiện đúng kỹ thuật.',
            'status' => 'HOAT_DONG',
            'equipment_ids' => [$equipmentA, $equipmentB],
            'muscle_groups' => [['id' => $muscle, 'role' => 'CHINH']],
        ], $headers)->assertCreated()
            ->assertJsonPath('data.equipment_semantics', 'AND')
            ->assertJsonCount(2, 'data.equipment')
            ->assertJsonPath('data.content_version', 1);
        $exerciseId = (int) $created->json('data.id');

        $this->assertSame([$equipmentA, $equipmentB], DB::table('bai_tap_dung_cu')
            ->where('bai_tap_id', $exerciseId)->orderBy('dung_cu_id')->pluck('dung_cu_id')->map(fn ($id) => (int) $id)->all());
        $this->patchJson('/api/admin/exercises/'.$exerciseId, [
            'instructions' => 'Nội dung mới cho các kế hoạch tương lai.',
            'equipment_ids' => [$equipmentA],
        ], $headers)->assertOk()
            ->assertJsonCount(1, 'data.equipment')
            ->assertJsonPath('data.content_version', 2);
        $this->patchJson('/api/admin/exercises/'.$exerciseId, ['status' => 'NGUNG_SU_DUNG'], $headers)
            ->assertOk()->assertJsonPath('data.status', 'NGUNG_SU_DUNG')
            ->assertJsonPath('data.content_version', 2);
        $this->assertDatabaseHas('bai_tap', ['id' => $exerciseId, 'ma_bai_tap' => $code]);
        $this->deleteJson('/api/admin/exercises/'.$exerciseId, [], $headers)->assertMethodNotAllowed();

        $this->postJson('/api/admin/exercises', [
            'code' => 'BAD_LINK_'.strtoupper(bin2hex(random_bytes(3))),
            'name' => 'Invalid relation',
            'difficulty' => 'DE',
            'instructions' => 'Không được lưu.',
            'equipment_ids' => [999999999],
            'muscle_groups' => [],
        ], $headers)->assertUnprocessable()->assertJsonPath('code', 'INVALID_EQUIPMENT');
    }

    public function test_template_mutation_changes_future_catalog_only_and_preserves_plan_and_completed_history(): void
    {
        [$admin, $headers] = $this->admin();
        $member = $this->taoHoiVienWorkout();
        $exerciseA = $this->taoBaiTapWorkout($admin, ['ten_bai_tap' => 'Bài snapshot A']);
        $exerciseB = $this->taoBaiTapWorkout($admin, ['ten_bai_tap' => 'Bài catalog B']);
        $payload = $this->templatePayload('TPL_'.strtoupper(bin2hex(random_bytes(4))), $exerciseA);
        $templateId = (int) $this->postJson('/api/admin/workout-templates', $payload, $headers)
            ->assertCreated()->assertJsonPath('data.content_version', 1)->json('data.id');

        $structure = $this->cauTrucWorkout($exerciseA, null, 'Plan snapshot từ template');
        $structure['template_id'] = $templateId;
        $structure['template_name'] = $payload['name'];
        $planModel = app(WorkoutPlanService::class)->taoMoi(
            $member['user'],
            $structure,
            (string) Str::uuid(),
            true,
        )->fresh('phienBanHienTai.ngayTrongKeHoachs');
        $plan = [
            'plan' => $planModel,
            'version' => $planModel->phienBanHienTai,
            'day' => $planModel->phienBanHienTai->ngayTrongKeHoachs->firstOrFail(),
        ];
        $schedule = app(WorkoutScheduleService::class)->taoMotBuoi(
            $member['member_id'],
            $plan['plan']->getKey(),
            $plan['version']->getKey(),
            $plan['day']->getKey(),
            CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString(),
        );
        $session = app(WorkoutSessionService::class)->batDau($member['user'], $schedule->getKey(), (string) Str::uuid());
        app(WorkoutSessionService::class)->ghiHiep(
            $member['user'],
            $session['id'],
            $session['exercises'][0]['id'],
            ['order' => 1, 'reps' => 10, 'weight_kg' => 25, 'actual_rest_seconds' => 60],
            (string) Str::uuid(),
        );
        app(WorkoutSessionService::class)->hoanThanh($member['user'], $session['id'], (string) Str::uuid());
        $historyBefore = [
            'plan_exercise' => DB::table('bai_tap_trong_ke_hoach')->where('ngay_trong_ke_hoach_id', $plan['day']->getKey())->first(),
            'session' => DB::table('phien_tap')->where('id', $session['id'])->first(),
            'session_exercise' => DB::table('bai_tap_trong_phien')->where('phien_tap_id', $session['id'])->first(),
            'set' => DB::table('hiep_tap')->where('bai_tap_trong_phien_id', $session['exercises'][0]['id'])->first(),
        ];

        $oldDayIds = DB::table('ngay_trong_giao_an')->where('giao_an_mau_id', $templateId)->pluck('id');
        $oldTree = [
            'days' => DB::table('ngay_trong_giao_an')->whereIn('id', $oldDayIds)->orderBy('id')->get(),
            'exercises' => DB::table('bai_tap_trong_giao_an')->whereIn('ngay_trong_giao_an_id', $oldDayIds)->orderBy('id')->get(),
        ];
        $revision = $this->templatePayload('TPL_REV_'.strtoupper(bin2hex(random_bytes(4))), $exerciseB);
        $revision['new_code'] = $revision['code'];
        unset($revision['code']);
        $revision['expected_content_version'] = 1;
        $revision['name'] = 'Template catalog đã cập nhật';
        $newTemplateId = (int) $this->postJson(
            '/api/admin/workout-templates/'.$templateId.'/revisions',
            $revision,
            $headers,
        )->assertCreated()
            ->assertJsonPath('data.replaces_template_id', $templateId)
            ->assertJsonPath('data.content_version', 2)
            ->assertJsonPath('data.status', 'HOAT_DONG')
            ->assertJsonPath('data.previous_template_status', 'NGUNG_SU_DUNG')
            ->json('data.new_template_id');
        $this->assertSame('NGUNG_SU_DUNG', DB::table('giao_an_mau')->where('id', $templateId)->value('trang_thai'));
        $this->assertEquals($oldTree['days'], DB::table('ngay_trong_giao_an')->whereIn('id', $oldDayIds)->orderBy('id')->get());
        $this->assertEquals($oldTree['exercises'], DB::table('bai_tap_trong_giao_an')->whereIn('ngay_trong_giao_an_id', $oldDayIds)->orderBy('id')->get());
        $this->getJson('/api/admin/workout-templates/'.$newTemplateId, $headers)
            ->assertOk()->assertJsonPath('data.days.0.exercises.0.exercise_id', $exerciseB);
        $this->patchJson('/api/admin/workout-templates/'.$templateId, ['days' => $revision['days']], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('days');

        $this->assertEquals($historyBefore['plan_exercise'], DB::table('bai_tap_trong_ke_hoach')
            ->where('ngay_trong_ke_hoach_id', $plan['day']->getKey())->first());
        $this->assertEquals($historyBefore['session'], DB::table('phien_tap')->where('id', $session['id'])->first());
        $this->assertEquals($historyBefore['session_exercise'], DB::table('bai_tap_trong_phien')
            ->where('phien_tap_id', $session['id'])->first());
        $this->assertEquals($historyBefore['set'], DB::table('hiep_tap')
            ->where('bai_tap_trong_phien_id', $session['exercises'][0]['id'])->first());
        $this->getJson('/api/workout/templates/'.$templateId, $this->bearer($member['token']))->assertNotFound();
        $this->deleteJson('/api/admin/workout-templates/'.$templateId, [], $headers)->assertMethodNotAllowed();
    }

    public function test_template_tree_validation_is_atomic_and_authority_fields_are_prohibited(): void
    {
        [, $headers] = $this->admin();
        $exercise = (int) DB::table('bai_tap')->where('trang_thai', 'HOAT_DONG')->value('id');
        $code = 'ATOMIC_'.strtoupper(bin2hex(random_bytes(4)));
        $payload = $this->templatePayload($code, $exercise);
        $payload['sessions_per_week'] = 2;
        $this->postJson('/api/admin/workout-templates', $payload, $headers)
            ->assertUnprocessable()->assertJsonPath('code', 'INVALID_WORKOUT_TEMPLATE_STRUCTURE');
        $this->assertDatabaseMissing('giao_an_mau', ['ma_giao_an' => $code]);

        $authority = $this->templatePayload('AUTH_TPL_'.strtoupper(bin2hex(random_bytes(3))), $exercise);
        $authority['content_version'] = 500;
        $authority['created_by_id'] = 999999999;
        $this->postJson('/api/admin/workout-templates', $authority, $headers)->assertUnprocessable();
        $this->assertDatabaseMissing('giao_an_mau', ['ma_giao_an' => $authority['code']]);

        $exerciseAuthority = [
            'code' => 'AUTH_EX_'.strtoupper(bin2hex(random_bytes(3))),
            'name' => 'Authority blocked',
            'difficulty' => 'DE',
            'instructions' => 'Không lưu.',
            'equipment_ids' => [],
            'muscle_groups' => [],
            'content_version' => 10,
            'created_by_id' => 999999999,
        ];
        $this->postJson('/api/admin/exercises', $exerciseAuthority, $headers)->assertUnprocessable();
        $this->assertDatabaseMissing('bai_tap', ['ma_bai_tap' => $exerciseAuthority['code']]);
    }

    public function test_all_catalog_mutations_block_unauthenticated_and_non_admin_roles(): void
    {
        $routes = [
            ['POST', '/api/admin/packages'],
            ['PUT', '/api/admin/packages/1/benefits'],
            ['POST', '/api/admin/equipment'],
            ['PATCH', '/api/admin/equipment/1'],
            ['POST', '/api/admin/muscle-groups'],
            ['POST', '/api/admin/exercises'],
            ['PATCH', '/api/admin/exercises/1'],
            ['POST', '/api/admin/workout-templates'],
            ['PATCH', '/api/admin/workout-templates/1'],
            ['POST', '/api/admin/workout-templates/1/revisions'],
        ];
        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri, [])->assertUnauthorized();
        }

        foreach (['MEMBER', 'PT', 'RECEPTIONIST'] as $role) {
            $actor = $this->taoNguoiDungAuth([$role]);
            $token = $this->taoTheTruyCapThuCong(
                $actor['user'],
                CarbonImmutable::now('UTC'),
                CarbonImmutable::now('UTC')->addHour(),
            );
            foreach ($routes as [$method, $uri]) {
                $this->json($method, $uri, [], $this->bearer($token))->assertForbidden();
            }
        }
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function admin(): array
    {
        $fixture = $this->taoNguoiDungAuth(['ADMIN']);
        $token = $this->taoTheTruyCapThuCong(
            $fixture['user'],
            CarbonImmutable::now('UTC'),
            CarbonImmutable::now('UTC')->addHour(),
        );

        return [$fixture, $this->bearer($token)];
    }

    /** @return array<string, mixed> */
    private function packagePayload(string $code): array
    {
        return [
            'code' => $code,
            'name' => 'Gói API '.$code,
            'price' => 450000,
            'duration_days' => 30,
            'description' => 'Catalog tạo từ Admin API.',
            'status' => 'DANG_BAN',
            'benefits' => [
                'gym_access' => true,
                'fitness_assistant' => true,
                'fitness_assistant_limit' => 10,
                'trainer_chat' => true,
                'direct_trainer_sessions' => 0,
            ],
        ];
    }

    private function createEquipment(array $headers, string $prefix): int
    {
        return (int) $this->postJson('/api/admin/equipment', [
            'code' => $prefix.'_'.strtoupper(bin2hex(random_bytes(3))),
            'name' => 'Dụng cụ '.$prefix,
            'status' => 'HOAT_DONG',
        ], $headers)->assertCreated()->json('data.id');
    }

    private function createMuscle(array $headers, string $prefix): int
    {
        return (int) $this->postJson('/api/admin/muscle-groups', [
            'code' => $prefix.'_'.strtoupper(bin2hex(random_bytes(3))),
            'name' => 'Nhóm cơ '.$prefix,
        ], $headers)->assertCreated()->json('data.id');
    }

    /** @return array<string, mixed> */
    private function templatePayload(string $code, int $exerciseId): array
    {
        return [
            'code' => $code,
            'name' => 'Giáo án '.$code,
            'description' => 'Cây giáo án kiểm thử Admin Catalog.',
            'goal' => 'TANG_SUC_MANH',
            'level' => 'MOI_BAT_DAU',
            'sessions_per_week' => 1,
            'status' => 'HOAT_DONG',
            'days' => [[
                'order' => 1,
                'name' => 'Ngày thứ nhất',
                'estimated_minutes' => 60,
                'exercises' => [[
                    'exercise_id' => $exerciseId,
                    'order' => 1,
                    'target_sets' => 3,
                    'min_reps' => 8,
                    'max_reps' => 12,
                    'rest_seconds' => 90,
                    'notes' => 'Mục tiêu catalog.',
                ]],
            ]],
        ];
    }
}
