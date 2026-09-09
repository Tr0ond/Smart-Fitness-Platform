<?php

namespace Tests\Feature;

use App\Models\NhatKyHeThong;
use App\Services\Pt\PtAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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

    /** Các fixture mặc định tạo branch riêng; assignment contract yêu cầu cùng branch. */
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

    private function snapshotAssignments(): array
    {
        return DB::table('phan_cong_huan_luyen_vien')
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function snapshotAssignmentAudits(): array
    {
        return DB::table('nhat_ky_he_thong')
            ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
            ->orderBy('id')
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    private function snapshotAssignment(int $assignmentId): ?array
    {
        $row = DB::table('phan_cong_huan_luyen_vien')->where('id', $assignmentId)->first();

        return $row === null ? null : (array) $row;
    }

    private function demAuditAssignment(?int $assignmentId = null): int
    {
        $query = DB::table('nhat_ky_he_thong')
            ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN');
        if ($assignmentId !== null) {
            $query->where('dinh_danh_doi_tuong', $assignmentId);
        }

        return $query->count();
    }

    private function giaMaSnapshotAudit(mixed $duLieu): ?array
    {
        if ($duLieu === null) {
            return null;
        }
        if (is_array($duLieu)) {
            return $duLieu;
        }

        return json_decode((string) $duLieu, true, 512, JSON_THROW_ON_ERROR);
    }

    private function snapshotAuditMongDoi(
        int $id,
        int $memberId,
        int $trainerId,
        int $assignedById,
        CarbonImmutable $start,
        ?CarbonImmutable $end,
        ?string $reason,
        bool $isCurrent,
    ): array {
        return [
            'id' => $id,
            'member_id' => $memberId,
            'trainer_id' => $trainerId,
            'assigned_by_id' => $assignedById,
            'start_at' => $start->toISOString(),
            'end_at' => $end?->toISOString(),
            'reason' => $reason,
            'is_current' => $isCurrent,
        ];
    }

    private function assertAssignmentAudit(
        object $audit,
        string $action,
        int $assignmentId,
        ?array $before,
        array $after,
        int $actorId,
        string $correlation,
    ): void {
        $this->assertSame($actorId, (int) $audit->nguoi_thuc_hien_id);
        $this->assertSame('NGUOI_DUNG', $audit->loai_tac_nhan);
        $this->assertSame($action, $audit->hanh_dong);
        $this->assertSame('PHAN_CONG_HUAN_LUYEN_VIEN', $audit->loai_doi_tuong);
        $this->assertSame($assignmentId, (int) $audit->dinh_danh_doi_tuong);
        $this->assertSame($correlation, $audit->khoa_tuong_quan);
        $this->assertSame('THANH_CONG', $audit->ket_qua);
        $snapshotKeys = ['id', 'member_id', 'trainer_id', 'assigned_by_id', 'start_at', 'end_at', 'reason', 'is_current'];
        $decodedBefore = $this->giaMaSnapshotAudit($audit->du_lieu_truoc);
        $decodedAfter = $this->giaMaSnapshotAudit($audit->du_lieu_sau);
        if ($before === null) {
            $this->assertNull($decodedBefore);
        } else {
            $this->assertSame($snapshotKeys, array_keys($decodedBefore ?? []));
            $this->assertSame($before, $decodedBefore);
        }
        $this->assertSame($snapshotKeys, array_keys($decodedAfter ?? []));
        $this->assertSame($after, $decodedAfter);
    }

    public function test_admin_can_create_assignment_and_member_or_pt_can_read_only_own_scope(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
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
        $this->dongBoChiNhanh($fixture);
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
        $this->dongBoChiNhanh($fixture);
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
        $this->dongBoChiNhanh($fixture);
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
        $this->dongBoChiNhanh($fixture);
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
        $this->dongBoChiNhanh($fixture);
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
        $this->dongBoChiNhanh($fixture);
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

    public function test_admin_assignment_list_detail_filters_current_boundary_and_hides_foreign_branch(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $start = CarbonImmutable::now('UTC')->subHour();
        $assignment = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => $start->toISOString(),
            'end_at' => $start->addMinutes(30)->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();
        $assignmentId = $assignment->json('data.id');

        $list = $this->getJson('/api/pt/assignments?member_id='.$fixture['member_a_id'].'&current=false&per_page=10', $this->bearer($adminToken))
            ->assertOk()
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.items.0.id', $assignmentId)
            ->assertJsonPath('data.items.0.member.id', $fixture['member_a_id'])
            ->assertJsonPath('data.items.0.trainer.id', $fixture['pt_a_id'])
            ->assertJsonPath('data.items.0.trainer.status', 'HOAT_DONG')
            ->assertJsonPath('data.items.0.is_current', false);
        $this->assertArrayHasKey('created_at', $list->json('data.items.0'));
        $this->assertArrayHasKey('updated_at', $list->json('data.items.0'));
        $this->assertArrayNotHasKey('introduction', $list->json('data.items.0.trainer'));
        $this->assertArrayNotHasKey('specialties', $list->json('data.items.0.trainer'));

        $this->getJson('/api/pt/assignments/'.$assignmentId, $this->bearer($adminToken))
            ->assertOk()
            ->assertJsonPath('data.id', $assignmentId)
            ->assertJsonPath('data.is_current', false);

        $foreign = $this->taoBoPtFixtures();
        $foreignAssignmentId = $this->taoPhanCongPt(
            $foreign,
            $foreign['member_a_id'],
            $foreign['pt_a_id'],
            $start,
            $start->addMinutes(30),
        );
        $this->getJson('/api/pt/assignments/'.$foreignAssignmentId, $this->bearer($adminToken))
            ->assertNotFound()
            ->assertJsonPath('code', 'ASSIGNMENT_NOT_FOUND');
        $this->getJson('/api/pt/assignments?member_id='.$foreign['member_a_id'], $this->bearer($adminToken))
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_assignment_mutations_write_allow_list_audit_and_preserve_exact_assignment_chat_rows(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $assignment = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->subMinute()->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();
        $assignmentId = (int) $assignment->json('data.id');
        $now = CarbonImmutable::now('UTC');
        $conversationId = DB::table('hoi_thoai')->insertGetId([
            'hoi_vien_id' => $fixture['member_a_id'],
            'huan_luyen_vien_id' => $fixture['pt_a_id'],
            'phan_cong_huan_luyen_vien_id' => $assignmentId,
            'so_thu_tu_cuoi' => 0,
            'hoi_vien_doc_den_so' => 0,
            'huan_luyen_vien_doc_den_so' => 0,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $messageId = DB::table('tin_nhan')->insertGetId([
            'hoi_thoai_id' => $conversationId,
            'so_thu_tu' => 1,
            'nguoi_gui_id' => $fixture['member_a']['user']->getKey(),
            'phan_cong_huan_luyen_vien_id' => $assignmentId,
            'su_dung_quyen_loi_id' => null,
            'ma_tin_nhan_phia_gui' => (string) Str::uuid(),
            'noi_dung' => 'Q05 preservation test',
            'gui_luc' => $now,
            'hoi_vien_id' => $fixture['member_a_id'],
            'huan_luyen_vien_id' => $fixture['pt_a_id'],
            'ngay_tao' => $now,
        ]);
        $conversationBefore = DB::table('hoi_thoai')->where('id', $conversationId)->first();
        $messageBefore = DB::table('tin_nhan')->where('id', $messageId)->first();

        $this->patchJson('/api/pt/assignments/'.$assignmentId.'/end', [
            'reason' => 'Q05 end',
        ], $this->bearer($adminToken))->assertOk();
        $this->assertEquals($conversationBefore, DB::table('hoi_thoai')->where('id', $conversationId)->first());
        $this->assertEquals($messageBefore, DB::table('tin_nhan')->where('id', $messageId)->first());

        $audits = DB::table('nhat_ky_he_thong')
            ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
            ->where('dinh_danh_doi_tuong', $assignmentId)
            ->get();
        $this->assertCount(2, $audits);
        $this->assertNotEmpty($audits->firstWhere('hanh_dong', 'TAO_PHAN_CONG_HUAN_LUYEN_VIEN'));
        $this->assertNotEmpty($audits->firstWhere('hanh_dong', 'KET_THUC_PHAN_CONG_HUAN_LUYEN_VIEN'));
        foreach ($audits as $audit) {
            $this->assertSame('NGUOI_DUNG', $audit->loai_tac_nhan);
            $this->assertSame('THANH_CONG', $audit->ket_qua);
            $this->assertNotSame('', $audit->khoa_tuong_quan);
        }
    }

    public function test_assignment_routes_require_admin_and_conceal_foreign_resources_without_mutation(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $memberToken = $this->layTokenProfile($fixture['member_a']);
        $ptToken = $this->layTokenProfile($fixture['pt_a']);
        $payload = [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->subMinute()->toISOString(),
        ];

        $this->getJson('/api/pt/assignments')->assertUnauthorized();
        $this->getJson('/api/pt/assignments/999999')->assertUnauthorized();
        $this->postJson('/api/pt/assignments', $payload)->assertUnauthorized();
        $this->patchJson('/api/pt/assignments/999999/end', [])->assertUnauthorized();
        $this->postJson('/api/pt/assignments/999999/reassign', [
            'trainer_id' => $fixture['pt_b_id'],
            'start_at' => CarbonImmutable::now('UTC')->toISOString(),
        ])->assertUnauthorized();

        $assignment = $this->postJson('/api/pt/assignments', $payload, $this->bearer($adminToken))->assertCreated();
        $assignmentId = (int) $assignment->json('data.id');
        $rowsBeforeRoleChecks = $this->snapshotAssignments();
        $auditsBeforeRoleChecks = $this->snapshotAssignmentAudits();
        foreach ([$memberToken, $ptToken] as $token) {
            $this->getJson('/api/pt/assignments', $this->bearer($token))->assertForbidden();
            $this->getJson('/api/pt/assignments/'.$assignmentId, $this->bearer($token))->assertForbidden();
            $this->postJson('/api/pt/assignments', $payload, $this->bearer($token))->assertForbidden();
            $this->patchJson('/api/pt/assignments/'.$assignmentId.'/end', [
                'reason' => 'Không phải Admin',
            ], $this->bearer($token))->assertForbidden();
            $this->postJson('/api/pt/assignments/'.$assignmentId.'/reassign', [
                'trainer_id' => $fixture['pt_b_id'],
                'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
            ], $this->bearer($token))->assertForbidden();
        }
        $this->assertSame($rowsBeforeRoleChecks, $this->snapshotAssignments());
        $this->assertSame($auditsBeforeRoleChecks, $this->snapshotAssignmentAudits());

        $foreign = $this->taoBoPtFixtures();
        $foreignAssignmentId = $this->taoPhanCongPt(
            $foreign,
            $foreign['member_a_id'],
            $foreign['pt_a_id'],
            CarbonImmutable::now('UTC')->subHour(),
        );
        $foreignBefore = $this->snapshotAssignment($foreignAssignmentId);
        $auditsBeforeForeign = $this->snapshotAssignmentAudits();
        $this->postJson('/api/pt/assignments', [
            'member_id' => $foreign['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
        ], $this->bearer($adminToken))
            ->assertNotFound()
            ->assertJsonPath('code', 'MEMBER_NOT_FOUND');
        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_b_id'],
            'trainer_id' => $foreign['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
        ], $this->bearer($adminToken))
            ->assertNotFound()
            ->assertJsonPath('code', 'TRAINER_NOT_FOUND');
        $this->patchJson('/api/pt/assignments/'.$foreignAssignmentId.'/end', [
            'reason' => 'Foreign',
        ], $this->bearer($adminToken))
            ->assertNotFound();
        $this->postJson('/api/pt/assignments/'.$foreignAssignmentId.'/reassign', [
            'trainer_id' => $foreign['pt_b_id'],
            'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
        ], $this->bearer($adminToken))
            ->assertNotFound();
        $this->assertSame($foreignBefore, $this->snapshotAssignment($foreignAssignmentId));
        $this->assertSame($auditsBeforeForeign, $this->snapshotAssignmentAudits());

        $rowsBeforeRevokedAdmin = $this->snapshotAssignments();
        $auditsBeforeRevokedAdmin = $this->snapshotAssignmentAudits();
        $this->thuHoiVaiTroProfile($fixture['admin'], 'ADMIN');
        $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_b_id'],
            'trainer_id' => $fixture['pt_b_id'],
            'start_at' => CarbonImmutable::now('UTC')->addHours(2)->toISOString(),
        ], $this->bearer($adminToken))->assertForbidden();
        $this->assertSame($rowsBeforeRevokedAdmin, $this->snapshotAssignments());
        $this->assertSame($auditsBeforeRevokedAdmin, $this->snapshotAssignmentAudits());
    }

    public function test_assignment_create_and_reassign_require_active_member_trainer_and_effective_pt_role(): void
    {
        $inactiveMember = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($inactiveMember);
        $inactiveMemberToken = $this->layTokenProfile($inactiveMember['admin']);
        DB::table('nguoi_dung')->where('id', $inactiveMember['member_a']['user']->getKey())->update(['trang_thai' => 'BI_KHOA']);
        $rowsBefore = $this->snapshotAssignments();
        $auditsBefore = $this->snapshotAssignmentAudits();
        $this->postJson('/api/pt/assignments', [
            'member_id' => $inactiveMember['member_a_id'],
            'trainer_id' => $inactiveMember['pt_a_id'],
        ], $this->bearer($inactiveMemberToken))
            ->assertStatus(409)
            ->assertJsonPath('code', 'MEMBER_NOT_ACTIVE');
        $this->assertSame($rowsBefore, $this->snapshotAssignments());
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());

        $inactiveTrainer = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($inactiveTrainer);
        $inactiveTrainerToken = $this->layTokenProfile($inactiveTrainer['admin']);
        DB::table('nguoi_dung')->where('id', $inactiveTrainer['pt_a']['user']->getKey())->update(['trang_thai' => 'BI_KHOA']);
        $rowsBefore = $this->snapshotAssignments();
        $auditsBefore = $this->snapshotAssignmentAudits();
        $this->postJson('/api/pt/assignments', [
            'member_id' => $inactiveTrainer['member_a_id'],
            'trainer_id' => $inactiveTrainer['pt_a_id'],
        ], $this->bearer($inactiveTrainerToken))
            ->assertStatus(409)
            ->assertJsonPath('code', 'TRAINER_NOT_AVAILABLE');
        $this->assertSame($rowsBefore, $this->snapshotAssignments());
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());

        $pausedTrainer = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($pausedTrainer);
        $pausedTrainerToken = $this->layTokenProfile($pausedTrainer['admin']);
        DB::table('ho_so_huan_luyen_vien')->where('id', $pausedTrainer['pt_a_id'])->update(['trang_thai' => 'NGUNG_NHAN_PHAN_CONG']);
        $rowsBefore = $this->snapshotAssignments();
        $auditsBefore = $this->snapshotAssignmentAudits();
        $this->postJson('/api/pt/assignments', [
            'member_id' => $pausedTrainer['member_a_id'],
            'trainer_id' => $pausedTrainer['pt_a_id'],
        ], $this->bearer($pausedTrainerToken))
            ->assertStatus(409)
            ->assertJsonPath('code', 'TRAINER_NOT_AVAILABLE');
        $this->assertSame($rowsBefore, $this->snapshotAssignments());
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());

        $revokedTrainer = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($revokedTrainer);
        $revokedTrainerToken = $this->layTokenProfile($revokedTrainer['admin']);
        $this->thuHoiVaiTroProfile($revokedTrainer['pt_a'], 'PT');
        $rowsBefore = $this->snapshotAssignments();
        $auditsBefore = $this->snapshotAssignmentAudits();
        $this->postJson('/api/pt/assignments', [
            'member_id' => $revokedTrainer['member_a_id'],
            'trainer_id' => $revokedTrainer['pt_a_id'],
        ], $this->bearer($revokedTrainerToken))
            ->assertStatus(403)
            ->assertJsonPath('code', 'TRAINER_ROLE_REQUIRED');
        $this->assertSame($rowsBefore, $this->snapshotAssignments());
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());

        $reassignFixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($reassignFixture);
        $reassignToken = $this->layTokenProfile($reassignFixture['admin']);
        $assignment = $this->postJson('/api/pt/assignments', [
            'member_id' => $reassignFixture['member_a_id'],
            'trainer_id' => $reassignFixture['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->subHour()->toISOString(),
        ], $this->bearer($reassignToken))->assertCreated();
        $assignmentId = (int) $assignment->json('data.id');
        $oldBefore = $this->snapshotAssignment($assignmentId);
        $auditsBefore = $this->snapshotAssignmentAudits();

        DB::table('nguoi_dung')->where('id', $reassignFixture['pt_b']['user']->getKey())->update(['trang_thai' => 'BI_KHOA']);
        $this->postJson('/api/pt/assignments/'.$assignmentId.'/reassign', [
            'trainer_id' => $reassignFixture['pt_b_id'],
            'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
        ], $this->bearer($reassignToken))
            ->assertStatus(409)
            ->assertJsonPath('code', 'TRAINER_NOT_AVAILABLE');
        $this->assertSame($oldBefore, $this->snapshotAssignment($assignmentId));
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());

        DB::table('nguoi_dung')->where('id', $reassignFixture['pt_b']['user']->getKey())->update(['trang_thai' => 'HOAT_DONG']);
        $this->thuHoiVaiTroProfile($reassignFixture['pt_b'], 'PT');
        $this->postJson('/api/pt/assignments/'.$assignmentId.'/reassign', [
            'trainer_id' => $reassignFixture['pt_b_id'],
            'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
        ], $this->bearer($reassignToken))
            ->assertStatus(403)
            ->assertJsonPath('code', 'TRAINER_ROLE_REQUIRED');
        $this->assertSame($oldBefore, $this->snapshotAssignment($assignmentId));
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());
    }

    public function test_assignment_audit_lifecycle_has_exact_snapshots_correlation_and_silent_reads_noop_overlap(): void
    {
        $now = CarbonImmutable::parse('2026-09-09 10:00:00.000000', 'UTC');
        CarbonImmutable::setTestNow($now);

        try {
            $fixture = $this->taoBoPtFixtures();
            $this->dongBoChiNhanh($fixture);
            $adminToken = $this->layTokenProfile($fixture['admin']);
            $actorId = (int) $fixture['admin']['user']->getKey();

            $startA = $now->subHours(3);
            $createdA = $this->postJson('/api/pt/assignments', [
                'member_id' => $fixture['member_a_id'],
                'trainer_id' => $fixture['pt_a_id'],
                'start_at' => $startA->toISOString(),
            ], $this->bearer($adminToken))->assertCreated();
            $assignmentAId = (int) $createdA->json('data.id');
            $auditAAfterCreate = DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('dinh_danh_doi_tuong', $assignmentAId)
                ->orderBy('id')
                ->get();
            $this->assertCount(1, $auditAAfterCreate);
            $this->assertAssignmentAudit(
                $auditAAfterCreate->first(),
                'TAO_PHAN_CONG_HUAN_LUYEN_VIEN',
                $assignmentAId,
                null,
                $this->snapshotAuditMongDoi(
                    $assignmentAId,
                    $fixture['member_a_id'],
                    $fixture['pt_a_id'],
                    $actorId,
                    $startA,
                    null,
                    null,
                    true,
                ),
                $actorId,
                (string) $auditAAfterCreate->first()->khoa_tuong_quan,
            );

            $auditCountBeforeReads = $this->demAuditAssignment();
            $this->getJson('/api/pt/assignments?member_id='.$fixture['member_a_id'].'&current=true', $this->bearer($adminToken))
                ->assertOk();
            $this->getJson('/api/pt/assignments/'.$assignmentAId, $this->bearer($adminToken))
                ->assertOk();
            $this->assertSame($auditCountBeforeReads, $this->demAuditAssignment());

            $endedA = $this->patchJson('/api/pt/assignments/'.$assignmentAId.'/end', [
                'reason' => 'Kết thúc tại mốc đông lạnh',
            ], $this->bearer($adminToken))->assertOk();
            $this->assertSame($now->toISOString(), $endedA->json('data.end_at'));
            $auditAAfterEnd = DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('dinh_danh_doi_tuong', $assignmentAId)
                ->orderBy('id')
                ->get();
            $this->assertCount(2, $auditAAfterEnd);
            $this->assertAssignmentAudit(
                $auditAAfterEnd->get(1),
                'KET_THUC_PHAN_CONG_HUAN_LUYEN_VIEN',
                $assignmentAId,
                $this->snapshotAuditMongDoi(
                    $assignmentAId,
                    $fixture['member_a_id'],
                    $fixture['pt_a_id'],
                    $actorId,
                    $startA,
                    null,
                    null,
                    true,
                ),
                $this->snapshotAuditMongDoi(
                    $assignmentAId,
                    $fixture['member_a_id'],
                    $fixture['pt_a_id'],
                    $actorId,
                    $startA,
                    $now,
                    'Kết thúc tại mốc đông lạnh',
                    false,
                ),
                $actorId,
                (string) $auditAAfterEnd->get(1)->khoa_tuong_quan,
            );
            $this->assertNotSame('', (string) $auditAAfterEnd->get(0)->khoa_tuong_quan);
            $auditCountAfterEnd = $this->demAuditAssignment();
            $repeatedEnd = $this->patchJson('/api/pt/assignments/'.$assignmentAId.'/end', [
                'reason' => 'Không được ghi thêm',
            ], $this->bearer($adminToken))->assertOk();
            $this->assertSame($assignmentAId, (int) $repeatedEnd->json('data.id'));
            $this->assertSame($endedA->json('data.end_at'), $repeatedEnd->json('data.end_at'));
            $this->assertSame($auditCountAfterEnd, $this->demAuditAssignment());

            $startB = $now->subHours(5);
            $createdB = $this->postJson('/api/pt/assignments', [
                'member_id' => $fixture['member_b_id'],
                'trainer_id' => $fixture['pt_a_id'],
                'start_at' => $startB->toISOString(),
            ], $this->bearer($adminToken))->assertCreated();
            $assignmentBId = (int) $createdB->json('data.id');
            $auditBCreate = DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('dinh_danh_doi_tuong', $assignmentBId)
                ->orderBy('id')
                ->get();
            $this->assertCount(1, $auditBCreate);
            $this->assertAssignmentAudit(
                $auditBCreate->first(),
                'TAO_PHAN_CONG_HUAN_LUYEN_VIEN',
                $assignmentBId,
                null,
                $this->snapshotAuditMongDoi(
                    $assignmentBId,
                    $fixture['member_b_id'],
                    $fixture['pt_a_id'],
                    $actorId,
                    $startB,
                    null,
                    null,
                    true,
                ),
                $actorId,
                (string) $auditBCreate->first()->khoa_tuong_quan,
            );

            $reassignStart = $now->subHour();
            $createdNew = $this->postJson('/api/pt/assignments/'.$assignmentBId.'/reassign', [
                'trainer_id' => $fixture['pt_b_id'],
                'start_at' => $reassignStart->toISOString(),
                'reason' => 'Đổi PT theo mốc đông lạnh',
            ], $this->bearer($adminToken))->assertCreated();
            $newAssignmentId = (int) $createdNew->json('data.id');
            $auditBOld = DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('dinh_danh_doi_tuong', $assignmentBId)
                ->orderBy('id')
                ->get();
            $auditBNew = DB::table('nhat_ky_he_thong')
                ->where('loai_doi_tuong', 'PHAN_CONG_HUAN_LUYEN_VIEN')
                ->where('dinh_danh_doi_tuong', $newAssignmentId)
                ->orderBy('id')
                ->get();
            $this->assertCount(2, $auditBOld);
            $this->assertCount(1, $auditBNew);
            $this->assertNotSame($assignmentBId, $newAssignmentId);
            $this->assertNotSame('', (string) $auditBOld->get(1)->khoa_tuong_quan);
            $this->assertSame($auditBOld->get(1)->khoa_tuong_quan, $auditBNew->first()->khoa_tuong_quan);
            $this->assertAssignmentAudit(
                $auditBOld->get(1),
                'KET_THUC_PHAN_CONG_HUAN_LUYEN_VIEN',
                $assignmentBId,
                $this->snapshotAuditMongDoi(
                    $assignmentBId,
                    $fixture['member_b_id'],
                    $fixture['pt_a_id'],
                    $actorId,
                    $startB,
                    null,
                    null,
                    true,
                ),
                $this->snapshotAuditMongDoi(
                    $assignmentBId,
                    $fixture['member_b_id'],
                    $fixture['pt_a_id'],
                    $actorId,
                    $startB,
                    $reassignStart,
                    'Đổi PT theo mốc đông lạnh',
                    false,
                ),
                $actorId,
                (string) $auditBOld->get(1)->khoa_tuong_quan,
            );
            $this->assertAssignmentAudit(
                $auditBNew->first(),
                'TAO_PHAN_CONG_HUAN_LUYEN_VIEN',
                $newAssignmentId,
                null,
                $this->snapshotAuditMongDoi(
                    $newAssignmentId,
                    $fixture['member_b_id'],
                    $fixture['pt_b_id'],
                    $actorId,
                    $reassignStart,
                    null,
                    null,
                    true,
                ),
                $actorId,
                (string) $auditBNew->first()->khoa_tuong_quan,
            );

            $auditCountBeforeOverlap = $this->demAuditAssignment();
            $this->postJson('/api/pt/assignments', [
                'member_id' => $fixture['member_b_id'],
                'trainer_id' => $fixture['pt_a_id'],
                'start_at' => $now->subMinutes(30)->toISOString(),
            ], $this->bearer($adminToken))
                ->assertStatus(409)
                ->assertJsonPath('code', 'ASSIGNMENT_OVERLAP');
            $this->assertSame($auditCountBeforeOverlap, $this->demAuditAssignment());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_assignment_reassign_rolls_back_when_second_audit_creation_fails(): void
    {
        $fixture = $this->taoBoPtFixtures();
        $this->dongBoChiNhanh($fixture);
        $adminToken = $this->layTokenProfile($fixture['admin']);
        $assignment = $this->postJson('/api/pt/assignments', [
            'member_id' => $fixture['member_a_id'],
            'trainer_id' => $fixture['pt_a_id'],
            'start_at' => CarbonImmutable::now('UTC')->subHour()->toISOString(),
        ], $this->bearer($adminToken))->assertCreated();
        $assignmentId = (int) $assignment->json('data.id');
        $assignmentIdsBefore = DB::table('phan_cong_huan_luyen_vien')->orderBy('id')->pluck('id')->all();
        $oldBefore = $this->snapshotAssignment($assignmentId);
        $auditsBefore = $this->snapshotAssignmentAudits();
        $eventName = 'eloquent.creating: '.NhatKyHeThong::class;
        $listener = function (NhatKyHeThong $audit) use ($assignmentId): void {
            if ($audit->loai_doi_tuong === 'PHAN_CONG_HUAN_LUYEN_VIEN'
                && $audit->hanh_dong === 'TAO_PHAN_CONG_HUAN_LUYEN_VIEN'
                && (int) $audit->dinh_danh_doi_tuong !== $assignmentId) {
                throw new \RuntimeException('round-4 forced second assignment audit failure');
            }
        };
        Event::listen($eventName, $listener);

        try {
            try {
                app(PtAssignmentService::class)->phanCongLai(
                    $fixture['admin']['user']->fresh(),
                    $assignmentId,
                    [
                        'trainer_id' => $fixture['pt_b_id'],
                        'start_at' => CarbonImmutable::now('UTC')->addHour()->toISOString(),
                        'reason' => 'Rollback audit',
                    ],
                );
                $this->fail('Expected the forced second assignment audit failure.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('round-4 forced second assignment audit failure', $exception->getMessage());
            }
        } finally {
            Event::forget($eventName);
        }

        $this->assertSame($oldBefore, $this->snapshotAssignment($assignmentId));
        $this->assertSame($assignmentIdsBefore, DB::table('phan_cong_huan_luyen_vien')->orderBy('id')->pluck('id')->all());
        $this->assertSame($auditsBefore, $this->snapshotAssignmentAudits());
    }

    public function test_assignment_current_filter_classifies_end_boundary_start_boundary_and_future_exactly(): void
    {
        $now = CarbonImmutable::parse('2026-09-09 12:00:00.000000', 'UTC');
        CarbonImmutable::setTestNow($now);

        try {
            $fixture = $this->taoBoPtFixtures();
            $this->dongBoChiNhanh($fixture);
            $adminToken = $this->layTokenProfile($fixture['admin']);
            $endedStart = $now->subHours(2);
            $ended = $this->postJson('/api/pt/assignments', [
                'member_id' => $fixture['member_a_id'],
                'trainer_id' => $fixture['pt_a_id'],
                'start_at' => $endedStart->toISOString(),
                'end_at' => $now->toISOString(),
            ], $this->bearer($adminToken))->assertCreated();
            $atBoundary = $this->postJson('/api/pt/assignments', [
                'member_id' => $fixture['member_b_id'],
                'trainer_id' => $fixture['pt_a_id'],
                'start_at' => $now->toISOString(),
            ], $this->bearer($adminToken))->assertCreated();

            $futureFixture = $this->taoBoPtFixtures();
            $this->dongBoChiNhanh($futureFixture);
            DB::table('nguoi_dung')
                ->whereIn('id', [
                    $futureFixture['member_a']['user']->getKey(),
                    $futureFixture['member_b']['user']->getKey(),
                    $futureFixture['pt_a']['user']->getKey(),
                    $futureFixture['pt_b']['user']->getKey(),
                ])
                ->update(['chi_nhanh_id' => $fixture['admin']['branch_id']]);
            $futureStart = $now->addHour();
            $future = $this->postJson('/api/pt/assignments', [
                'member_id' => $futureFixture['member_a_id'],
                'trainer_id' => $futureFixture['pt_a_id'],
                'start_at' => $futureStart->toISOString(),
            ], $this->bearer($adminToken))->assertCreated();

            $endedId = (int) $ended->json('data.id');
            $atBoundaryId = (int) $atBoundary->json('data.id');
            $futureId = (int) $future->json('data.id');
            $current = $this->getJson('/api/pt/assignments?current=true&per_page=10', $this->bearer($adminToken))
                ->assertOk();
            $this->assertSame([$atBoundaryId], $current->json('data.items.*.id'));
            $this->assertTrue($current->json('data.items.0.is_current'));

            $notCurrent = $this->getJson('/api/pt/assignments?current=false&per_page=10', $this->bearer($adminToken))
                ->assertOk();
            $this->assertSame([$futureId, $endedId], $notCurrent->json('data.items.*.id'));
            $this->assertFalse($notCurrent->json('data.items.0.is_current'));
            $this->assertFalse($notCurrent->json('data.items.1.is_current'));

            $this->getJson('/api/pt/assignments/'.$endedId, $this->bearer($adminToken))
                ->assertOk()
                ->assertJsonPath('data.is_current', false);
            $this->getJson('/api/pt/assignments/'.$atBoundaryId, $this->bearer($adminToken))
                ->assertOk()
                ->assertJsonPath('data.is_current', true);
            $this->getJson('/api/pt/assignments/'.$futureId, $this->bearer($adminToken))
                ->assertOk()
                ->assertJsonPath('data.is_current', false);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
