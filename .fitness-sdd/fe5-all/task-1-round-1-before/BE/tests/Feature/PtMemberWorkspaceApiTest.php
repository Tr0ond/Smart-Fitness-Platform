<?php

namespace Tests\Feature;

use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class PtMemberWorkspaceApiTest extends TestCase
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

    public function test_workspace_requires_authentication_and_pt_role_on_every_route(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $paths = $this->workspacePaths($fixture['member_a_id']);

        foreach ($paths as $path) {
            $this->getJson($path)->assertUnauthorized();
        }

        $memberToken = $this->layTokenProfile($fixture['member_a']);
        foreach ($paths as $path) {
            $this->withHeaders($this->bearer($memberToken))
                ->getJson($path)
                ->assertForbidden();
        }
    }

    public function test_current_pt_reads_safe_member_detail_allow_list(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);

        $response = $this->withHeaders($this->bearer($fixture['pt_a_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'])
            ->assertOk();

        $this->assertSame([
            'id',
            'code',
            'name',
            'training_goal',
            'training_experience',
            'desired_training_days',
            'session_duration_minutes',
            'profile_version',
            'updated_at',
        ], array_keys($response->json('data.member')));
        $this->assertSame(['id', 'start_at', 'end_at'], array_keys($response->json('data.assignment')));
        $this->assertSame($fixture['member_a_id'], $response->json('data.member.id'));
        $this->assertSame($assignmentId, $response->json('data.assignment.id'));
        $this->assertArrayNotHasKey('email', $response->json('data.member'));
        $this->assertArrayNotHasKey('phone', $response->json('data.member'));
        $this->assertArrayNotHasKey('birth_date', $response->json('data.member'));
        $this->assertArrayNotHasKey('password_hash', $response->json('data.member'));
    }

    public function test_every_workspace_route_conceals_foreign_unassigned_future_and_ended_scope(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $now = CarbonImmutable::now('UTC');
        $this->taoPhanCongPt(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_a_id'],
            $now->subHour(),
            $now,
        );
        $this->taoPhanCongPt(
            $fixture,
            $fixture['member_b_id'],
            $fixture['pt_a_id'],
            $now->addHour(),
        );

        foreach ($this->workspacePaths($fixture['member_a_id']) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))->getJson($path)->assertNotFound();
        }
        foreach ($this->workspacePaths($fixture['member_b_id']) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))->getJson($path)->assertNotFound();
        }
        foreach ($this->workspacePaths($fixture['member_a_id']) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_b_token']))->getJson($path)->assertNotFound();
        }
    }

    public function test_assignment_start_is_inclusive_and_end_is_exclusive(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $now = CarbonImmutable::now('UTC');
        $assignmentId = $this->taoPhanCongPt(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_a_id'],
            $now,
            $now->addHour(),
        );

        $this->withHeaders($this->bearer($fixture['pt_a_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'])
            ->assertOk()
            ->assertJsonPath('data.assignment.id', $assignmentId);

        CarbonImmutable::setTestNow($now->addHour());

        $this->withHeaders($this->bearer($fixture['pt_a_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'])
            ->assertNotFound();
    }

    public function test_plan_route_returns_official_plan_bounded_future_schedule_and_no_membership_side_effect(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $member = $this->memberWorkoutFixture($fixture);
        $exercise = $this->taoBaiTapWorkout($member);
        $plan = $this->taoPlanWorkout($member, $exercise);
        $schedule = $this->taoLichWorkout($member, $plan);
        $before = [
            'registrations' => DB::table('dang_ky_goi_tap')->count(),
            'terms' => DB::table('ky_han_hoi_vien')->count(),
            'usage' => DB::table('su_dung_quyen_loi')->count(),
        ];

        $response = $this->withHeaders($this->bearer($fixture['pt_a_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/plans/current')
            ->assertOk()
            ->assertJsonPath('data.plan.id', $plan['plan']->getKey())
            ->assertJsonPath('data.future_schedule.0.id', $schedule->getKey())
            ->assertJsonPath('data.schedule_window.timezone', 'Asia/Ho_Chi_Minh');

        $from = CarbonImmutable::parse($response->json('data.schedule_window.from'), 'Asia/Ho_Chi_Minh');
        $to = CarbonImmutable::parse($response->json('data.schedule_window.to'), 'Asia/Ho_Chi_Minh');
        $this->assertSame(91, (int) $from->diffInDays($to));
        $this->assertSame($before, [
            'registrations' => DB::table('dang_ky_goi_tap')->count(),
            'terms' => DB::table('ky_han_hoi_vien')->count(),
            'usage' => DB::table('su_dung_quyen_loi')->count(),
        ]);
    }

    public function test_session_list_and_detail_are_member_scoped_bounded_and_immutable_read_only(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $this->taoPhanCongPt($fixture, $fixture['member_b_id'], $fixture['pt_b_id']);
        $sessionA = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture, 'member_a'));
        $sessionB = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture, 'member_b'));
        $headers = $this->bearer($fixture['pt_a_token']);

        $list = $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions?limit=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sessionA);

        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions?before_id='.$sessionA)
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions/'.$sessionA)
            ->assertOk()
            ->assertJsonPath('data.id', $sessionA)
            ->assertJsonPath('data.status', 'HOAN_THANH');
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions/'.$sessionB)
            ->assertNotFound();

        $this->withHeaders($headers)
            ->postJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions/'.$sessionA.'/complete', [])
            ->assertNotFound();
        $this->assertSame($sessionA, $list->json('data.0.id'));
    }

    public function test_session_query_validation_rejects_invalid_limit_cursor_and_member_override(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $path = '/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions';
        $headers = $this->bearer($fixture['pt_a_token']);

        $this->withHeaders($headers)->getJson($path.'?limit=0')->assertUnprocessable();
        $this->withHeaders($headers)->getJson($path.'?limit=101')->assertUnprocessable();
        $this->withHeaders($headers)->getJson($path.'?before_id=0')->assertUnprocessable();
        $this->withHeaders($headers)->getJson($path.'?member_id='.$fixture['member_b_id'])->assertUnprocessable();
    }

    public function test_existing_pt_profile_assignment_progress_and_notes_routes_regressions_remain_available(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $headers = $this->bearer($fixture['pt_a_token']);

        $this->withHeaders($headers)->getJson('/api/profile/trainer')->assertOk();
        $this->withHeaders($headers)->getJson('/api/pt/members')->assertOk();
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/overview')
            ->assertOk();
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/notes')
            ->assertOk();
    }

    /** @return array<string, mixed> */
    private function taoWorkspaceFixtures(): array
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $fixture['pt_a_token'] = $this->layTokenProfile($fixture['pt_a']);
        $fixture['pt_b_token'] = $this->layTokenProfile($fixture['pt_b']);
        $fixture['member_a_token'] = $this->layTokenProfile($fixture['member_a']);

        return $fixture;
    }

    /** @param array<string, mixed> $fixture */
    private function dongBoChiNhanh(array $fixture): void
    {
        DB::table('nguoi_dung')
            ->whereIn('id', [
                $fixture['member_a']['user']->getKey(),
                $fixture['member_b']['user']->getKey(),
                $fixture['pt_a']['user']->getKey(),
                $fixture['pt_b']['user']->getKey(),
            ])
            ->update(['chi_nhanh_id' => $fixture['admin']['branch_id']]);
    }

    /** @return array<string, mixed> */
    private function memberWorkoutFixture(array $fixture, string $member = 'member_a'): array
    {
        return array_merge($fixture[$member], ['member_id' => $fixture[$member.'_id']]);
    }

    /** @param array<string, mixed> $member */
    private function taoSessionHoanThanh(array $member): int
    {
        $exercise = $this->taoBaiTapWorkout($member);
        $plan = $this->taoPlanWorkout($member, $exercise);
        $schedule = $this->taoLichWorkout($member, $plan);
        $service = app(WorkoutSessionService::class);
        $session = $service->batDau($member['user'], $schedule->getKey(), (string) Str::uuid());
        $service->hoanThanh($member['user'], $session['id'], (string) Str::uuid());

        return (int) $session['id'];
    }

    /** @return array<int, string> */
    private function workspacePaths(int $memberId): array
    {
        return [
            '/api/pt/members/'.$memberId,
            '/api/pt/members/'.$memberId.'/workout/plans/current',
            '/api/pt/members/'.$memberId.'/workout/sessions',
            '/api/pt/members/'.$memberId.'/workout/sessions/999999999',
        ];
    }
}
