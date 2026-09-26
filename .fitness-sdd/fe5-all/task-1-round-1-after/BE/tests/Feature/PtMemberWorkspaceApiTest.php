<?php

namespace Tests\Feature;

use App\Services\MembershipLifecycleService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
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
        $sessionId = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture));
        $paths = $this->workspacePaths($fixture['member_a_id'], $sessionId);

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
        $sessionA = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture));
        $sessionB = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture, 'member_b'));
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

        foreach ($this->workspacePaths($fixture['member_a_id'], $sessionA) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))->getJson($path)->assertNotFound();
        }
        foreach ($this->workspacePaths($fixture['member_b_id'], $sessionB) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))->getJson($path)->assertNotFound();
        }
        foreach ($this->workspacePaths($fixture['member_a_id'], $sessionA) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_b_token']))->getJson($path)->assertNotFound();
        }
    }

    public function test_old_pt_after_reassignment_loses_every_workspace_route_family(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $sessionId = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture));
        $now = CarbonImmutable::now('UTC');

        $this->taoPhanCongPt(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_a_id'],
            $now->subHour(),
            $now,
        );
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_b_id'], $now);

        foreach ($this->workspacePaths($fixture['member_a_id'], $sessionId) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))
                ->getJson($path)
                ->assertNotFound()
                ->assertJsonPath('code', 'ASSIGNMENT_NOT_FOUND');
        }

        $this->withHeaders($this->bearer($fixture['pt_b_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'])
            ->assertOk();
        $this->withHeaders($this->bearer($fixture['pt_b_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions/'.$sessionId)
            ->assertOk()
            ->assertJsonPath('data.id', $sessionId);
    }

    public function test_never_assigned_member_is_concealed_for_every_workspace_route_family(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $sessionId = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture, 'member_b'));

        $this->assertSame(0, DB::table('phan_cong_huan_luyen_vien')
            ->where('hoi_vien_id', $fixture['member_b_id'])
            ->count());

        foreach ($this->workspacePaths($fixture['member_b_id'], $sessionId) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))
                ->getJson($path)
                ->assertNotFound()
                ->assertJsonPath('code', 'ASSIGNMENT_NOT_FOUND');
        }
    }

    public function test_authenticated_pt_without_available_profile_is_forbidden_on_every_workspace_route(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $sessionId = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture));

        DB::table('ho_so_huan_luyen_vien')
            ->where('id', $fixture['pt_a_id'])
            ->update(['trang_thai' => 'NGUNG_NHAN_PHAN_CONG']);

        foreach ($this->workspacePaths($fixture['member_a_id'], $sessionId) as $path) {
            $this->withHeaders($this->bearer($fixture['pt_a_token']))
                ->getJson($path)
                ->assertForbidden()
                ->assertJsonPath('code', 'TRAINER_NOT_AVAILABLE');
        }

        $missingProfilePt = $this->taoNguoiDungAuth(['PT']);
        $missingProfileToken = $this->layTokenProfile($missingProfilePt);
        foreach ($this->workspacePaths($fixture['member_a_id'], $sessionId) as $path) {
            $this->withHeaders($this->bearer($missingProfileToken))
                ->getJson($path)
                ->assertForbidden()
                ->assertJsonPath('code', 'TRAINER_NOT_AVAILABLE');
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

    public function test_pending_pt_proposal_is_excluded_from_official_plan_response(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $member = $this->memberWorkoutFixture($fixture);
        $exerciseId = $this->taoBaiTapWorkout($member);
        $officialPlan = $this->taoPlanWorkout($member, $exerciseId);
        $schedule = $this->taoLichWorkout($member, $officialPlan);

        $proposal = $this->postJson(
            '/api/pt/members/'.$fixture['member_a_id'].'/proposals',
            $this->proposalPayload($exerciseId, 'Pending PT proposal', 'Pending replacement plan'),
            [
                ...$this->bearer($fixture['pt_a_token']),
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        )->assertCreated()
            ->assertJsonPath('data.status', 'CHO_XAC_NHAN');
        $proposalId = (int) $proposal->json('data.id');
        $proposalBefore = DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->get()->toJson();

        $response = $this->withHeaders($this->bearer($fixture['pt_a_token']))
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/plans/current')
            ->assertOk()
            ->assertJsonPath('data.plan.id', $officialPlan['plan']->getKey())
            ->assertJsonPath('data.plan.name', $officialPlan['plan']->ten_ke_hoach)
            ->assertJsonPath('data.plan.current_version.id', $officialPlan['version']->getKey())
            ->assertJsonPath('data.future_schedule.0.id', $schedule->getKey());

        $encodedPlan = json_encode($response->json('data.plan'), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Pending replacement plan', $encodedPlan);
        $this->assertArrayNotHasKey('proposal_id', $response->json('data.plan'));
        $this->assertSame($proposalBefore, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->get()->toJson());
        $this->assertSame('CHO_XAC_NHAN', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
        $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')
            ->where('de_xuat_ke_hoach_tap_id', $proposalId)
            ->count());
    }

    public function test_workspace_gets_leave_pending_and_expired_membership_snapshots_unchanged(): void
    {
        foreach (['CHO_KICH_HOAT', 'HET_HAN'] as $membershipState) {
            $fixture = $this->taoWorkspaceFixtures();
            $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
            $now = CarbonImmutable::now('UTC');

            if ($membershipState === 'CHO_KICH_HOAT') {
                $term = $this->taoMembershipPt($fixture, $fixture['member_a_id']);
            } else {
                $past = $now->subDays(40);
                CarbonImmutable::setTestNow($past);
                $term = $this->taoMembershipPt($fixture, $fixture['member_a_id'], [], true);
                CarbonImmutable::setTestNow($now);
                app(MembershipLifecycleService::class)->doiChieuHoiVien($fixture['member_a_id'], $now);
            }

            $this->assertSame($membershipState, $term->fresh()->trang_thai);
            $member = $this->memberWorkoutFixture($fixture);
            $sessionId = $this->taoSessionHoanThanh($member);
            $before = $this->membershipSnapshot($fixture['member_a_id']);

            foreach ($this->workspacePaths($fixture['member_a_id'], $sessionId) as $path) {
                $this->withHeaders($this->bearer($fixture['pt_a_token']))
                    ->getJson($path)
                    ->assertOk();
                $this->assertSame($before, $this->membershipSnapshot($fixture['member_a_id']));
            }

            $this->assertSame($before, $this->membershipSnapshot($fixture['member_a_id']));
            $this->assertSame($membershipState, $term->fresh()->trang_thai);
            $this->assertSame($membershipState, DB::table('dang_ky_goi_tap')
                ->where('id', $term->dang_ky_goi_tap_id)
                ->value('trang_thai'));
        }
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

    public function test_same_pt_assigned_to_two_members_cannot_cross_member_bind_session_detail(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $this->taoPhanCongPt($fixture, $fixture['member_b_id'], $fixture['pt_a_id']);
        $sessionA = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture));
        $sessionB = $this->taoSessionHoanThanh($this->memberWorkoutFixture($fixture, 'member_b'));
        $headers = $this->bearer($fixture['pt_a_token']);

        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_b_id'].'/workout/sessions/'.$sessionB)
            ->assertOk()
            ->assertJsonPath('data.id', $sessionB);
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions/'.$sessionB)
            ->assertNotFound()
            ->assertJsonPath('code', 'WORKOUT_SESSION_NOT_FOUND');
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/workout/sessions/'.$sessionA)
            ->assertOk()
            ->assertJsonPath('data.id', $sessionA);
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

    public function test_profile_list_progress_and_append_only_notes_preserve_plan_and_session_snapshots(): void
    {
        $fixture = $this->taoWorkspaceFixtures();
        $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $member = $this->memberWorkoutFixture($fixture);
        $exerciseId = $this->taoBaiTapWorkout($member);
        $plan = $this->taoPlanWorkout($member, $exerciseId);
        $schedule = $this->taoLichWorkout($member, $plan);
        $sessionService = app(WorkoutSessionService::class);
        $session = $sessionService->batDau($member['user'], $schedule->getKey(), (string) Str::uuid());
        $sessionExerciseId = (int) ($session['exercises'][0]['id'] ?? 0);
        $sessionService->ghiHiep(
            $member['user'],
            (int) $session['id'],
            $sessionExerciseId,
            ['order' => 1, 'reps' => 10, 'weight_kg' => 25, 'actual_rest_seconds' => 60],
            (string) Str::uuid(),
        );
        $sessionService->hoanThanh($member['user'], (int) $session['id'], (string) Str::uuid());
        $sessionId = (int) $session['id'];
        $before = $this->workoutSnapshot(
            (int) $plan['plan']->getKey(),
            (int) $plan['version']->getKey(),
            (int) $schedule->getKey(),
            $sessionId,
        );
        $headers = $this->bearer($fixture['pt_a_token']);

        $this->withHeaders($headers)->getJson('/api/profile/trainer')->assertOk();
        $this->withHeaders($headers)->getJson('/api/pt/members')->assertOk();
        $this->withHeaders($headers)->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/overview')->assertOk();
        $this->withHeaders($headers)->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/body')->assertOk();
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/progress/exercises/'.$exerciseId)
            ->assertOk();
        $this->withHeaders($headers)->getJson('/api/pt/members/'.$fixture['member_a_id'].'/notes')->assertOk();

        $noteOne = $this->postJson('/api/pt/members/'.$fixture['member_a_id'].'/notes', [
            'content' => 'Ghi chu mot chi la nhan xet.',
            'plan_id' => $plan['plan']->getKey(),
            'session_id' => $sessionId,
        ], $headers)->assertCreated();
        $noteOneId = (int) $noteOne->json('data.id');
        $noteOneBefore = (array) DB::table('ghi_chu_huan_luyen')->where('id', $noteOneId)->first();
        $this->assertSame($before, $this->workoutSnapshot(
            (int) $plan['plan']->getKey(),
            (int) $plan['version']->getKey(),
            (int) $schedule->getKey(),
            $sessionId,
        ));

        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/notes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $noteOneId);

        $noteTwo = $this->postJson('/api/pt/members/'.$fixture['member_a_id'].'/notes', [
            'content' => 'Ghi chu hai append-only.',
            'plan_id' => $plan['plan']->getKey(),
            'session_id' => $sessionId,
        ], $headers)->assertCreated();
        $noteTwoId = (int) $noteTwo->json('data.id');

        $this->assertNotSame($noteOneId, $noteTwoId);
        $this->assertSame($noteOneBefore, (array) DB::table('ghi_chu_huan_luyen')->where('id', $noteOneId)->first());
        $this->assertSame(2, DB::table('ghi_chu_huan_luyen')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->count());
        $this->assertSame($before, $this->workoutSnapshot(
            (int) $plan['plan']->getKey(),
            (int) $plan['version']->getKey(),
            (int) $schedule->getKey(),
            $sessionId,
        ));
        $this->withHeaders($headers)
            ->getJson('/api/pt/members/'.$fixture['member_a_id'].'/notes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $noteTwoId)
            ->assertJsonPath('data.1.id', $noteOneId);
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

    /** @return array<string, mixed> */
    private function proposalPayload(int $exerciseId, string $title, string $planName): array
    {
        $effectiveFrom = CarbonImmutable::now('Asia/Ho_Chi_Minh')->addDay();
        $weekday = $effectiveFrom->dayOfWeekIso === 7 ? 8 : $effectiveFrom->dayOfWeekIso + 1;

        return [
            'change_type' => 'DIEU_CHINH',
            'title' => $title,
            'explanation' => 'Proposal kiem thu phai cho Member xac nhan.',
            'effective_from' => $effectiveFrom->toDateString(),
            'plan' => [
                'name' => $planName,
                'goal' => 'TANG_SUC_MANH',
                'days' => [[
                    'order' => 1,
                    'weekday' => $weekday,
                    'name' => 'Ngay proposal',
                    'estimated_minutes' => 60,
                    'exercises' => [[
                        'exercise_id' => $exerciseId,
                        'order' => 1,
                        'target_sets' => 4,
                        'min_reps' => 6,
                        'max_reps' => 10,
                        'target_weight_kg' => 25,
                        'rest_seconds' => 90,
                        'notes' => 'Chi ap dung sau xac nhan',
                    ]],
                ]],
            ],
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function membershipSnapshot(int $memberId): array
    {
        return [
            'registrations' => DB::table('dang_ky_goi_tap')
                ->where('hoi_vien_id', $memberId)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
            'terms' => DB::table('ky_han_hoi_vien')
                ->where('hoi_vien_id', $memberId)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
            'usage' => DB::table('su_dung_quyen_loi')
                ->where('hoi_vien_id', $memberId)
                ->orderBy('id')
                ->get()
                ->map(static fn ($row): array => (array) $row)
                ->all(),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function workoutSnapshot(int $planId, int $versionId, int $scheduleId, int $sessionId): array
    {
        $days = DB::table('ngay_trong_ke_hoach')
            ->where('phien_ban_ke_hoach_tap_id', $versionId)
            ->orderBy('id')
            ->get();
        $dayIds = $days->pluck('id')->all();
        $planExercises = DB::table('bai_tap_trong_ke_hoach')
            ->whereIn('ngay_trong_ke_hoach_id', $dayIds)
            ->orderBy('id')
            ->get();
        $sessionExercises = DB::table('bai_tap_trong_phien')
            ->where('phien_tap_id', $sessionId)
            ->orderBy('id')
            ->get();
        $sessionExerciseIds = $sessionExercises->pluck('id')->all();

        return [
            'plans' => $this->duLieuDong(DB::table('ke_hoach_tap')->where('id', $planId)->get()),
            'versions' => $this->duLieuDong(DB::table('phien_ban_ke_hoach_tap')->where('id', $versionId)->get()),
            'days' => $this->duLieuDong($days),
            'plan_exercises' => $this->duLieuDong($planExercises),
            'schedules' => $this->duLieuDong(DB::table('buoi_tap_du_kien')->where('id', $scheduleId)->get()),
            'sessions' => $this->duLieuDong(DB::table('phien_tap')->where('id', $sessionId)->get()),
            'session_exercises' => $this->duLieuDong($sessionExercises),
            'sets' => $this->duLieuDong(DB::table('hiep_tap')
                ->whereIn('bai_tap_trong_phien_id', $sessionExerciseIds)
                ->orderBy('id')
                ->get()),
        ];
    }

    /** @param Collection<int, object> $rows */
    private function duLieuDong($rows): array
    {
        return $rows->map(static fn ($row): array => (array) $row)->all();
    }

    /** @return array<int, string> */
    private function workspacePaths(int $memberId, ?int $sessionId = null): array
    {
        $sessionId ??= 999999999;

        return [
            '/api/pt/members/'.$memberId,
            '/api/pt/members/'.$memberId.'/workout/plans/current',
            '/api/pt/members/'.$memberId.'/workout/sessions',
            '/api/pt/members/'.$memberId.'/workout/sessions/'.$sessionId,
        ];
    }
}
