<?php

namespace Tests\Feature;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\BaiTapTrongPhien;
use App\Models\BuoiTapDuKien;
use App\Models\HiepTap;
use App\Models\KeHoachTap;
use App\Models\PhienTap;
use App\Services\MembershipActivationService;
use App\Services\MembershipLifecycleService;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class WorkoutFoundationTest extends TestCase
{
    use CreatesWorkoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-30 02:00:00', 'UTC'));
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_template_api_chi_tra_template_hoat_dong_va_khong_co_mutation_route(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $baiTapId = $this->taoBaiTapWorkout($fixture);
        $active = $this->taoGiaoAnMauWorkout($fixture, $baiTapId);
        $inactive = $this->taoGiaoAnMauWorkout($fixture, $baiTapId, 'NGUNG_SU_DUNG');

        $this->withHeaders($this->bearer($fixture['token']))->getJson('/api/workout/templates')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active);
        $this->withHeaders($this->bearer($fixture['token']))->getJson('/api/workout/templates/'.$active)
            ->assertOk()->assertJsonPath('data.days.0.exercises.0.exercise_id', $baiTapId)
            ->assertJsonMissingPath('data.nguoi_tao_id');
        $this->withHeaders($this->bearer($fixture['token']))->getJson('/api/workout/templates/'.$inactive)->assertNotFound();
        $this->withHeaders($this->bearer($fixture['token']))->postJson('/api/workout/templates', [])->assertMethodNotAllowed();
    }

    public function test_plan_read_self_only_mot_active_va_version_cu_bat_bien(): void
    {
        $a = $this->taoHoiVienWorkout();
        $b = $this->taoHoiVienWorkout();
        $baiA = $this->taoBaiTapWorkout($a, ['ten_bai_tap' => 'Bài A']);
        $baiB = $this->taoBaiTapWorkout($a, ['ten_bai_tap' => 'Bài B']);
        $planA = $this->taoPlanWorkout($a, $baiA);
        $planB = $this->taoPlanWorkout($a, $baiB, null, false);

        app(WorkoutPlanService::class)->kichHoat($a['user'], $planB['plan']->getKey());
        $this->assertSame(1, KeHoachTap::query()->where('hoi_vien_id', $a['member_id'])->where('trang_thai', 'DANG_SU_DUNG')->count());
        $this->assertSame('LUU_TRU', $planA['plan']->fresh()->trang_thai);

        $v1ExerciseName = DB::table('bai_tap_trong_ke_hoach')->where('ngay_trong_ke_hoach_id', $planB['day']->getKey())->value('ten_bai_tap');
        $definition = $this->cauTrucWorkout($baiA, null, 'Kế hoạch phiên bản 2');
        $v2 = app(WorkoutPlanService::class)->taoPhienBanTiepTheo($a['user'], $planB['plan']->getKey(), $definition);
        $this->assertSame(2, (int) $v2->so_phien_ban);
        $this->assertSame($v1ExerciseName, DB::table('bai_tap_trong_ke_hoach')->where('ngay_trong_ke_hoach_id', $planB['day']->getKey())->value('ten_bai_tap'));

        $this->withHeaders($this->bearer($a['token']))->getJson('/api/workout/plans/current')
            ->assertOk()->assertJsonPath('data.id', $planB['plan']->getKey())->assertJsonPath('data.current_version.number', 2);
        $this->withHeaders($this->bearer($b['token']))->getJson('/api/workout/plans/'.$planB['plan']->getKey())->assertNotFound();
    }

    public function test_schedule_range_slot_release_va_hold_semantics(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $exercise = $this->taoBaiTapWorkout($fixture);
        $plan = $this->taoPlanWorkout($fixture, $exercise);
        $schedule = $this->taoLichWorkout($fixture, $plan);
        $service = app(WorkoutScheduleService::class);

        try {
            $this->taoLichWorkout($fixture, $plan);
            $this->fail('Duplicate valid schedule must fail.');
        } catch (WorkoutWorkflowException $exception) {
            $this->assertSame('WORKOUT_DATE_SLOT_CONFLICT', $exception->safeCode);
        }

        $service->huyNoiBo($fixture['member_id'], $schedule->getKey());
        $replacement = $this->taoLichWorkout($fixture, $plan);
        $this->assertNull($schedule->fresh()->ngay_tap_con_hieu_luc);
        $service->boQua($fixture['user'], $replacement->getKey());
        $this->assertNotNull($replacement->fresh()->ngay_tap_con_hieu_luc);
        $this->expectException(WorkoutWorkflowException::class);
        $this->taoLichWorkout($fixture, $plan);
    }

    public function test_schedule_api_bounded_ordered_and_self_owned(): void
    {
        $a = $this->taoHoiVienWorkout();
        $b = $this->taoHoiVienWorkout();
        $exercise = $this->taoBaiTapWorkout($a);
        $plan = $this->taoPlanWorkout($a, $exercise);
        $schedule = $this->taoLichWorkout($a, $plan);
        $date = CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString();

        $this->withHeaders($this->bearer($a['token']))->getJson("/api/workout/schedule?from=$date&to=$date")
            ->assertOk()->assertJsonPath('data.0.id', $schedule->getKey());
        $this->withHeaders($this->bearer($b['token']))->getJson("/api/workout/schedule?from=$date&to=$date")
            ->assertOk()->assertJsonCount(0, 'data');
        $this->withHeaders($this->bearer($a['token']))->getJson('/api/workout/schedule?from=2026-01-01&to=2027-01-01')->assertUnprocessable();
    }

    public function test_version_moi_chuyen_lich_tuong_lai_sang_da_thay_the_va_tao_hang_moi(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $a = $this->taoBaiTapWorkout($fixture, ['ten_bai_tap' => 'A']);
        $b = $this->taoBaiTapWorkout($fixture, ['ten_bai_tap' => 'B']);
        $plan = $this->taoPlanWorkout($fixture, $a);
        $old = $this->taoLichWorkout($fixture, $plan);
        $v2 = app(WorkoutPlanService::class)->taoPhienBanTiepTheo(
            $fixture['user'], $plan['plan']->getKey(), $this->cauTrucWorkout($b, null, 'Version thay thế'),
        );
        $date = CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString();
        $created = app(WorkoutScheduleService::class)->lapLich(
            $fixture['member_id'], $plan['plan']->getKey(), $v2->getKey(), $date, $date,
        );

        $this->assertSame('DA_THAY_THE', $old->fresh()->trang_thai);
        $this->assertNull($old->fresh()->ngay_tap_con_hieu_luc);
        $this->assertCount(1, $created);
        $this->assertSame($old->getKey(), $created[0]['replaces_scheduled_workout_id']);
        $this->assertSame('CHUA_TAP', $created[0]['status']);
        $this->assertSame(1, BuoiTapDuKien::query()->where('hoi_vien_id', $fixture['member_id'])->whereNotNull('ngay_tap_con_hieu_luc')->count());
    }

    public function test_session_start_materialize_set_complete_replay_va_immutability(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $exercise = $this->taoBaiTapWorkout($fixture, ['ten_bai_tap' => 'Snapshot A', 'huong_dan' => 'Hướng dẫn A']);
        $plan = $this->taoPlanWorkout($fixture, $exercise);
        $schedule = $this->taoLichWorkout($fixture, $plan);
        $startKey = (string) Str::uuid();
        $headers = array_merge($this->bearer($fixture['token']), ['Idempotency-Key' => $startKey]);

        $start = $this->withHeaders($headers)->postJson('/api/workout/scheduled-sessions/'.$schedule->getKey().'/start')
            ->assertCreated()->assertJsonPath('data.status', 'DANG_TAP')->assertJsonCount(1, 'data.exercises');
        $sessionId = (int) $start->json('data.id');
        $sessionExerciseId = (int) $start->json('data.exercises.0.id');
        $this->withHeaders($headers)->postJson('/api/workout/scheduled-sessions/'.$schedule->getKey().'/start')
            ->assertCreated()->assertJsonPath('data.id', $sessionId)->assertJsonPath('data.replayed', true);

        $setKey = (string) Str::uuid();
        $setHeaders = array_merge($this->bearer($fixture['token']), ['Idempotency-Key' => $setKey]);
        $payload = ['order' => 1, 'reps' => 10, 'weight_kg' => 25.5, 'actual_rest_seconds' => 80];
        $this->withHeaders($setHeaders)->postJson("/api/workout/sessions/$sessionId/exercises/$sessionExerciseId/sets", $payload)
            ->assertCreated()->assertJsonPath('data.reps', 10);
        $this->withHeaders($setHeaders)->postJson("/api/workout/sessions/$sessionId/exercises/$sessionExerciseId/sets", $payload)
            ->assertCreated()->assertJsonPath('data.replayed', true);

        $completeKey = (string) Str::uuid();
        $completeHeaders = array_merge($this->bearer($fixture['token']), ['Idempotency-Key' => $completeKey]);
        $this->withHeaders($completeHeaders)->postJson("/api/workout/sessions/$sessionId/complete", ['notes' => 'Hoàn tất'])
            ->assertOk()->assertJsonPath('data.status', 'HOAN_THANH');
        $this->withHeaders($completeHeaders)->postJson("/api/workout/sessions/$sessionId/complete", ['notes' => 'Hoàn tất'])
            ->assertOk()->assertJsonPath('data.replayed', true);
        $this->withHeaders(array_merge($this->bearer($fixture['token']), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson("/api/workout/sessions/$sessionId/exercises/$sessionExerciseId/sets", ['order' => 2, 'reps' => 8])
            ->assertConflict()->assertJsonPath('code', 'WORKOUT_SESSION_IMMUTABLE');
        $this->assertSame('HOAN_THANH', $schedule->fresh()->trang_thai);
        $this->assertSame(1, PhienTap::query()->where('buoi_tap_du_kien_id', $schedule->getKey())->count());
    }

    public function test_workout_khong_membership_khong_usage_khong_activation_quota(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $exercise = $this->taoBaiTapWorkout($fixture);
        $plan = $this->taoPlanWorkout($fixture, $exercise);
        $schedule = $this->taoLichWorkout($fixture, $plan);
        $before = [
            'usage' => DB::table('su_dung_quyen_loi')->count(),
            'terms' => DB::table('ky_han_hoi_vien')->count(),
            'registrations' => DB::table('dang_ky_goi_tap')->count(),
        ];

        $session = app(WorkoutSessionService::class)->batDau($fixture['user'], $schedule->getKey(), (string) Str::uuid());
        app(WorkoutSessionService::class)->ghiHiep($fixture['user'], $session['id'], $session['exercises'][0]['id'], ['order' => 1, 'reps' => 0, 'weight_kg' => null, 'actual_rest_seconds' => null], (string) Str::uuid());
        app(WorkoutSessionService::class)->hoanThanh($fixture['user'], $session['id'], (string) Str::uuid());

        $this->assertSame($before['usage'], DB::table('su_dung_quyen_loi')->count());
        $this->assertSame($before['terms'], DB::table('ky_han_hoi_vien')->count());
        $this->assertSame($before['registrations'], DB::table('dang_ky_goi_tap')->count());
    }

    public function test_membership_het_han_van_thuc_hien_workout_va_khong_doi_quota(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $hienTai = CarbonImmutable::now('UTC');
        $quaKhu = $hienTai->subDays(40);
        CarbonImmutable::setTestNow($quaKhu);
        $package = $this->taoGoiTapMembership($fixture);
        $term = $this->taoDonVaSnapshotMembership($fixture['member_id'], $package['package'], $quaKhu);
        $this->xacNhanVaCapMembership($term, $quaKhu);
        $term = $term->fresh();
        $usage = $this->taoUsageMembership($term, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $quaKhu);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        CarbonImmutable::setTestNow($hienTai);
        app(MembershipLifecycleService::class)->doiChieuHoiVien($fixture['member_id'], $hienTai);
        $term = $term->fresh();
        $this->assertSame('HET_HAN', $term->trang_thai);
        $quota = [(int) $term->so_buoi_huan_luyen_vien_da_dung, (int) $term->so_luot_tro_ly_da_dung, (int) $term->so_luot_tro_ly_dang_giu];
        $usageCount = DB::table('su_dung_quyen_loi')->count();

        $exercise = $this->taoBaiTapWorkout($fixture);
        $plan = $this->taoPlanWorkout($fixture, $exercise);
        $schedule = $this->taoLichWorkout($fixture, $plan);
        $session = app(WorkoutSessionService::class)->batDau($fixture['user'], $schedule->getKey(), (string) Str::uuid());
        app(WorkoutSessionService::class)->hoanThanh($fixture['user'], $session['id'], (string) Str::uuid());

        $term = $term->fresh();
        $this->assertSame($quota, [(int) $term->so_buoi_huan_luyen_vien_da_dung, (int) $term->so_luot_tro_ly_da_dung, (int) $term->so_luot_tro_ly_dang_giu]);
        $this->assertSame($usageCount, DB::table('su_dung_quyen_loi')->count());
        $this->assertSame('HET_HAN', $term->trang_thai);
    }

    public function test_workout_routes_yeu_cau_xac_thuc_member_hieu_luc(): void
    {
        $this->getJson('/api/workout/plans')->assertUnauthorized();
        $pt = $this->taoNguoiDungAuth(['PT']);
        $token = $this->taoTheTruyCapThuCong($pt['user'], CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addDay());
        $this->withHeaders($this->bearer($token))->getJson('/api/workout/plans')->assertForbidden();

        $member = $this->taoHoiVienWorkout();
        DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $member['user']->getKey())->update(['thu_hoi_luc' => CarbonImmutable::now('UTC')]);
        $this->withHeaders($this->bearer($member['token']))->getJson('/api/workout/plans')->assertForbidden();
    }

    public function test_plan_v2_khong_sua_completed_session_snapshot_va_sets(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $a = $this->taoBaiTapWorkout($fixture, ['ten_bai_tap' => 'Bài lịch sử A']);
        $b = $this->taoBaiTapWorkout($fixture, ['ten_bai_tap' => 'Bài tương lai B']);
        $plan = $this->taoPlanWorkout($fixture, $a);
        $schedule = $this->taoLichWorkout($fixture, $plan);
        $session = app(WorkoutSessionService::class)->batDau($fixture['user'], $schedule->getKey(), (string) Str::uuid());
        app(WorkoutSessionService::class)->ghiHiep($fixture['user'], $session['id'], $session['exercises'][0]['id'], ['order' => 1, 'reps' => 11, 'weight_kg' => 30, 'actual_rest_seconds' => 70], (string) Str::uuid());
        app(WorkoutSessionService::class)->hoanThanh($fixture['user'], $session['id'], (string) Str::uuid());
        $snapshot = [
            'session' => PhienTap::query()->findOrFail($session['id'])->toArray(),
            'exercise' => BaiTapTrongPhien::query()->where('phien_tap_id', $session['id'])->firstOrFail()->toArray(),
            'set' => HiepTap::query()->where('bai_tap_trong_phien_id', $session['exercises'][0]['id'])->firstOrFail()->toArray(),
        ];

        app(WorkoutPlanService::class)->taoPhienBanTiepTheo($fixture['user'], $plan['plan']->getKey(), $this->cauTrucWorkout($b, null, 'Version 2'));
        $this->assertSame($snapshot['session'], PhienTap::query()->findOrFail($session['id'])->toArray());
        $this->assertSame($snapshot['exercise'], BaiTapTrongPhien::query()->where('phien_tap_id', $session['id'])->firstOrFail()->toArray());
        $this->assertSame($snapshot['set'], HiepTap::query()->where('bai_tap_trong_phien_id', $session['exercises'][0]['id'])->firstOrFail()->toArray());
    }

    public function test_start_sai_ngay_foreign_schedule_va_skipped_deu_bi_chan(): void
    {
        $a = $this->taoHoiVienWorkout();
        $b = $this->taoHoiVienWorkout();
        $exercise = $this->taoBaiTapWorkout($a);
        $plan = $this->taoPlanWorkout($a, $exercise, '2026-08-31');
        $future = $this->taoLichWorkout($a, $plan, '2026-08-31');

        $this->withHeaders(array_merge($this->bearer($a['token']), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/workout/scheduled-sessions/'.$future->getKey().'/start')->assertConflict()->assertJsonPath('code', 'SCHEDULED_DATE_MISMATCH');
        $this->withHeaders(array_merge($this->bearer($b['token']), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/workout/scheduled-sessions/'.$future->getKey().'/start')->assertNotFound();
        app(WorkoutScheduleService::class)->boQua($a['user'], $future->getKey());
        $this->withHeaders(array_merge($this->bearer($a['token']), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/workout/scheduled-sessions/'.$future->getKey().'/start')->assertConflict()->assertJsonPath('code', 'SCHEDULED_WORKOUT_NOT_STARTABLE');
    }

    public function test_database_chan_free_workout_va_hai_session_cung_lich(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $now = CarbonImmutable::now('UTC');
        try {
            DB::table('phien_tap')->insert([
                'hoi_vien_id' => $fixture['member_id'], 'buoi_tap_du_kien_id' => null, 'ma_lan_bat_dau' => (string) Str::uuid(),
                'ten_buoi_tap' => 'Free', 'bat_dau_luc' => $now, 'ket_thuc_luc' => null, 'trang_thai' => 'DANG_TAP',
                'ghi_chu' => null, 'phien_ban_du_lieu' => 1, 'ma_lan_hoan_thanh' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
            ]);
            $this->fail('Free Workout must be blocked.');
        } catch (QueryException $exception) {
            $this->assertSame(1048, (int) ($exception->errorInfo[1] ?? 0));
        }

        $exercise = $this->taoBaiTapWorkout($fixture);
        $plan = $this->taoPlanWorkout($fixture, $exercise);
        $schedule = $this->taoLichWorkout($fixture, $plan);
        app(WorkoutSessionService::class)->batDau($fixture['user'], $schedule->getKey(), (string) Str::uuid());
        $this->expectException(QueryException::class);
        PhienTap::query()->create([
            'hoi_vien_id' => $fixture['member_id'], 'buoi_tap_du_kien_id' => $schedule->getKey(), 'ma_lan_bat_dau' => (string) Str::uuid(),
            'ten_buoi_tap' => 'Duplicate', 'bat_dau_luc' => $now, 'ket_thuc_luc' => null, 'trang_thai' => 'DANG_TAP',
            'ghi_chu' => null, 'phien_ban_du_lieu' => 1, 'ma_lan_hoan_thanh' => null, 'ngay_tao' => $now, 'ngay_cap_nhat' => $now,
        ]);
    }
}
