<?php

namespace Tests\Feature;

use App\Models\LanThanhToan;
use App\Services\Gym\GymCheckInService;
use App\Services\Gym\GymQrService;
use App\Services\MembershipActivationService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class AdminDashboardApiTest extends TestCase
{
    use CreatesPtFixtures;
    use CreatesWorkoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 01:00:00', 'UTC'));
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_admin_dashboard_metrics_branch_timezone_bounds_and_read_only(): void
    {
        $fixture = $this->taoDashboardFixture();
        $snapshot = $this->snapshotNguonDashboard();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $response = $this->withHeaders($this->bearer($fixture['admin_token']))
            ->getJson('/api/admin/dashboard?from=2026-08-30&to=2026-08-31')
            ->assertOk()
            ->assertJsonPath('data.branch.id', $fixture['admin']['branch_id'])
            ->assertJsonPath('data.branch.timezone', 'Asia/Ho_Chi_Minh')
            ->assertJsonPath('data.period.inclusive_days', 2)
            ->assertJsonPath('data.accounts.active_members_count', 2)
            ->assertJsonPath('data.accounts.active_trainers_count', 1)
            ->assertJsonPath('data.memberships.active_terms_count', 1)
            ->assertJsonPath('data.memberships.awaiting_activation_terms_count', 1)
            ->assertJsonPath('data.activity.check_ins_today_count', 1)
            ->assertJsonPath('data.activity.completed_workouts_today_count', 1)
            ->assertJsonPath('data.activity.completed_workouts_in_period_count', 1)
            ->assertJsonPath('data.payments.successful_in_period_count', 2)
            ->assertJsonPath('data.payments.reconciliation_required_count', 1)
            ->assertJsonPath('data.pt.active_assignments_count', 1);
        $this->assertLessThanOrEqual(25, $queries, 'Dashboard phải dùng aggregate query hữu hạn, không N+1 theo số hàng.');
        $this->assertSame($snapshot, $this->snapshotNguonDashboard());

        $body = (string) $response->getContent();
        foreach (['thu_dien_tu', 'mat_khau', 'tin_nhan', 'noi_dung_yeu_cau', 'du_lieu_da_loc', 'checkout_url', 'so_tien_da_nhan'] as $sensitive) {
            $this->assertStringNotContainsString($sensitive, $body);
        }

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 17:01:00', 'UTC'));
        $this->withHeaders($this->bearer($fixture['admin_token']))->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.period.to', '2026-09-01')
            ->assertJsonPath('data.activity.check_ins_today_count', 0)
            ->assertJsonPath('data.activity.completed_workouts_today_count', 0)
            ->assertJsonPath('data.activity.completed_workouts_in_period_count', 1);
    }

    public function test_dashboard_is_admin_only_and_revalidates_role(): void
    {
        $fixture = $this->taoDashboardFixture(taoDuLieu: false);
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
        foreach (['member_token', 'pt_token', 'receptionist_token'] as $tokenKey) {
            $this->withHeaders($this->bearer($fixture[$tokenKey]))->getJson('/api/admin/dashboard')->assertForbidden();
        }

        DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $fixture['admin']['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'ADMIN')
            ->update(['phan_quyen_nguoi_dung.thu_hoi_luc' => CarbonImmutable::now('UTC')]);
        $this->withHeaders($this->bearer($fixture['admin_token']))->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_dashboard_rejects_unbounded_or_client_selected_branch(): void
    {
        $fixture = $this->taoDashboardFixture(taoDuLieu: false);
        $headers = $this->bearer($fixture['admin_token']);

        $this->withHeaders($headers)->getJson('/api/admin/dashboard?from=2025-01-01&to=2026-08-31')
            ->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->withHeaders($headers)->getJson('/api/admin/dashboard?from=2026-08-31')
            ->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->withHeaders($headers)->getJson('/api/admin/dashboard?branch_id=999')
            ->assertUnprocessable()->assertJsonValidationErrors('branch_id');
    }

    /** @return array<string,mixed> */
    private function taoDashboardFixture(bool $taoDuLieu = true): array
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $memberA = $this->taoNguoiDungAuth(['MEMBER']);
        $memberB = $this->taoNguoiDungAuth(['MEMBER']);
        $pt = $this->taoNguoiDungAuth(['PT']);
        $receptionist = $this->taoNguoiDungAuth(['RECEPTIONIST']);
        $foreignMember = $this->taoNguoiDungAuth(['MEMBER']);
        foreach ([$memberA, $memberB, $pt, $receptionist] as $actor) {
            DB::table('nguoi_dung')->where('id', $actor['user']->getKey())
                ->update(['chi_nhanh_id' => $admin['branch_id']]);
            $actor['user']->refresh();
        }
        $memberAId = $this->taoHoSoHoiVien($memberA);
        $memberBId = $this->taoHoSoHoiVien($memberB);
        $ptId = $this->taoHoSoHuanLuyenVien($pt);
        $this->taoHoSoHoiVien($foreignMember);
        $now = CarbonImmutable::now('UTC');
        $fixture = [
            'admin' => $admin,
            'member_a' => $memberA,
            'member_b' => $memberB,
            'pt' => $pt,
            'receptionist' => $receptionist,
            'member_a_id' => $memberAId,
            'member_b_id' => $memberBId,
            'pt_id' => $ptId,
            'admin_token' => $this->taoTheTruyCapThuCong($admin['user'], $now, $now->addDays(2)),
            'member_token' => $this->taoTheTruyCapThuCong($memberA['user'], $now, $now->addDays(2)),
            'pt_token' => $this->taoTheTruyCapThuCong($pt['user'], $now, $now->addDays(2)),
            'receptionist_token' => $this->taoTheTruyCapThuCong($receptionist['user'], $now, $now->addDays(2)),
        ];
        if (! $taoDuLieu) {
            return $fixture;
        }

        $goi = $this->taoGoiTapMembership($admin, [], ['cho_phep_vao_phong_tap' => true]);
        $kyA = $this->taoDonVaSnapshotMembership($memberAId, $goi['package'], $now);
        $this->xacNhanVaCapMembership($kyA, $now);
        $usage = $this->taoUsageMembership($kyA->fresh(), $memberA['user']->getKey(), 'VAO_PHONG_TAP', $now);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        $kyB = $this->taoDonVaSnapshotMembership($memberBId, $goi['package'], $now);
        $this->xacNhanVaCapMembership($kyB, $now);

        $kyDoiSoat = $this->taoDonVaSnapshotMembership($memberBId, $goi['package'], $now);
        LanThanhToan::query()->create([
            'don_mua_goi_id' => $kyDoiSoat->don_mua_goi_id,
            'so_lan' => 1,
            'ma_kenh_thanh_toan' => 'DASHBOARD_TEST',
            'ma_don_cong_thanh_toan' => random_int(100000000, 2000000000),
            'ma_lien_ket_thanh_toan' => null,
            'duong_dan_thanh_toan' => null,
            'so_tien_yeu_cau' => $kyDoiSoat->gia_da_mua,
            'don_vi_tien' => 'VND',
            'trang_thai' => 'CAN_DOI_SOAT',
            'ma_tham_chieu_duoc_chap_nhan' => null,
            'so_tien_da_nhan' => null,
            'thanh_toan_luc' => null,
            'xac_nhan_luc' => null,
            'het_han_luc' => $now->addHour(),
            'ma_loi' => 'DASHBOARD_TEST_RECONCILIATION',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);

        $qr = app(GymQrService::class)->phatHanh($memberA['user']);
        app(GymCheckInService::class)->xacNhan($receptionist['user'], $qr['qr_token']);
        $this->taoPhanCongPt(['admin' => $admin], $memberAId, $ptId);

        $workoutFixture = array_merge($memberA, [
            'member_id' => $memberAId,
            'token' => $fixture['member_token'],
        ]);
        $exercise = $this->taoBaiTapWorkout($workoutFixture);
        $plan = $this->taoPlanWorkout($workoutFixture, $exercise, CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString());
        $schedule = $this->taoLichWorkout($workoutFixture, $plan);
        $session = app(WorkoutSessionService::class)->batDau($memberA['user'], $schedule->getKey(), (string) Str::uuid());
        app(WorkoutSessionService::class)->hoanThanh($memberA['user'], $session['id'], (string) Str::uuid());

        return $fixture;
    }

    private function snapshotNguonDashboard(): array
    {
        $tables = [
            'nguoi_dung', 'phan_quyen_nguoi_dung', 'ho_so_hoi_vien', 'ho_so_huan_luyen_vien',
            'ky_han_hoi_vien', 'dang_ky_goi_tap', 'lich_su_vao_phong_tap', 'phien_tap',
            'lan_thanh_toan', 'don_mua_goi', 'phan_cong_huan_luyen_vien',
        ];

        return collect($tables)->mapWithKeys(fn (string $table): array => [
            $table => hash('sha256', DB::table($table)->orderBy('id')->get()->toJson()),
        ])->all();
    }
}
