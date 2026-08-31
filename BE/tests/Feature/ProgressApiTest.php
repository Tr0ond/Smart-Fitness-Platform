<?php

namespace Tests\Feature;

use App\Models\BaiTapTrongPhien;
use App\Models\ChiSoCoThe;
use App\Models\HiepTap;
use App\Models\KyHanHoiVien;
use App\Models\PhienTap;
use App\Services\MembershipActivationService;
use App\Services\MembershipLifecycleService;
use App\Services\Progress\BodyMeasurementAuditService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class ProgressApiTest extends TestCase
{
    use CreatesPtFixtures;
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

    public function test_member_tao_history_latest_bmi_va_idempotency(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $entryId = (string) Str::uuid();
        $payload = [
            'measured_at' => '2026-08-29T03:00:00.000000+00:00',
            'weight_kg' => 72,
            'height_cm' => 180,
            'waist_cm' => 81.5,
            'notes' => '  Số đo buổi sáng  ',
            'entry_id' => strtoupper($entryId),
        ];
        $headers = $this->bearer($fixture['token']);

        $created = $this->withHeaders($headers)->postJson('/api/progress/body', $payload)
            ->assertCreated()
            ->assertJsonPath('data.weight_kg', '72.00')
            ->assertJsonPath('data.height_cm', '180.00')
            ->assertJsonPath('data.bmi', '22.22')
            ->assertJsonPath('data.bmi_status', 'available')
            ->assertJsonPath('data.notes', 'Số đo buổi sáng')
            ->assertJsonPath('data.entry_id', $entryId)
            ->assertJsonPath('data.replayed', false);
        $measurementId = (int) $created->json('data.id');
        $this->assertSame(1, ChiSoCoThe::query()->where('hoi_vien_id', $fixture['member_id'])->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'TAO_CHI_SO_CO_THE')->count());

        $this->withHeaders($headers)->postJson('/api/progress/body', $payload)
            ->assertCreated()->assertJsonPath('data.id', $measurementId)->assertJsonPath('data.replayed', true);
        $this->assertSame(1, ChiSoCoThe::query()->where('hoi_vien_id', $fixture['member_id'])->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'TAO_CHI_SO_CO_THE')->count());

        $this->withHeaders($headers)->postJson('/api/progress/body', array_merge($payload, ['weight_kg' => 73]))
            ->assertConflict()->assertJsonPath('code', 'BODY_MEASUREMENT_IDEMPOTENCY_CONFLICT');

        $olderId = (string) Str::uuid();
        $this->withHeaders($headers)->postJson('/api/progress/body', [
            'measured_at' => '2026-08-20T03:00:00.000000+00:00',
            'weight_kg' => 74,
            'height_cm' => 180,
            'entry_id' => $olderId,
        ])->assertCreated();
        $this->withHeaders($headers)->getJson('/api/progress/body?limit=1')
            ->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.entry_id', $entryId)
            ->assertJsonPath('data.next_cursor.before_id', $measurementId);
        $cursor = urlencode('2026-08-29T03:00:00.000000Z');
        $this->withHeaders($headers)->getJson("/api/progress/body?limit=1&before_measured_at=$cursor&before_id=$measurementId")
            ->assertOk()->assertJsonPath('data.items.0.entry_id', $olderId);
        $this->withHeaders($headers)->getJson('/api/progress/body/latest')
            ->assertOk()->assertJsonPath('data.id', $measurementId)->assertJsonPath('data.bmi', '22.22');
    }

    public function test_measurement_validation_blocks_authority_fields_and_missing_height_is_safe(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $headers = $this->bearer($fixture['token']);

        $this->withHeaders($headers)->getJson('/api/progress/body/latest')->assertOk()->assertJsonPath('data', null);
        $this->withHeaders($headers)->getJson('/api/progress/overview')
            ->assertOk()->assertJsonPath('data.latest_body_measurement', null)
            ->assertJsonPath('data.weight_trend', []);

        $this->withHeaders($headers)->postJson('/api/progress/body', [
            'measured_at' => '2026-08-29T03:00:00+00:00',
            'weight_kg' => 70,
            'entry_id' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('height_cm');

        $this->withHeaders($headers)->postJson('/api/progress/body', [
            'measured_at' => '2026-08-29T03:00:00+00:00',
            'weight_kg' => 70,
            'height_cm' => 175,
            'entry_id' => (string) Str::uuid(),
            'member_id' => $fixture['member_id'] + 100,
            'bmi' => 99,
        ])->assertUnprocessable()->assertJsonValidationErrors(['member_id', 'bmi']);
        $this->assertSame(0, ChiSoCoThe::query()->where('hoi_vien_id', $fixture['member_id'])->count());
    }

    public function test_progress_uses_only_completed_immutable_sessions_and_actual_sets_reps_weight(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $exercise = $this->taoBaiTapWorkout($fixture, ['ten_bai_tap' => 'Bài tiến độ']);
        $plan = $this->taoPlanWorkout($fixture, $exercise, '2026-08-20');
        $this->taoPhienHoanThanh($fixture, $plan, $exercise, '2026-08-20 03:00:00', 10, 20);
        $this->taoPhienHoanThanh($fixture, $plan, $exercise, '2026-08-27 03:00:00', 8, 25);
        $dangTap = $this->taoPhienDangTap($fixture, $plan, '2026-08-29 03:00:00');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-30 02:00:00', 'UTC'));

        $snapshot = [
            'sessions' => PhienTap::query()->orderBy('id')->get()->toArray(),
            'exercises' => BaiTapTrongPhien::query()->orderBy('id')->get()->toArray(),
            'sets' => HiepTap::query()->orderBy('id')->get()->toArray(),
        ];
        $headers = $this->bearer($fixture['token']);
        $query = '?from=2026-08-18&to=2026-08-30';

        $this->withHeaders($headers)->getJson('/api/progress/overview'.$query)
            ->assertOk()->assertJsonPath('data.completed_sessions_count', 2)
            ->assertJsonPath('data.training_frequency.active_days_count', 2)
            ->assertJsonPath('data.training_frequency.completed_sessions_per_7_days', '1.08')
            ->assertJsonPath('data.period.inclusive_days', 13);
        $this->withHeaders($headers)->getJson('/api/progress/exercises/'.$exercise.$query)
            ->assertOk()->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.sets_count', 1)
            ->assertJsonPath('data.items.0.sets.0.reps', 8)
            ->assertJsonPath('data.items.0.sets.0.weight_kg', '25.00')
            ->assertJsonPath('data.items.1.sets.0.reps', 10)
            ->assertJsonPath('data.items.1.sets.0.weight_kg', '20.00');
        $this->assertSame('DANG_TAP', PhienTap::query()->findOrFail($dangTap)->trang_thai);
        $this->assertSame($snapshot['sessions'], PhienTap::query()->orderBy('id')->get()->toArray());
        $this->assertSame($snapshot['exercises'], BaiTapTrongPhien::query()->orderBy('id')->get()->toArray());
        $this->assertSame($snapshot['sets'], HiepTap::query()->orderBy('id')->get()->toArray());

        $this->withHeaders($headers)->getJson('/api/progress/overview?from=2025-01-01&to=2026-08-30')
            ->assertUnprocessable()->assertJsonValidationErrors('to');
    }

    public function test_no_membership_progress_is_readable_without_activation_usage_or_quota(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $before = $this->membershipCounters();
        $headers = $this->bearer($fixture['token']);

        $this->withHeaders($headers)->postJson('/api/progress/body', [
            'measured_at' => '2026-08-29T03:00:00+00:00',
            'weight_kg' => 68,
            'height_cm' => 172,
            'entry_id' => (string) Str::uuid(),
        ])->assertCreated();
        $this->withHeaders($headers)->getJson('/api/progress/overview')->assertOk();

        $this->assertSame($before, $this->membershipCounters());
    }

    public function test_expired_membership_progress_is_readable_and_unchanged(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $hienTai = CarbonImmutable::now('UTC');
        $quaKhu = $hienTai->subDays(40);
        CarbonImmutable::setTestNow($quaKhu);
        $package = $this->taoGoiTapMembership($fixture);
        $term = $this->taoDonVaSnapshotMembership($fixture['member_id'], $package['package'], $quaKhu);
        $this->xacNhanVaCapMembership($term, $quaKhu);
        $usage = $this->taoUsageMembership($term->fresh(), $fixture['user']->getKey(), 'VAO_PHONG_TAP', $quaKhu);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        CarbonImmutable::setTestNow($hienTai);
        app(MembershipLifecycleService::class)->doiChieuHoiVien($fixture['member_id'], $hienTai);
        $this->assertSame('HET_HAN', $term->fresh()->trang_thai);
        $before = $this->membershipCounters();

        $this->withHeaders($this->bearer($fixture['token']))->getJson('/api/progress/overview')->assertOk();
        $this->withHeaders($this->bearer($fixture['token']))->getJson('/api/progress/body')->assertOk();

        $this->assertSame($before, $this->membershipCounters());
        $this->assertSame('HET_HAN', $term->fresh()->trang_thai);
    }

    public function test_current_pt_can_read_but_old_foreign_pt_and_member_pt_route_are_blocked(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $now = CarbonImmutable::now('UTC');
        DB::table('chi_so_co_the')->insert([
            'hoi_vien_id' => $fixture['member_a_id'],
            'do_luc' => $now,
            'can_nang_kg' => 70,
            'chieu_cao_cm' => 175,
            'vong_eo_cm' => null,
            'ghi_chu' => null,
            'ma_lan_ghi' => (string) Str::uuid(),
            'ngay_tao' => $now,
        ]);
        $ptAToken = $this->taoTheTruyCapThuCong($fixture['pt_a']['user'], $now, $now->addDay());
        $ptBToken = $this->taoTheTruyCapThuCong($fixture['pt_b']['user'], $now, $now->addDay());
        $memberToken = $this->taoTheTruyCapThuCong($fixture['member_a']['user'], $now, $now->addDay());
        $before = $this->membershipCounters();

        $this->withHeaders($this->bearer($ptAToken))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/overview')->assertOk();
        $this->withHeaders($this->bearer($ptAToken))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/body')
            ->assertOk()->assertJsonCount(1, 'data.items');
        $this->withHeaders($this->bearer($ptBToken))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/overview')->assertNotFound();
        $this->withHeaders($this->bearer($memberToken))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/overview')->assertForbidden();

        DB::table('phan_cong_huan_luyen_vien')->where('hoi_vien_id', $fixture['member_a_id'])
            ->update(['ngay_ket_thuc' => $now, 'ngay_cap_nhat' => $now]);
        $this->withHeaders($this->bearer($ptAToken))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/overview')->assertNotFound();
        $this->assertSame($before, $this->membershipCounters());
    }

    public function test_late_audit_failure_rolls_back_measurement(): void
    {
        $fixture = $this->taoHoiVienWorkout();
        $beforeAudit = DB::table('nhat_ky_he_thong')->count();
        $this->app->bind(BodyMeasurementAuditService::class, fn () => new class extends BodyMeasurementAuditService
        {
            public function ghiDaTao($actor, $chiSo, $thoiDiem): void
            {
                throw new RuntimeException('forced progress audit failure');
            }
        });

        $this->withHeaders($this->bearer($fixture['token']))->postJson('/api/progress/body', [
            'measured_at' => '2026-08-29T03:00:00+00:00',
            'weight_kg' => 70,
            'height_cm' => 175,
            'entry_id' => (string) Str::uuid(),
        ])->assertInternalServerError();

        $this->assertSame(0, ChiSoCoThe::query()->where('hoi_vien_id', $fixture['member_id'])->count());
        $this->assertSame($beforeAudit, DB::table('nhat_ky_he_thong')->count());
    }

    private function taoPhienHoanThanh(array $fixture, array $plan, int $exercise, string $thoiDiem, int $reps, float $weight): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($thoiDiem, 'UTC'));
        $schedule = $this->taoLichWorkout($fixture, $plan, CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString());
        $service = app(WorkoutSessionService::class);
        $session = $service->batDau($fixture['user'], $schedule->getKey(), (string) Str::uuid());
        $service->ghiHiep(
            $fixture['user'],
            $session['id'],
            $session['exercises'][0]['id'],
            ['order' => 1, 'reps' => $reps, 'weight_kg' => $weight, 'actual_rest_seconds' => 60],
            (string) Str::uuid(),
        );
        $service->hoanThanh($fixture['user'], $session['id'], (string) Str::uuid());
        $this->assertSame($exercise, (int) BaiTapTrongPhien::query()->where('phien_tap_id', $session['id'])->value('bai_tap_id'));
    }

    private function taoPhienDangTap(array $fixture, array $plan, string $thoiDiem): int
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($thoiDiem, 'UTC'));
        $schedule = $this->taoLichWorkout($fixture, $plan, CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString());

        return (int) app(WorkoutSessionService::class)
            ->batDau($fixture['user'], $schedule->getKey(), (string) Str::uuid())['id'];
    }

    private function membershipCounters(): array
    {
        return [
            'registrations' => DB::table('dang_ky_goi_tap')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
            'terms' => KyHanHoiVien::query()->orderBy('id')->get()->toArray(),
            'usage' => DB::table('su_dung_quyen_loi')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all(),
        ];
    }
}
