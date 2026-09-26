<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\TestCase;

class PtDirectServiceTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;
    use CreatesPtFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_first_valid_direct_service_activates_membership_and_deducts_once(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id'], ['so_buoi_huan_luyen_vien' => 2]);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $token = $this->layTokenProfile($fixture['pt_a']);
        $key = (string) Str::uuid();

        $response = $this->postJson('/api/pt/direct-sessions/complete', [
            'assignment_id' => $assignmentId,
            'notes' => 'Buổi đầu hoàn thành',
        ], array_merge($this->bearer($token), ['Idempotency-Key' => $key]))
            ->assertCreated();
        $historyId = $response->json('data.history_id');
        $ky = DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $fixture['member_a_id'])->first();
        $chuoi = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $fixture['member_a_id'])->first();

        $this->assertSame(1, DB::table('lich_su_su_dung_huan_luyen_vien')->where('id', $historyId)->count());
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('id', $response->json('data.usage_id'))->count());
        $this->assertSame(1, (int) $ky->so_buoi_huan_luyen_vien_da_dung);
        $this->assertSame('DANG_HOAT_DONG', $chuoi->trang_thai);
        $this->assertSame($response->json('data.usage_id'), (int) $chuoi->lan_su_dung_dau_tien_id);
    }

    public function test_same_idempotency_key_replays_without_second_usage_or_counter_increment(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $headers = array_merge($this->bearer($this->layTokenProfile($fixture['pt_a'])), ['Idempotency-Key' => (string) Str::uuid()]);
        $payload = ['assignment_id' => $assignmentId];

        $first = $this->postJson('/api/pt/direct-sessions/complete', $payload, $headers)->assertCreated();
        $second = $this->postJson('/api/pt/direct-sessions/complete', $payload, $headers)->assertCreated();

        $this->assertSame($first->json('data.history_id'), $second->json('data.history_id'));
        $this->assertTrue($second->json('data.replayed'));
        $this->assertSame(1, DB::table('lich_su_su_dung_huan_luyen_vien')->count());
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('loai_su_dung', 'BUOI_HUAN_LUYEN')->count());
        $this->assertSame(1, (int) DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $fixture['member_a_id'])->value('so_buoi_huan_luyen_vien_da_dung'));
    }

    public function test_only_assigned_pt_can_confirm_and_member_cannot_confirm(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $payload = ['assignment_id' => $assignmentId];

        $this->postJson('/api/pt/direct-sessions/complete', $payload, array_merge(
            $this->bearer($this->layTokenProfile($fixture['pt_b'])),
            ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertNotFound();
        $this->postJson('/api/pt/direct-sessions/complete', $payload, array_merge(
            $this->bearer($this->layTokenProfile($fixture['member_a'])),
            ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertForbidden();
        $this->assertSame(0, DB::table('lich_su_su_dung_huan_luyen_vien')->count());
    }

    public function test_chat_right_does_not_grant_direct_quota_and_direct_quota_does_not_require_chat(): void
    {
        $chatOnly = $this->taoBoPtFixtures();
        $this->taoMembershipPt($chatOnly, $chatOnly['member_a_id'], [
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $assignmentChat = $this->taoPhanCongPt($chatOnly, $chatOnly['member_a_id'], $chatOnly['pt_a_id']);
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentChat], array_merge(
            $this->bearer($this->layTokenProfile($chatOnly['pt_a'])), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertStatus(409)->assertJsonPath('code', 'PT_QUOTA_EXHAUSTED');

        $directOnly = $this->taoBoPtFixtures();
        $this->taoMembershipPt($directOnly, $directOnly['member_a_id'], [
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 1,
        ]);
        $assignmentDirect = $this->taoPhanCongPt($directOnly, $directOnly['member_a_id'], $directOnly['pt_a_id']);
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentDirect], array_merge(
            $this->bearer($this->layTokenProfile($directOnly['pt_a'])), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertCreated();
    }

    public function test_revoked_or_disabled_actor_and_expired_assignment_are_blocked(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $token = $this->layTokenProfile($fixture['pt_a']);
        $this->thuHoiVaiTroProfile($fixture['pt_a'], 'PT');
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], array_merge(
            $this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertForbidden();
    }

    public function test_disabled_pt_and_disabled_member_are_blocked(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $headers = $this->bearer($this->layTokenProfile($fixture['pt_a'])) + ['Idempotency-Key' => (string) Str::uuid()];

        $fixture['pt_a']['user']->forceFill(['trang_thai' => 'BI_KHOA'])->save();
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], $headers)
            ->assertUnauthorized();

        $fixture['pt_a']['user']->forceFill(['trang_thai' => 'HOAT_DONG'])->save();
        $fixture['member_a']['user']->forceFill(['trang_thai' => 'BI_KHOA'])->save();
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], [
            ...$this->bearer($this->layTokenProfile($fixture['pt_a'])),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)->assertJsonPath('code', 'MEMBER_NOT_ACTIVE');
    }

    public function test_idempotency_conflict_and_forbidden_client_timestamp_are_rejected(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id'], ['so_buoi_huan_luyen_vien' => 2]);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $headers = array_merge($this->bearer($this->layTokenProfile($fixture['pt_a'])), ['Idempotency-Key' => (string) Str::uuid()]);
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId, 'notes' => 'A'], $headers)->assertCreated();
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId, 'notes' => 'B'], $headers)
            ->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
        $this->postJson('/api/pt/direct-sessions/complete', [
            'assignment_id' => $assignmentId, 'completed_at' => '2020-01-01T00:00:00Z',
        ], array_merge($this->bearer($this->layTokenProfile($fixture['pt_a'])), ['Idempotency-Key' => (string) Str::uuid()]))
            ->assertUnprocessable();
    }

    public function test_member_and_trainer_can_read_only_their_direct_history(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], array_merge(
            $this->bearer($this->layTokenProfile($fixture['pt_a'])), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertCreated();

        $this->getJson('/api/pt/direct-sessions', $this->bearer($this->layTokenProfile($fixture['member_a'])))
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/pt/direct-sessions', $this->bearer($this->layTokenProfile($fixture['pt_a'])))
            ->assertOk()->assertJsonPath('data.0.assignment_id', $assignmentId);
        $this->getJson('/api/pt/direct-sessions', $this->bearer($this->layTokenProfile($fixture['member_b'])))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_missing_membership_expired_assignment_and_future_assignment_are_denied(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $token = $this->layTokenProfile($fixture['pt_a']);

        $missingMembershipAssignment = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $missingMembershipAssignment], array_merge(
            $this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertStatus(409)->assertJsonPath('code', 'PT_ENTITLEMENT_DENIED');

        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $expired = $this->taoPhanCongPt(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_a_id'],
            CarbonImmutable::now('UTC')->subHours(3),
            CarbonImmutable::now('UTC')->subHours(2),
        );
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $expired], array_merge(
            $this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertStatus(409)->assertJsonPath('code', 'ASSIGNMENT_NOT_ACTIVE');

        $future = $this->taoPhanCongPt(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_a_id'],
            CarbonImmutable::now('UTC')->addHours(2),
            CarbonImmutable::now('UTC')->addHours(3),
        );
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $future], array_merge(
            $this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertStatus(409)->assertJsonPath('code', 'ASSIGNMENT_NOT_ACTIVE');
    }

    public function test_active_membership_completion_does_not_reset_activation_clock(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->taoMembershipPt($fixture, $fixture['member_a_id'], ['so_buoi_huan_luyen_vien' => 2]);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $headers = $this->bearer($this->layTokenProfile($fixture['pt_a'])) + ['Idempotency-Key' => (string) Str::uuid()];
        $first = $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], $headers)->assertCreated();
        $startedAt = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $fixture['member_a_id'])->value('ngay_bat_dau');
        $sourceId = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $fixture['member_a_id'])->value('lan_su_dung_dau_tien_id');

        $second = $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], $this->bearer($this->layTokenProfile($fixture['pt_a'])) + ['Idempotency-Key' => (string) Str::uuid()])->assertCreated();
        $this->assertNotSame($first->json('data.history_id'), $second->json('data.history_id'));
        $this->assertSame($startedAt, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $fixture['member_a_id'])->value('ngay_bat_dau'));
        $this->assertSame($sourceId, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $fixture['member_a_id'])->value('lan_su_dung_dau_tien_id'));
        $this->assertSame(2, (int) DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $fixture['member_a_id'])->value('so_buoi_huan_luyen_vien_da_dung'));
    }

    public function test_future_term_quota_is_not_borrowed_when_current_term_is_exhausted(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $first = $this->taoMembershipPt($fixture, $fixture['member_a_id'], ['so_buoi_huan_luyen_vien' => 1]);
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $token = $this->layTokenProfile($fixture['pt_a']);
        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], array_merge(
            $this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertCreated();
        $future = $this->taoMembershipPt($fixture, $fixture['member_a_id'], ['so_buoi_huan_luyen_vien' => 10]);

        $this->postJson('/api/pt/direct-sessions/complete', ['assignment_id' => $assignmentId], array_merge(
            $this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()],
        ))->assertStatus(409)->assertJsonPath('code', 'PT_QUOTA_EXHAUSTED');
        $this->assertSame(0, (int) $future->fresh()->so_buoi_huan_luyen_vien_da_dung);
        $this->assertSame(1, (int) $first->fresh()->so_buoi_huan_luyen_vien_da_dung);
    }
}
