<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\TestCase;

class PtAssignmentApiTest extends TestCase
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

    public function test_admin_can_create_assignment_and_member_or_pt_can_read_only_own_scope(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $memberToken = $this->layTokenProfile($fixture['member_a']);
        $ptToken = $this->layTokenProfile($fixture['pt_a']);
        $start = CarbonImmutable::now('UTC')->subMinute()->toISOString();

        $response = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => $start,
        ], $this->bearer($adminToken))->assertCreated();
        $assignmentId = $response->json('data.id');

        $this->getJson('/api/pt/assignment', $this->bearer($memberToken))
            ->assertOk()
            ->assertJsonPath('data.current.id', $assignmentId);
        $this->getJson('/api/pt/members', $this->bearer($ptToken))
            ->assertOk()
            ->assertJsonPath('data.0.member.id', $fixture['member_a_id']);
        $this->assertNotSame($fixture['member_a']['user']->getKey(), $fixture['member_a_id']);
    }

    public function test_member_and_pt_self_reads_cannot_cross_ownership_scope(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
        ], $this->bearer($adminToken))->assertCreated();

        $this->getJson('/api/pt/assignment', $this->bearer($this->layTokenProfile($fixture['member_b'])))
            ->assertOk()
            ->assertJsonPath('data.current', null)
            ->assertJsonCount(0, 'data.history');
        $this->getJson('/api/pt/members', $this->bearer($this->layTokenProfile($fixture['pt_b'])))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_assignment_rejects_overlap_allows_adjacent_and_reassignment_preserves_history(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $base = CarbonImmutable::parse('2026-08-29 10:00:00.000000', 'UTC');
        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'], 'trainer_id' => $fixture['pt_a_id'],
            'start_at' => $base->toISOString(), 'end_at' => $base->addHours(2)->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();

        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'], 'trainer_id' => $fixture['pt_b_id'],
            'start_at' => $base->addHour()->toISOString(), 'end_at' => $base->addHours(3)->toISOString(),
        ], $this->bearer($adminToken))->assertStatus(409)->assertJsonPath('code', 'ASSIGNMENT_OVERLAP');

        $adjacent = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'], 'trainer_id' => $fixture['pt_b_id'],
            'start_at' => $base->addHours(2)->toISOString(), 'end_at' => $base->addHours(3)->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();
        $this->assertSame(2, DB::table('phan_cong_huan_luyen_vien')->where('hoi_vien_id', $fixture['member_a_id'])->count());
        $this->assertNotNull($adjacent->json('data.end_at'));
    }

    public function test_open_assignment_rejects_another_overlapping_open_interval(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $start = CarbonImmutable::now('UTC')->subHour();

        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => $start->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();

        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_b_id'],
            'start_at' => CarbonImmutable::now('UTC')->subMinute()->toISOString(),
        ], $this->bearer($adminToken))
            ->assertStatus(409)
            ->assertJsonPath('code', 'ASSIGNMENT_OVERLAP');
    }

    public function test_admin_can_end_assignment_with_server_time_and_preserve_history(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $assignment = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->subMinute()->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();

        $ended = $this->patchJson('/api/pt/assignments/'.$assignment->json('data.id').'/end', [
            'reason' => 'Kết thúc hợp đồng',
        ], $this->bearer($adminToken))->assertOk();

        $this->assertNotNull($ended->json('data.end_at'));
        $this->assertDatabaseHas('phan_cong_huan_luyen_vien', [
            'id' => $assignment->json('data.id'),
            'ly_do_ket_thuc' => 'Kết thúc hợp đồng',
        ]);
        $this->getJson('/api/pt/assignment', $this->bearer($this->layTokenProfile($fixture['member_a'])))
            ->assertOk()
            ->assertJsonPath('data.current', null)
            ->assertJsonPath('data.history.0.id', $assignment->json('data.id'));
    }

    public function test_only_admin_manages_and_assignment_does_not_activate_membership(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $memberToken = $this->layTokenProfile($fixture['member_a']);
        $ptToken = $this->layTokenProfile($fixture['pt_a']);
        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'], 'trainer_id' => $fixture['pt_a_id'],
        ], $this->bearer($memberToken))->assertForbidden();
        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'], 'trainer_id' => $fixture['pt_a_id'],
        ], $this->bearer($ptToken))->assertForbidden();

        $this->taoMembershipPt($fixture, $fixture['member_a_id']);
        $this->assertDatabaseHas('dang_ky_goi_tap', [
            'hoi_vien_id' => $fixture['member_a_id'], 'trang_thai' => 'CHO_KICH_HOAT',
            'lan_su_dung_dau_tien_id' => null,
        ]);
    }

    public function test_reassign_closes_old_at_new_start_and_keeps_both_rows(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $start = CarbonImmutable::parse('2026-08-29 09:00:00.000000', 'UTC');
        $assignment = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'], 'trainer_id' => $fixture['pt_a_id'],
            'start_at' => $start->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();
        $boundary = $start->addHours(3);

        $new = $this->postJson('/api/pt/assignments/'.$assignment->json('data.id').'/reassign', [
            'trainer_id' => $fixture['pt_b_id'], 'start_at' => $boundary->toISOString(), 'reason' => 'Đổi PT',
        ], $this->bearer($adminToken))->assertCreated();

        $this->assertDatabaseHas('phan_cong_huan_luyen_vien', [
            'id' => $assignment->json('data.id'),
            'huan_luyen_vien_id' => $fixture['pt_a_id'],
            'ngay_ket_thuc' => $boundary->format('Y-m-d H:i:s.u'),
        ]);
        $this->assertDatabaseHas('phan_cong_huan_luyen_vien', [
            'id' => $new->json('data.id'), 'huan_luyen_vien_id' => $fixture['pt_b_id'],
        ]);
    }
}
