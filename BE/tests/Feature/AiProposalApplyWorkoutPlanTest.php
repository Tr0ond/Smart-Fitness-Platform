<?php

namespace Tests\Feature;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Gateways\GeminiWorkoutAiProvider;
use App\Services\Ai\AiProposalApplyService;
use App\Services\Ai\AiProposalAuditService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\Fakes\FakeWorkoutAiProvider;
use Tests\TestCase;

class AiProposalApplyWorkoutPlanTest extends TestCase
{
    use CreatesWorkoutFixtures;

    private FakeWorkoutAiProvider $fake;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-30 02:00:00.123456', 'UTC'));
        $this->batDauGiaoDichAuthCoLap();
        config(['ai.idempotency_ttl_hours' => 24, 'ai.proposal_ttl_hours' => 24]);
        $this->fake = new FakeWorkoutAiProvider;
        app()->instance(WorkoutAiProvider::class, $this->fake);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_tao_moi_apply_dung_mot_lan_va_khong_goi_provider_quota_hay_membership(): void
    {
        $bo = $this->taoBoApply();
        $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
        $key = (string) Str::uuid();
        $kyTruoc = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $usageTruoc = DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->count();
        $chatUsageTruoc = DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->where('loai_su_dung', 'TRO_CHUYEN_HUAN_LUYEN_VIEN')->count();
        $deXuatTruoc = DB::table('de_xuat_ke_hoach_tap')->find($proposalId);
        $calls = $this->fake->callCount;
        config([
            'ai.provider' => 'gemini',
            'ai.model' => 'gemini-apply-must-not-call',
            'ai.gemini.api_key' => 'dummy-testing-key',
            'ai.gemini.base_url' => 'https://generativelanguage.googleapis.com',
        ]);
        app()->instance(WorkoutAiProvider::class, app(GeminiWorkoutAiProvider::class));
        Http::preventStrayRequests();

        $lanDau = $this->apply($bo, $proposalId, $key)->assertOk()
            ->assertJsonPath('data.proposal.status', 'DA_AP_DUNG')
            ->assertJsonPath('data.plan.status', 'DANG_SU_DUNG')
            ->assertJsonPath('data.plan.current_version.source', 'TRO_LY')
            ->assertJsonPath('data.plan.current_version.number', 1)
            ->assertJsonPath('data.replayed', false);
        $planId = (int) $lanDau->json('data.plan.id');
        $versionId = (int) $lanDau->json('data.plan.current_version.id');

        $this->apply($bo, $proposalId, $key)->assertOk()
            ->assertJsonPath('data.plan.id', $planId)
            ->assertJsonPath('data.plan.current_version.id', $versionId)
            ->assertJsonPath('data.replayed', true);

        $this->assertSame($calls, $this->fake->callCount);
        Http::assertNothingSent();
        $this->assertSame(1, DB::table('ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(1, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
        $this->assertGreaterThan(0, DB::table('buoi_tap_du_kien')->where('phien_ban_ke_hoach_tap_id', $versionId)->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'AP_DUNG_DE_XUAT_TRO_LY')->where('dinh_danh_doi_tuong', $proposalId)->count());
        $this->assertSame($usageTruoc, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->count());
        $kySau = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $this->assertSame($kyTruoc->ngay_bat_dau, $kySau->ngay_bat_dau);
        $this->assertSame($kyTruoc->ngay_ket_thuc, $kySau->ngay_ket_thuc);
        $this->assertSame((int) $kyTruoc->so_luot_tro_ly_da_dung, (int) $kySau->so_luot_tro_ly_da_dung);
        $this->assertSame((int) $kyTruoc->so_buoi_huan_luyen_vien_da_dung, (int) $kySau->so_buoi_huan_luyen_vien_da_dung);
        $this->assertSame($chatUsageTruoc, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->where('loai_su_dung', 'TRO_CHUYEN_HUAN_LUYEN_VIEN')->count());
        $deXuatSau = DB::table('de_xuat_ke_hoach_tap')->find($proposalId);
        $this->assertSame($deXuatTruoc->noi_dung_de_xuat, $deXuatSau->noi_dung_de_xuat);
        $this->assertSame($deXuatTruoc->ma_bam_noi_dung, $deXuatSau->ma_bam_noi_dung);
        $this->assertSame($deXuatTruoc->tieu_de, $deXuatSau->tieu_de);
        $this->assertSame(1, (int) DB::table('ho_so_hoi_vien')->where('id', $bo['member_id'])->value('moc_thay_doi_ke_hoach'));
    }

    public function test_apply_validation_role_idor_va_authority_fields(): void
    {
        $bo = $this->taoBoApply();
        $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
        $this->flushHeaders();
        $this->postJson('/api/assistant/proposals/'.$proposalId.'/apply')->assertUnauthorized();

        $pt = $this->taoNguoiDungAuth(['PT']);
        $tokenPt = (string) $this->dangNhapApi($pt['user']->thu_dien_tu)->json('data.access_token');
        $this->withHeaders(array_merge($this->bearer($tokenPt), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/assistant/proposals/'.$proposalId.'/apply')->assertForbidden();

        $khac = $this->taoBoApply();
        $this->apply($khac, $proposalId, (string) Str::uuid())->assertNotFound();
        $this->withHeaders(array_merge($this->bearer($bo['token']), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/assistant/proposals/'.$proposalId.'/apply', [
                'plan_id' => 999,
                'content' => ['tamper' => true],
                'quota' => 999,
            ])->assertUnprocessable();
        $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
    }

    public function test_ttl_uses_microsecond_boundary_and_persists_expired_terminal_state(): void
    {
        $hopLe = $this->taoBoApply();
        $hopLeId = $this->taoProposal($hopLe, 'TAO_KE_HOACH');
        $hetHanHopLe = CarbonImmutable::now('UTC')->addSecond();
        DB::table('de_xuat_ke_hoach_tap')->where('id', $hopLeId)->update([
            'het_han_luc' => $hetHanHopLe->format('Y-m-d H:i:s.u'),
        ]);
        CarbonImmutable::setTestNow($hetHanHopLe->subMicrosecond());
        $this->apply($hopLe, $hopLeId, (string) Str::uuid())->assertOk();

        $bo = $this->taoBoApply();
        $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
        $hetHan = CarbonImmutable::now('UTC')->addSecond();
        DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->update([
            'het_han_luc' => $hetHan->format('Y-m-d H:i:s.u'),
        ]);

        CarbonImmutable::setTestNow($hetHan);
        $this->apply($bo, $proposalId, (string) Str::uuid())->assertStatus(409)
            ->assertJsonPath('code', 'AI_PROPOSAL_EXPIRED');
        $this->assertSame('HET_HAN', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
        $this->assertSame(0, DB::table('ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
    }

    public function test_tampered_content_profile_availability_and_candidate_are_blocked(): void
    {
        $tampered = $this->taoBoApply();
        $tamperedId = $this->taoProposal($tampered, 'TAO_KE_HOACH');
        DB::table('de_xuat_ke_hoach_tap')->where('id', $tamperedId)->update(['ma_bam_noi_dung' => str_repeat('f', 64)]);
        $response = $this->apply($tampered, $tamperedId, (string) Str::uuid());
        $this->assertSame(409, $response->status(), $response->getContent());
        $response
            ->assertJsonPath('code', 'AI_PROPOSAL_INTEGRITY_FAILED');

        $profile = $this->taoBoApply();
        $profileId = $this->taoProposal($profile, 'TAO_KE_HOACH');
        DB::table('ho_so_hoi_vien')->where('id', $profile['member_id'])->increment('phien_ban_ho_so');
        $response = $this->apply($profile, $profileId, (string) Str::uuid());
        $this->assertSame(409, $response->status(), $response->getContent());
        $response
            ->assertJsonPath('code', 'AI_PROPOSAL_CONTEXT_STALE');

        $availability = $this->taoBoApply();
        $availabilityId = $this->taoProposal($availability, 'TAO_KE_HOACH');
        DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $availability['member_id'])->where('thu_trong_tuan', 6)->delete();
        $response = $this->apply($availability, $availabilityId, (string) Str::uuid());
        $this->assertSame(409, $response->status(), $response->getContent());
        $response
            ->assertJsonPath('code', 'AI_AVAILABILITY_STALE');

        $candidate = $this->taoBoApply();
        $candidateId = $this->taoProposal($candidate, 'TAO_KE_HOACH');
        $noiDung = json_decode((string) DB::table('de_xuat_ke_hoach_tap')->where('id', $candidateId)->value('noi_dung_de_xuat'), true, flags: JSON_THROW_ON_ERROR);
        $baiTapDaChon = (int) $noiDung['ngay_trong_ke_hoach'][0]['bai_tap_trong_ke_hoach'][0]['bai_tap_id'];
        DB::table('bai_tap')->where('id', $baiTapDaChon)->update(['trang_thai' => 'NGUNG_SU_DUNG']);
        $response = $this->apply($candidate, $candidateId, (string) Str::uuid());
        $this->assertSame(409, $response->status(), $response->getContent());
        $response
            ->assertJsonPath('code', 'AI_CANDIDATE_STALE');

        $equipment = $this->taoBoApply();
        $equipmentId = $this->taoProposal($equipment, 'TAO_KE_HOACH');
        $noiDung = json_decode((string) DB::table('de_xuat_ke_hoach_tap')->where('id', $equipmentId)->value('noi_dung_de_xuat'), true, flags: JSON_THROW_ON_ERROR);
        $baiTapDaChon = (int) $noiDung['ngay_trong_ke_hoach'][0]['bai_tap_trong_ke_hoach'][0]['bai_tap_id'];
        $dungCuMoi = $this->taoDungCuAi();
        DB::table('bai_tap_dung_cu')->insert([
            'bai_tap_id' => $baiTapDaChon,
            'dung_cu_id' => $dungCuMoi,
            'ngay_tao' => CarbonImmutable::now('UTC'),
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);
        $response = $this->apply($equipment, $equipmentId, (string) Str::uuid());
        $this->assertSame(409, $response->status(), $response->getContent());
        $response->assertJsonPath('code', 'AI_CANDIDATE_STALE');

        $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')->whereIn('de_xuat_ke_hoach_tap_id', [$tamperedId, $profileId, $availabilityId, $candidateId, $equipmentId])->count());
    }

    public function test_terminal_proposal_state_cannot_be_applied(): void
    {
        foreach (['DA_TU_CHOI', 'XUNG_DOT', 'HET_HAN'] as $trangThai) {
            $bo = $this->taoBoApply();
            $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
            DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->update(['trang_thai' => $trangThai]);
            $this->apply($bo, $proposalId, (string) Str::uuid())->assertStatus(409)
                ->assertJsonPath('code', 'AI_PROPOSAL_NOT_APPLICABLE');
            $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
        }
    }

    public function test_dieu_chinh_creates_next_version_same_plan_and_stale_second_proposal_loses(): void
    {
        $bo = $this->taoBoApply();
        $plan = $this->taoPlanWorkout($bo, $bo['exercise_id']);
        $v1Truoc = DB::table('phien_ban_ke_hoach_tap')->find($plan['version']->getKey());
        $ngayV1Truoc = DB::table('ngay_trong_ke_hoach')->where('phien_ban_ke_hoach_tap_id', $plan['version']->getKey())->orderBy('id')->get()->toJson();
        $baiV1Truoc = DB::table('bai_tap_trong_ke_hoach')->whereIn('ngay_trong_ke_hoach_id', DB::table('ngay_trong_ke_hoach')->where('phien_ban_ke_hoach_tap_id', $plan['version']->getKey())->pluck('id'))->orderBy('id')->get()->toJson();
        $a = $this->taoProposal($bo, 'DIEU_CHINH');
        $b = $this->taoProposal($bo, 'DIEU_CHINH');

        $this->apply($bo, $a, (string) Str::uuid())->assertOk()
            ->assertJsonPath('data.plan.id', $plan['plan']->getKey())
            ->assertJsonPath('data.plan.current_version.number', 2);
        $this->apply($bo, $b, (string) Str::uuid())->assertStatus(409)
            ->assertJsonPath('code', 'AI_PLAN_STALE');

        $this->assertSame('DA_AP_DUNG', DB::table('de_xuat_ke_hoach_tap')->where('id', $a)->value('trang_thai'));
        $this->assertSame('XUNG_DOT', DB::table('de_xuat_ke_hoach_tap')->where('id', $b)->value('trang_thai'));
        $this->assertSame(2, DB::table('phien_ban_ke_hoach_tap')->where('ke_hoach_tap_id', $plan['plan']->getKey())->count());
        $this->assertEquals($v1Truoc, DB::table('phien_ban_ke_hoach_tap')->find($plan['version']->getKey()));
        $this->assertSame($ngayV1Truoc, DB::table('ngay_trong_ke_hoach')->where('phien_ban_ke_hoach_tap_id', $plan['version']->getKey())->orderBy('id')->get()->toJson());
        $this->assertSame($baiV1Truoc, DB::table('bai_tap_trong_ke_hoach')->whereIn('ngay_trong_ke_hoach_id', DB::table('ngay_trong_ke_hoach')->where('phien_ban_ke_hoach_tap_id', $plan['version']->getKey())->pluck('id'))->orderBy('id')->get()->toJson());
    }

    public function test_membership_expiry_after_proposal_does_not_block_apply_or_charge_again(): void
    {
        $bo = $this->taoBoApply();
        $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
        $usage = DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->count();
        DB::table('ky_han_hoi_vien')->where('id', $bo['term']->getKey())->update(['trang_thai' => 'HET_HAN']);
        DB::table('dang_ky_goi_tap')->where('id', $bo['term']->dang_ky_goi_tap_id)->update(['trang_thai' => 'HET_HAN']);

        $this->apply($bo, $proposalId, (string) Str::uuid())->assertOk();
        $this->assertSame($usage, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->count());
    }

    public function test_apply_only_replaces_future_chua_tap_and_protects_dang_tap_hoan_thanh_bo_qua_history(): void
    {
        $ngayTap = '2026-08-31';
        $cacTrangThai = ['CHUA_TAP', 'DANG_TAP', 'HOAN_THANH', 'BO_QUA'];
        $duLieu = [];
        foreach ($cacTrangThai as $trangThai) {
            $bo = $this->taoBoApply();
            $plan = $this->taoPlanWorkout($bo, $bo['exercise_id'], $ngayTap);
            $lich = $this->taoLichWorkout($bo, $plan, $ngayTap);
            $proposalId = $this->taoProposal($bo, $trangThai === 'CHUA_TAP' ? 'THAY_BAI' : 'DIEU_CHINH');
            $duLieu[$trangThai] = compact('bo', 'plan', 'lich', 'proposalId');
        }

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 01:00:00.123456', 'UTC'));
        foreach (['DANG_TAP', 'HOAN_THANH'] as $trangThai) {
            $muc = $duLieu[$trangThai];
            $session = app(WorkoutSessionService::class)->batDau(
                $muc['bo']['user'],
                (int) $muc['lich']->getKey(),
                (string) Str::uuid(),
            );
            $duLieu[$trangThai]['session_id'] = (int) $session['id'];
            if ($trangThai === 'HOAN_THANH') {
                $baiTrongPhienId = (int) DB::table('bai_tap_trong_phien')->where('phien_tap_id', $session['id'])->value('id');
                app(WorkoutSessionService::class)->ghiHiep(
                    $muc['bo']['user'],
                    (int) $session['id'],
                    $baiTrongPhienId,
                    ['order' => 1, 'reps' => 10, 'weight_kg' => 25, 'actual_rest_seconds' => 75],
                    (string) Str::uuid(),
                );
                app(WorkoutSessionService::class)->hoanThanh(
                    $muc['bo']['user'],
                    (int) $session['id'],
                    (string) Str::uuid(),
                );
            }
        }
        app(WorkoutScheduleService::class)->boQua(
            $duLieu['BO_QUA']['bo']['user'],
            (int) $duLieu['BO_QUA']['lich']->getKey(),
        );
        $lichSuTruoc = [];
        foreach (['DANG_TAP', 'HOAN_THANH'] as $trangThai) {
            $sessionId = $duLieu[$trangThai]['session_id'];
            $baiIds = DB::table('bai_tap_trong_phien')->where('phien_tap_id', $sessionId)->pluck('id');
            $lichSuTruoc[$trangThai] = [
                'session' => DB::table('phien_tap')->where('id', $sessionId)->get()->toJson(),
                'exercises' => DB::table('bai_tap_trong_phien')->where('phien_tap_id', $sessionId)->orderBy('id')->get()->toJson(),
                'sets' => DB::table('hiep_tap')->whereIn('bai_tap_trong_phien_id', $baiIds)->orderBy('id')->get()->toJson(),
            ];
        }

        foreach ($duLieu as $trangThai => $muc) {
            $this->apply($muc['bo'], $muc['proposalId'], (string) Str::uuid())->assertOk();
            $lichCu = DB::table('buoi_tap_du_kien')->find($muc['lich']->getKey());
            if ($trangThai === 'CHUA_TAP') {
                $this->assertSame('DA_THAY_THE', $lichCu->trang_thai);
                $this->assertSame(1, DB::table('buoi_tap_du_kien')
                    ->where('hoi_vien_id', $muc['bo']['member_id'])
                    ->where('ngay_tap', $ngayTap)
                    ->where('trang_thai', 'CHUA_TAP')->count());
            } else {
                $this->assertSame($trangThai, $lichCu->trang_thai);
                $this->assertSame(1, DB::table('buoi_tap_du_kien')
                    ->where('hoi_vien_id', $muc['bo']['member_id'])
                    ->where('ngay_tap', $ngayTap)->count());
            }
        }
        $this->assertSame(2, DB::table('phien_tap')->whereIn('hoi_vien_id', [
            $duLieu['DANG_TAP']['bo']['member_id'],
            $duLieu['HOAN_THANH']['bo']['member_id'],
        ])->count());
        foreach (['DANG_TAP', 'HOAN_THANH'] as $trangThai) {
            $sessionId = $duLieu[$trangThai]['session_id'];
            $baiIds = DB::table('bai_tap_trong_phien')->where('phien_tap_id', $sessionId)->pluck('id');
            $this->assertSame($lichSuTruoc[$trangThai]['session'], DB::table('phien_tap')->where('id', $sessionId)->get()->toJson());
            $this->assertSame($lichSuTruoc[$trangThai]['exercises'], DB::table('bai_tap_trong_phien')->where('phien_tap_id', $sessionId)->orderBy('id')->get()->toJson());
            $this->assertSame($lichSuTruoc[$trangThai]['sets'], DB::table('hiep_tap')->whereIn('bai_tap_trong_phien_id', $baiIds)->orderBy('id')->get()->toJson());
        }
    }

    public function test_late_audit_failure_rolls_back_plan_schedule_proposal_marker_and_idempotency(): void
    {
        $bo = $this->taoBoApply();
        $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
        $audit = $this->createMock(AiProposalAuditService::class);
        $audit->expects($this->once())
            ->method('ghiDaApDung')
            ->willThrowException(new RuntimeException('controlled audit failure'));
        app()->instance(AiProposalAuditService::class, $audit);

        try {
            app(AiProposalApplyService::class)->apDung($bo['user'], $proposalId, (string) Str::uuid());
            $this->fail('Controlled late failure phải bubble để transaction rollback.');
        } catch (RuntimeException $exception) {
            $this->assertSame('controlled audit failure', $exception->getMessage());
        }

        $this->assertSame('CHO_XAC_NHAN', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
        $this->assertSame(0, DB::table('ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(0, DB::table('buoi_tap_du_kien')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(0, (int) DB::table('ho_so_hoi_vien')->where('id', $bo['member_id'])->value('moc_thay_doi_ke_hoach'));
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')->where('pham_vi', 'AP_DUNG_DE_XUAT_TRO_LY')->count());
    }

    /** @return array<string, mixed> */
    private function taoBoApply(): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $exerciseId = $this->taoBaiTapAi($fixture);
        $term = $this->taoMembershipAi($fixture, $memberId, 20);
        $token = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');

        return $fixture + compact('memberId', 'exerciseId', 'term', 'token') + [
            'member_id' => $memberId,
            'exercise_id' => $exerciseId,
        ];
    }

    private function taoProposal(array $bo, string $loai): int
    {
        $response = $this->withHeaders(array_merge($this->bearer($bo['token']), ['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/assistant/requests', [
                'request_type' => $loai,
                'prompt' => 'Đề xuất kế hoạch tập an toàn từ candidate Backend.',
            ]);
        $response->assertCreated();

        return (int) $response->json('data.proposal.id');
    }

    private function apply(array $bo, int $proposalId, string $key)
    {
        return $this->withHeaders(array_merge($this->bearer($bo['token']), ['Idempotency-Key' => $key]))
            ->postJson('/api/assistant/proposals/'.$proposalId.'/apply');
    }
}
