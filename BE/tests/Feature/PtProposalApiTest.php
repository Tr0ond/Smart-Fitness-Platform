<?php

namespace Tests\Feature;

use App\Services\Pt\PtProposalAuditService;
use App\Services\Pt\PtProposalService;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class PtProposalApiTest extends TestCase
{
    use CreatesPtFixtures;
    use CreatesWorkoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-30 02:00:00.123456', 'UTC'));
        config(['ai.proposal_ttl_hours' => 24, 'ai.idempotency_ttl_hours' => 24]);
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_q13_create_preview_and_retry_are_assignment_scoped_without_paid_side_effects(): void
    {
        $bo = $this->taoBoProposal();
        $truoc = $this->demSideEffects($bo['member_id']);
        $key = (string) Str::uuid();

        $first = $this->taoDeXuat($bo, 'TAO_MOI', $key)->assertCreated()
            ->assertJsonPath('data.source', 'HUAN_LUYEN_VIEN')
            ->assertJsonPath('data.status', 'CHO_XAC_NHAN')
            ->assertJsonPath('data.assignment_id', $bo['assignment_id'])
            ->assertJsonPath('data.base_plan_id', null)
            ->assertJsonPath('data.replayed', false);
        $proposalId = (int) $first->json('data.id');
        $expiresAt = $first->json('data.expires_at');
        $content = DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('noi_dung_de_xuat');
        $hash = DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung');

        $this->taoDeXuat($bo, 'TAO_MOI', $key)->assertCreated()
            ->assertJsonPath('data.id', $proposalId)
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.expires_at', $expiresAt);
        $this->getJson('/api/pt/proposals/'.$proposalId, $this->bearer($bo['member_token']))
            ->assertOk()
            ->assertJsonPath('data.id', $proposalId)
            ->assertJsonPath('data.expires_at', $expiresAt);

        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'TAO_DE_XUAT_HUAN_LUYEN_VIEN')
            ->where('dinh_danh_doi_tuong', $proposalId)->count());
        $this->assertSame($content, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('noi_dung_de_xuat'));
        $this->assertSame($hash, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung'));
        $this->assertSame($truoc, $this->demSideEffects($bo['member_id']));

        $this->getJson('/api/pt/proposals/'.$proposalId, $this->bearer($bo['foreign_member_token']))
            ->assertNotFound();
        $this->getJson('/api/pt/members/'.$bo['member_id'].'/proposals', $this->bearer($bo['foreign_pt_token']))
            ->assertNotFound();
        $this->postJson('/api/pt/members/'.$bo['member_id'].'/proposals', $this->payload($bo, 'TAO_MOI'), [
            ...$this->bearer($bo['foreign_pt_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertNotFound();
        $this->postJson('/api/pt/members/'.$bo['member_id'].'/proposals', array_replace(
            $this->payload($bo, 'TAO_MOI'),
            ['title' => 'Nội dung khác'],
        ), [
            ...$this->bearer($bo['pt_token']),
            'Idempotency-Key' => $key,
        ])->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    }

    public function test_q13_ignores_chat_and_direct_quota_but_old_pt_cannot_create(): void
    {
        $khongQuyenPt = $this->taoBoProposal();
        $kyKhongQuyen = $this->taoMembershipPt($khongQuyenPt['fixture'], $khongQuyenPt['member_id'], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $truoc = $kyKhongQuyen->only([
            'trang_thai',
            'ngay_bat_dau',
            'ngay_ket_thuc',
            'so_buoi_huan_luyen_vien_da_dung',
            'so_luot_tro_ly_da_dung',
        ]);
        $this->taoDeXuat($khongQuyenPt, 'TAO_MOI', (string) Str::uuid())->assertCreated();
        $this->assertSame($truoc, $kyKhongQuyen->fresh()->only(array_keys($truoc)));
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $khongQuyenPt['member_id'])->count());

        $hetQuota = $this->taoBoProposal();
        $kyHetQuota = $this->taoMembershipPt($hetQuota['fixture'], $hetQuota['member_id'], [
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 1,
        ]);
        DB::table('ky_han_hoi_vien')->where('id', $kyHetQuota->getKey())->update([
            'so_buoi_huan_luyen_vien_da_dung' => 1,
        ]);
        $this->taoDeXuat($hetQuota, 'TAO_MOI', (string) Str::uuid())->assertCreated();
        $this->assertSame(1, (int) DB::table('ky_han_hoi_vien')->where('id', $kyHetQuota->getKey())->value('so_buoi_huan_luyen_vien_da_dung'));
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $hetQuota['member_id'])->count());

        $hienTai = CarbonImmutable::now('UTC');
        DB::table('phan_cong_huan_luyen_vien')->where('id', $hetQuota['assignment_id'])->update([
            'ngay_ket_thuc' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
        $this->taoPhanCongPt($hetQuota['fixture'], $hetQuota['member_id'], $hetQuota['foreign_pt_id'], $hienTai);
        $this->taoDeXuat($hetQuota, 'TAO_MOI', (string) Str::uuid())->assertNotFound();
        $this->postJson('/api/pt/members/'.$hetQuota['member_id'].'/proposals', $this->payload($hetQuota, 'TAO_MOI'), [
            ...$this->bearer($hetQuota['foreign_pt_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated();
    }

    public function test_confirm_new_plan_is_atomic_idempotent_and_keeps_proposal_immutable(): void
    {
        $bo = $this->taoBoProposal();
        $proposal = $this->taoDeXuat($bo, 'TAO_MOI', (string) Str::uuid())->assertCreated();
        $proposalId = (int) $proposal->json('data.id');
        $content = DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('noi_dung_de_xuat');
        $hash = DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung');
        $truoc = $this->demSideEffects($bo['member_id']);
        $key = (string) Str::uuid();

        $first = $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.proposal.status', 'DA_AP_DUNG')
            ->assertJsonPath('data.plan.status', 'DANG_SU_DUNG')
            ->assertJsonPath('data.plan.current_version.source', 'HUAN_LUYEN_VIEN')
            ->assertJsonPath('data.plan.current_version.number', 1)
            ->assertJsonPath('data.replayed', false);
        $planId = (int) $first->json('data.plan.id');
        $versionId = (int) $first->json('data.plan.current_version.id');

        $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('data.plan.id', $planId)
            ->assertJsonPath('data.plan.current_version.id', $versionId)
            ->assertJsonPath('data.replayed', true);

        $this->assertSame(1, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
        $this->assertSame($bo['pt']['user']->getKey(), (int) DB::table('phien_ban_ke_hoach_tap')->where('id', $versionId)->value('nguoi_tao_id'));
        $this->assertGreaterThan(0, DB::table('buoi_tap_du_kien')->where('phien_ban_ke_hoach_tap_id', $versionId)->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'AP_DUNG_DE_XUAT_HUAN_LUYEN_VIEN')
            ->where('dinh_danh_doi_tuong', $proposalId)->count());
        $this->assertSame($content, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('noi_dung_de_xuat'));
        $this->assertSame($hash, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung'));
        $this->assertSame($truoc, $this->demSideEffects($bo['member_id']));
    }

    public function test_reject_is_owner_only_idempotent_and_does_not_change_plan_or_schedule(): void
    {
        $bo = $this->taoBoProposal();
        $proposalId = (int) $this->taoDeXuat($bo, 'TAO_MOI', (string) Str::uuid())->json('data.id');
        $key = (string) Str::uuid();

        $this->postJson('/api/pt/proposals/'.$proposalId.'/reject', ['reason' => 'Chưa phù hợp'], [
            ...$this->bearer($bo['foreign_member_token']),
            'Idempotency-Key' => $key,
        ])->assertNotFound();
        $this->postJson('/api/pt/proposals/'.$proposalId.'/reject', ['reason' => 'Chưa phù hợp'], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => $key,
        ])->assertOk()
            ->assertJsonPath('data.status', 'DA_TU_CHOI')
            ->assertJsonPath('data.replayed', false);
        $this->postJson('/api/pt/proposals/'.$proposalId.'/reject', ['reason' => 'Chưa phù hợp'], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => $key,
        ])->assertOk()->assertJsonPath('data.replayed', true);

        $this->assertSame(0, DB::table('ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(0, DB::table('buoi_tap_du_kien')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'TU_CHOI_DE_XUAT_HUAN_LUYEN_VIEN')
            ->where('dinh_danh_doi_tuong', $proposalId)->count());
    }

    public function test_assignment_end_and_new_assignment_cannot_rescue_source_proposal(): void
    {
        $bo = $this->taoBoProposal();
        $proposalId = (int) $this->taoDeXuat($bo, 'TAO_MOI', (string) Str::uuid())->json('data.id');
        $hienTai = CarbonImmutable::now('UTC');
        DB::table('phan_cong_huan_luyen_vien')->where('id', $bo['assignment_id'])->update([
            'ngay_ket_thuc' => $hienTai,
            'ly_do_ket_thuc' => 'Đổi PT',
            'ngay_cap_nhat' => $hienTai,
        ]);
        $this->taoPhanCongPt($bo['fixture'], $bo['member_id'], $bo['foreign_pt_id'], $hienTai);

        $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)->assertJsonPath('code', 'PROPOSAL_ASSIGNMENT_CONFLICT');

        $this->assertSame('XUNG_DOT', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
        $this->assertSame(0, DB::table('ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'XUNG_DOT_DE_XUAT_HUAN_LUYEN_VIEN')
            ->where('dinh_danh_doi_tuong', $proposalId)->count());
    }

    public function test_existing_plan_stale_and_equipment_revalidation_block_old_proposals(): void
    {
        $stale = $this->taoBoProposal(true);
        $proposalId = (int) $this->taoDeXuat($stale, 'DIEU_CHINH', (string) Str::uuid())->json('data.id');
        app(WorkoutPlanService::class)->taoPhienBanTiepTheo(
            $stale['member']['user'],
            $stale['plan']['plan']->getKey(),
            $this->cauTrucWorkout($stale['exercise_id'], '2026-08-31', 'Plan thay đổi ngoài Proposal'),
        );
        $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
            ...$this->bearer($stale['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)->assertJsonPath('code', 'PT_PLAN_STALE');
        $this->assertSame('XUNG_DOT', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));

        $equipment = $this->taoBoProposal();
        $equipmentProposal = (int) $this->taoDeXuat($equipment, 'TAO_MOI', (string) Str::uuid())->json('data.id');
        $dungCuId = $this->taoDungCuAi();
        DB::table('bai_tap_dung_cu')->insert([
            'bai_tap_id' => $equipment['exercise_id'],
            'dung_cu_id' => $dungCuId,
            'ngay_tao' => CarbonImmutable::now('UTC'),
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);
        $this->postJson('/api/pt/proposals/'.$equipmentProposal.'/confirm', [], [
            ...$this->bearer($equipment['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)->assertJsonPath('code', 'REQUIRED_EQUIPMENT_MISSING');
        $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $equipmentProposal)->count());

        $template = $this->taoBoProposal();
        $templateId = $this->taoGiaoAnMauWorkout($template['pt'], $template['exercise_id']);
        $payload = $this->payload($template, 'TAO_MOI');
        $payload['plan']['template_id'] = $templateId;
        $templateProposal = (int) $this->postJson('/api/pt/members/'.$template['member_id'].'/proposals', $payload, [
            ...$this->bearer($template['pt_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertCreated()->json('data.id');
        DB::table('giao_an_mau')->where('id', $templateId)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $this->postJson('/api/pt/proposals/'.$templateProposal.'/confirm', [], [
            ...$this->bearer($template['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)->assertJsonPath('code', 'PT_TEMPLATE_STALE');
    }

    public function test_expiry_boundary_and_authority_fields_are_enforced(): void
    {
        $bo = $this->taoBoProposal();
        $this->postJson('/api/pt/members/'.$bo['member_id'].'/proposals', array_merge($this->payload($bo, 'TAO_MOI'), [
            'assignment_id' => 999,
            'status' => 'DA_AP_DUNG',
            'base_version_id' => 999,
        ]), [
            ...$this->bearer($bo['pt_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertUnprocessable();

        $conHopLe = $this->taoBoProposal();
        $conHopLeId = (int) $this->taoDeXuat($conHopLe, 'TAO_MOI', (string) Str::uuid())->json('data.id');
        $hetHanConHopLe = CarbonImmutable::now('UTC')->addSecond();
        DB::table('de_xuat_ke_hoach_tap')->where('id', $conHopLeId)->update([
            'het_han_luc' => $hetHanConHopLe->format('Y-m-d H:i:s.u'),
        ]);
        CarbonImmutable::setTestNow($hetHanConHopLe->subMicrosecond());
        $this->postJson('/api/pt/proposals/'.$conHopLeId.'/confirm', [], [
            ...$this->bearer($conHopLe['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk();

        $proposalId = (int) $this->taoDeXuat($bo, 'TAO_MOI', (string) Str::uuid())->json('data.id');
        $hetHan = CarbonImmutable::now('UTC')->addSecond();
        DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->update([
            'het_han_luc' => $hetHan->format('Y-m-d H:i:s.u'),
        ]);
        CarbonImmutable::setTestNow($hetHan);
        $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertStatus(409)->assertJsonPath('code', 'PT_PROPOSAL_EXPIRED');
        $this->assertSame('HET_HAN', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
    }

    public function test_late_audit_failure_rolls_back_plan_schedule_marker_and_confirm_idempotency(): void
    {
        $bo = $this->taoBoProposal();
        $proposalId = (int) $this->taoDeXuat($bo, 'TAO_MOI', (string) Str::uuid())->json('data.id');
        $audit = $this->createMock(PtProposalAuditService::class);
        $audit->expects($this->once())
            ->method('ghiProposal')
            ->willThrowException(new RuntimeException('controlled PT audit failure'));
        app()->instance(PtProposalAuditService::class, $audit);

        try {
            app(PtProposalService::class)->xacNhan($bo['member']['user'], $proposalId, (string) Str::uuid());
            $this->fail('Controlled late failure phải rollback toàn bộ transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('controlled PT audit failure', $exception->getMessage());
        }

        $this->assertSame('CHO_XAC_NHAN', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
        $this->assertSame(0, DB::table('ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(0, DB::table('buoi_tap_du_kien')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(0, (int) DB::table('ho_so_hoi_vien')->where('id', $bo['member_id'])->value('moc_thay_doi_ke_hoach'));
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $bo['member']['user']->getKey())
            ->where('pham_vi', 'XAC_NHAN_DE_XUAT_HUAN_LUYEN_VIEN')->count());
    }

    public function test_confirm_next_version_preserves_completed_history_and_note_is_informational(): void
    {
        $bo = $this->taoBoProposal(true);
        $lich = $this->taoLichWorkout($bo['member'], $bo['plan'], '2026-08-31');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 01:00:00.123456', 'UTC'));
        $phien = app(WorkoutSessionService::class)->batDau(
            $bo['member']['user'],
            (int) $lich->getKey(),
            (string) Str::uuid(),
        );
        $baiTrongPhien = (int) DB::table('bai_tap_trong_phien')->where('phien_tap_id', $phien['id'])->value('id');
        app(WorkoutSessionService::class)->ghiHiep(
            $bo['member']['user'],
            (int) $phien['id'],
            $baiTrongPhien,
            ['order' => 1, 'reps' => 10, 'weight_kg' => 25, 'actual_rest_seconds' => 60],
            (string) Str::uuid(),
        );
        app(WorkoutSessionService::class)->hoanThanh($bo['member']['user'], (int) $phien['id'], (string) Str::uuid());
        $historyBefore = [
            DB::table('phien_tap')->where('id', $phien['id'])->get()->toJson(),
            DB::table('bai_tap_trong_phien')->where('phien_tap_id', $phien['id'])->get()->toJson(),
            DB::table('hiep_tap')->where('bai_tap_trong_phien_id', $baiTrongPhien)->get()->toJson(),
        ];

        $note = $this->postJson('/api/pt/members/'.$bo['member_id'].'/notes', [
            'content' => 'Giữ nhịp thở ổn định; đây chỉ là ghi chú.',
            'plan_id' => $bo['plan']['plan']->getKey(),
            'session_id' => $phien['id'],
        ], $this->bearer($bo['pt_token']))->assertCreated();
        $this->getJson('/api/pt/notes', $this->bearer($bo['member_token']))
            ->assertOk()->assertJsonPath('data.0.id', $note->json('data.id'));

        $proposalId = (int) $this->taoDeXuat($bo, 'DIEU_CHINH', (string) Str::uuid())->json('data.id');
        $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
            ...$this->bearer($bo['member_token']),
            'Idempotency-Key' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('data.plan.current_version.number', 2);

        $this->assertSame($historyBefore[0], DB::table('phien_tap')->where('id', $phien['id'])->get()->toJson());
        $this->assertSame($historyBefore[1], DB::table('bai_tap_trong_phien')->where('phien_tap_id', $phien['id'])->get()->toJson());
        $this->assertSame($historyBefore[2], DB::table('hiep_tap')->where('bai_tap_trong_phien_id', $baiTrongPhien)->get()->toJson());
        $this->assertSame('HOAN_THANH', DB::table('buoi_tap_du_kien')->where('id', $lich->getKey())->value('trang_thai'));
        $this->assertSame(1, DB::table('ghi_chu_huan_luyen')->where('id', $note->json('data.id'))->count());
    }

    public function test_apply_keeps_in_progress_and_skipped_schedule_rows_unchanged(): void
    {
        foreach (['DANG_TAP', 'BO_QUA'] as $trangThai) {
            $bo = $this->taoBoProposal(true);
            $lich = $this->taoLichWorkout($bo['member'], $bo['plan'], '2026-08-31');
            CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 01:00:00.123456', 'UTC'));
            if ($trangThai === 'DANG_TAP') {
                app(WorkoutSessionService::class)->batDau(
                    $bo['member']['user'],
                    (int) $lich->getKey(),
                    (string) Str::uuid(),
                );
            } else {
                app(WorkoutScheduleService::class)->boQua($bo['member']['user'], (int) $lich->getKey());
            }

            $payload = $this->payload($bo, 'DIEU_CHINH');
            $payload['effective_from'] = '2026-08-31';
            $proposalId = (int) $this->postJson('/api/pt/members/'.$bo['member_id'].'/proposals', $payload, [
                ...$this->bearer($bo['pt_token']),
                'Idempotency-Key' => (string) Str::uuid(),
            ])->assertCreated()->json('data.id');
            $this->postJson('/api/pt/proposals/'.$proposalId.'/confirm', [], [
                ...$this->bearer($bo['member_token']),
                'Idempotency-Key' => (string) Str::uuid(),
            ])->assertOk();

            $this->assertSame($trangThai, DB::table('buoi_tap_du_kien')->where('id', $lich->getKey())->value('trang_thai'));
            $this->assertSame(1, DB::table('buoi_tap_du_kien')
                ->where('hoi_vien_id', $bo['member_id'])
                ->where('ngay_tap', '2026-08-31')->count());
        }
    }

    /** @return array<string, mixed> */
    private function taoBoProposal(bool $coPlan = false): array
    {
        $fixture = $this->taoBoPtFixtures();
        $assignmentId = $this->taoPhanCongPt($fixture, $fixture['member_a_id'], $fixture['pt_a_id']);
        $exerciseId = $this->taoBaiTapWorkout($fixture['pt_a']);
        $ptToken = $this->taoTheTruyCapThuCong($fixture['pt_a']['user'], CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addDay());
        $memberToken = $this->taoTheTruyCapThuCong($fixture['member_a']['user'], CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addDay());
        $foreignMemberToken = $this->taoTheTruyCapThuCong($fixture['member_b']['user'], CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addDay());
        $foreignPtToken = $this->taoTheTruyCapThuCong($fixture['pt_b']['user'], CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addDay());
        $member = $fixture['member_a'] + ['member_id' => $fixture['member_a_id']];
        $plan = $coPlan ? $this->taoPlanWorkout($member, $exerciseId, '2026-08-31') : null;

        return [
            'fixture' => $fixture,
            'pt' => $fixture['pt_a'],
            'member' => $member,
            'member_id' => $fixture['member_a_id'],
            'foreign_pt_id' => $fixture['pt_b_id'],
            'assignment_id' => $assignmentId,
            'exercise_id' => $exerciseId,
            'pt_token' => $ptToken,
            'member_token' => $memberToken,
            'foreign_member_token' => $foreignMemberToken,
            'foreign_pt_token' => $foreignPtToken,
            'plan' => $plan,
        ];
    }

    private function taoDeXuat(array $bo, string $loai, string $key)
    {
        return $this->postJson('/api/pt/members/'.$bo['member_id'].'/proposals', $this->payload($bo, $loai), [
            ...$this->bearer($bo['pt_token']),
            'Idempotency-Key' => $key,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $bo, string $loai): array
    {
        return [
            'change_type' => $loai,
            'title' => 'Đề xuất PT cho kế hoạch tương lai',
            'explanation' => 'Điều chỉnh có kiểm soát và cần hội viên xác nhận.',
            'effective_from' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'plan' => [
                'name' => 'Kế hoạch PT đề xuất',
                'goal' => 'TANG_SUC_MANH',
                'days' => [[
                    'order' => 1,
                    'weekday' => 2,
                    'name' => 'Ngày PT đề xuất',
                    'estimated_minutes' => 60,
                    'exercises' => [[
                        'exercise_id' => $bo['exercise_id'],
                        'order' => 1,
                        'target_sets' => 4,
                        'min_reps' => 6,
                        'max_reps' => 10,
                        'target_weight_kg' => 25,
                        'rest_seconds' => 90,
                        'notes' => 'Chỉ áp dụng sau xác nhận',
                    ]],
                ]],
            ],
        ];
    }

    /** @return array<string, int> */
    private function demSideEffects(int $hoiVienId): array
    {
        return [
            'terms' => DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $hoiVienId)->count(),
            'usage' => DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $hoiVienId)->count(),
            'pt_direct' => DB::table('lich_su_su_dung_huan_luyen_vien')->where('hoi_vien_id', $hoiVienId)->count(),
            'ai_requests' => DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $hoiVienId)->count(),
            'ai_calls' => DB::table('lan_goi_mo_hinh')->whereIn('yeu_cau_tro_ly_id', DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $hoiVienId)->pluck('id'))->count(),
        ];
    }
}
