<?php

namespace Tests\Feature;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Data\Ai\WorkoutAiResult;
use App\Exceptions\Ai\AiProviderException;
use App\Gateways\GeminiWorkoutAiProvider;
use App\Services\Ai\AiRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesAiFixtures;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Fakes\FakeWorkoutAiProvider;
use Tests\TestCase;

class AiRequestApiTest extends TestCase
{
    use CreatesAiFixtures;
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    private FakeWorkoutAiProvider $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        config([
            'ai.proposal_ttl_hours' => 24,
            'ai.idempotency_ttl_hours' => 24,
            'ai.candidate_limit' => 200,
        ]);
        $this->fake = new FakeWorkoutAiProvider;
        app()->instance(WorkoutAiProvider::class, $this->fake);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_validation_role_and_out_of_scope_do_not_call_provider_or_mutate_ai_state(): void
    {
        $this->postJson('/api/assistant/requests', [])->assertUnauthorized();

        $pt = $this->taoNguoiDungAuth(['PT']);
        $tokenPt = (string) $this->dangNhapApi($pt['user']->thu_dien_tu)->json('data.access_token');
        $this->withHeaders($this->bearer($tokenPt))->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => 'Tạo lịch tập ba buổi.',
        ], ['Idempotency-Key' => $this->uuidAi()])->assertForbidden();

        $bo = $this->taoBoAi(false);
        $headers = array_merge($this->bearer($bo['token']), ['Idempotency-Key' => $this->uuidAi()]);
        $emptyResponse = $this->withHeaders($headers)->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => '',
        ]);
        $this->assertSame(422, $emptyResponse->status(), $emptyResponse->getContent());
        $this->withHeaders(array_merge($this->bearer($bo['token']), ['Idempotency-Key' => $this->uuidAi()]))
            ->postJson('/api/assistant/requests', [
                'request_type' => 'TAO_KE_HOACH',
                'prompt' => 'Hãy kê thuốc và chẩn đoán chấn thương đầu gối.',
            ])->assertStatus(422)->assertJsonPath('code', 'AI_REQUEST_OUT_OF_SCOPE');

        $this->assertSame(0, $this->fake->callCount);
        $this->assertSame(0, DB::table('yeu_cau_tro_ly')->count());
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('loai_su_dung', 'YEU_CAU_TRO_LY')->count());
        $this->assertSame(0, DB::table('de_xuat_ke_hoach_tap')->count());
    }

    public function test_first_valid_ai_request_activates_membership_consumes_one_quota_and_creates_proposal(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 10:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $bo = $this->taoBoAi(true, 3);
        $truoc = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $this->assertSame('CHO_KICH_HOAT', $truoc->trang_thai);
        $this->assertNull($truoc->ngay_bat_dau);

        $response = $this->guiYeuCau($bo, $this->uuidAi())->assertCreated();
        $requestId = (int) $response->json('data.id');
        $proposalId = (int) $response->json('data.proposal.id');

        $ky = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $chuoi = DB::table('dang_ky_goi_tap')->find($ky->dang_ky_goi_tap_id);
        $yeuCau = DB::table('yeu_cau_tro_ly')->find($requestId);
        $this->assertSame('DANG_HOAT_DONG', $ky->trang_thai);
        $this->assertSame(1, (int) $ky->so_luot_tro_ly_da_dung);
        $this->assertSame(0, (int) $ky->so_luot_tro_ly_giu_cho);
        $this->assertSame('DA_TINH', $yeuCau->trang_thai_han_muc);
        $this->assertSame('THANH_CONG', $yeuCau->trang_thai);
        $this->assertSame((int) $yeuCau->su_dung_quyen_loi_id, (int) $chuoi->lan_su_dung_dau_tien_id);
        $this->assertSame('2026-08-29 10:00:00.123456', $ky->ngay_bat_dau);
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->count());
        $this->assertSame(1, DB::table('lan_goi_mo_hinh')->where('yeu_cau_tro_ly_id', $requestId)->count());
        $this->assertSame(0, DB::table('ke_hoach_tap')->count());
        $this->assertSame(0, DB::table('phien_tap')->count());
    }

    public function test_gemini_transport_success_consumes_exactly_one_quota_and_persists_immutable_proposal(): void
    {
        $bo = $this->taoBoAi(true, 2);
        $exerciseId = (int) DB::table('bai_tap')
            ->where('nguoi_tao_id', $bo['fixture']['user']->getKey())
            ->latest('id')
            ->value('id');
        $output = $this->geminiOutput($exerciseId);
        config([
            'ai.provider' => 'gemini',
            'ai.model' => 'gemini-runtime-test',
            'ai.gemini.api_key' => 'dummy-testing-key',
            'ai.gemini.base_url' => 'https://generativelanguage.googleapis.com',
        ]);
        app()->instance(WorkoutAiProvider::class, app(GeminiWorkoutAiProvider::class));
        Http::fake(['*' => Http::response([
            'responseId' => 'runtime-response-id',
            'candidates' => [['content' => ['parts' => [['text' => json_encode($output, JSON_THROW_ON_ERROR)]]]]],
            'usageMetadata' => ['promptTokenCount' => 101, 'candidatesTokenCount' => 41],
        ], 200)]);

        $response = $this->guiYeuCau($bo, $this->uuidAi())->assertCreated();
        $requestId = (int) $response->json('data.id');
        $proposalId = (int) $response->json('data.proposal.id');
        $call = DB::table('lan_goi_mo_hinh')->where('yeu_cau_tro_ly_id', $requestId)->sole();
        $proposal = DB::table('de_xuat_ke_hoach_tap')->find($proposalId);

        $this->assertSame('gemini', $call->nha_cung_cap);
        $this->assertSame('gemini-runtime-test', $call->ten_mo_hinh);
        $this->assertSame('runtime-response-id', $call->ma_yeu_cau_nha_cung_cap);
        $this->assertSame(101, (int) $call->so_don_vi_dau_vao);
        $this->assertSame(41, (int) $call->so_don_vi_dau_ra);
        $this->assertSame(1, (int) DB::table('ky_han_hoi_vien')->where('id', $bo['term']->getKey())->value('so_luot_tro_ly_da_dung'));
        $this->assertSame(0, (int) DB::table('ky_han_hoi_vien')->where('id', $bo['term']->getKey())->value('so_luot_tro_ly_giu_cho'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $call->ma_bam_phan_hoi);
        $this->assertSame($proposal->ma_bam_noi_dung, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung'));
        Http::assertSentCount(1);
    }

    public function test_gemini_rate_limit_refunds_exactly_once_and_idempotent_replay_does_not_call_provider_again(): void
    {
        $bo = $this->taoBoAi(true, 1);
        $key = $this->uuidAi();
        config([
            'ai.provider' => 'gemini',
            'ai.model' => 'gemini-runtime-test',
            'ai.gemini.api_key' => 'dummy-testing-key',
            'ai.gemini.base_url' => 'https://generativelanguage.googleapis.com',
        ]);
        app()->instance(WorkoutAiProvider::class, app(GeminiWorkoutAiProvider::class));
        Http::fake(['*' => Http::response(['error' => ['message' => 'provider detail must stay private']], 429)]);

        $this->guiYeuCau($bo, $key)->assertStatus(503)->assertJsonPath('code', 'AI_PROVIDER_RATE_LIMITED');
        $this->guiYeuCau($bo, $key)->assertStatus(503)->assertJsonPath('code', 'AI_PROVIDER_RATE_LIMITED');

        $term = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $request = DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $bo['member_id'])->sole();
        $this->assertSame('DANG_HOAT_DONG', $term->trang_thai);
        $this->assertSame(0, (int) $term->so_luot_tro_ly_da_dung);
        $this->assertSame(0, (int) $term->so_luot_tro_ly_giu_cho);
        $this->assertSame('DA_TRA', $request->trang_thai_han_muc);
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->where('loai_su_dung', 'YEU_CAU_TRO_LY')->count());
        $this->assertSame(0, DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());
        Http::assertSentCount(1);
    }

    public function test_provider_failure_modes_refund_quota_keep_activation_and_create_no_proposal(): void
    {
        foreach ([
            FakeWorkoutAiProvider::TIMEOUT => 'AI_PROVIDER_TIMEOUT',
            FakeWorkoutAiProvider::RATE_LIMIT => 'AI_PROVIDER_RATE_LIMITED',
            FakeWorkoutAiProvider::SERVER_ERROR => 'AI_PROVIDER_UNAVAILABLE',
            FakeWorkoutAiProvider::MALFORMED_OUTPUT => 'AI_MALFORMED_OUTPUT',
            FakeWorkoutAiProvider::UNKNOWN_EXERCISE => 'AI_UNKNOWN_EXERCISE_ID',
            FakeWorkoutAiProvider::INVALID_STRUCTURE => 'AI_INVALID_STRUCTURE',
        ] as $mode => $code) {
            $bo = $this->taoBoAi(true, 1);
            $fake = new FakeWorkoutAiProvider($mode);
            app()->instance(WorkoutAiProvider::class, $fake);
            $response = $this->guiYeuCau($bo, $this->uuidAi());
            $response->assertStatus(in_array($mode, [FakeWorkoutAiProvider::TIMEOUT, FakeWorkoutAiProvider::RATE_LIMIT, FakeWorkoutAiProvider::SERVER_ERROR], true) ? 503 : 502)
                ->assertJsonPath('code', $code);

            $ky = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
            $chuoi = DB::table('dang_ky_goi_tap')->find($ky->dang_ky_goi_tap_id);
            $yeuCau = DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $bo['member_id'])->latest('id')->first();
            $this->assertSame('DANG_HOAT_DONG', $ky->trang_thai, $mode);
            $this->assertNotNull($ky->ngay_bat_dau, $mode);
            $this->assertNotNull($chuoi->lan_su_dung_dau_tien_id, $mode);
            $this->assertSame(0, (int) $ky->so_luot_tro_ly_da_dung, $mode);
            $this->assertSame(0, (int) $ky->so_luot_tro_ly_giu_cho, $mode);
            $this->assertSame('DA_TRA', $yeuCau->trang_thai_han_muc, $mode);
            $this->assertSame(0, DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count(), $mode);
        }
    }

    public function test_active_member_provider_failure_refunds_without_changing_membership_dates(): void
    {
        $bo = $this->taoBoAi(true, 2);
        $this->kichHoatMembershipAi($bo['term'], (int) $bo['fixture']['user']->getKey());
        $truoc = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        app()->instance(WorkoutAiProvider::class, new FakeWorkoutAiProvider(FakeWorkoutAiProvider::TIMEOUT));

        $this->guiYeuCau($bo, $this->uuidAi())->assertStatus(503);
        $sau = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $this->assertSame($truoc->ngay_bat_dau, $sau->ngay_bat_dau);
        $this->assertSame($truoc->ngay_ket_thuc, $sau->ngay_ket_thuc);
        $this->assertSame(0, (int) $sau->so_luot_tro_ly_da_dung);
        $this->assertSame(0, (int) $sau->so_luot_tro_ly_giu_cho);
    }

    public function test_entitlement_denies_no_membership_disabled_ai_and_exhausted_quota(): void
    {
        $khongGoi = $this->taoBoAi(false);
        $this->guiYeuCau($khongGoi, $this->uuidAi())->assertStatus(403)->assertJsonPath('code', 'AI_ENTITLEMENT_DENIED');

        $tatAi = $this->taoBoAi(false);
        $tatAi['term'] = $this->taoMembershipAi($tatAi['fixture'], $tatAi['member_id'], 0, false);
        $this->guiYeuCau($tatAi, $this->uuidAi())->assertStatus(429)->assertJsonPath('code', 'AI_QUOTA_EXHAUSTED');

        $het = $this->taoBoAi(true, 1);
        DB::table('ky_han_hoi_vien')->where('id', $het['term']->getKey())->update(['so_luot_tro_ly_da_dung' => 1]);
        $this->guiYeuCau($het, $this->uuidAi())->assertStatus(429)->assertJsonPath('code', 'AI_QUOTA_EXHAUSTED');
    }

    public function test_entitlement_denies_unpaid_cancelled_expired_and_future_ai_term(): void
    {
        $unpaid = $this->taoBoAi(false);
        $goiUnpaid = $this->taoGoiTapMembership($unpaid['fixture']);
        $this->taoDonVaSnapshotMembership($unpaid['member_id'], $goiUnpaid['package']);
        $this->guiYeuCau($unpaid, $this->uuidAi())->assertStatus(403)->assertJsonPath('code', 'AI_ENTITLEMENT_DENIED');

        $cancelled = $this->taoBoAi(true, 2);
        DB::table('ky_han_hoi_vien')->where('id', $cancelled['term']->getKey())->update(['trang_thai' => 'HUY']);
        DB::table('dang_ky_goi_tap')->where('id', $cancelled['term']->dang_ky_goi_tap_id)->update(['trang_thai' => 'HUY']);
        $this->guiYeuCau($cancelled, $this->uuidAi())->assertStatus(403)->assertJsonPath('code', 'AI_ENTITLEMENT_DENIED');

        $expired = $this->taoBoAi(true, 2);
        $old = CarbonImmutable::parse('2026-01-01 00:00:00.000001', 'UTC');
        $this->kichHoatMembershipAi($expired['term'], (int) $expired['fixture']['user']->getKey(), $old);
        CarbonImmutable::setTestNow($old->addDays(31));
        $this->guiYeuCau($expired, $this->uuidAi())->assertStatus(403)->assertJsonPath('code', 'AI_ENTITLEMENT_DENIED');
        CarbonImmutable::setTestNow();

        $future = $this->taoBoAi(false);
        $current = $this->taoMembershipAi($future['fixture'], $future['member_id'], 0, false);
        $next = $this->taoMembershipAi($future['fixture'], $future['member_id'], 2, true);
        $this->kichHoatMembershipAi($current, (int) $future['fixture']['user']->getKey());
        $this->assertSame('CHO_DEN_LUOT', DB::table('ky_han_hoi_vien')->where('id', $next->getKey())->value('trang_thai'));
        $this->guiYeuCau($future, $this->uuidAi())->assertStatus(429)->assertJsonPath('code', 'AI_QUOTA_EXHAUSTED');
        $this->assertSame(0, (int) DB::table('ky_han_hoi_vien')->where('id', $next->getKey())->value('so_luot_tro_ly_da_dung'));
    }

    public function test_two_distinct_successful_requests_consume_two_units_without_future_merge(): void
    {
        $bo = $this->taoBoAi(true, 3);
        $this->guiYeuCau($bo, $this->uuidAi())->assertCreated();
        $this->guiYeuCau($bo, $this->uuidAi())->assertCreated();
        $ky = DB::table('ky_han_hoi_vien')->find($bo['term']->getKey());
        $this->assertSame(2, (int) $ky->so_luot_tro_ly_da_dung);
        $this->assertSame(0, (int) $ky->so_luot_tro_ly_giu_cho);
        $this->assertSame(2, DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(2, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $bo['member_id'])->where('loai_su_dung', 'YEU_CAU_TRO_LY')->count());
    }

    public function test_no_candidate_and_client_authority_are_rejected_before_quota(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $equipment = $this->taoDungCuAi();
        $this->taoBaiTapAi($fixture, [$equipment]);
        $term = $this->taoMembershipAi($fixture, $memberId, 2);
        $token = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
        $headers = array_merge($this->bearer($token), ['Idempotency-Key' => $this->uuidAi()]);

        $this->withHeaders($headers)->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => 'Tạo lịch tập.',
            'hoi_vien_id' => $memberId,
            'candidate_ids' => [1],
        ])->assertUnprocessable();
        $this->withHeaders(array_merge($this->bearer($token), ['Idempotency-Key' => $this->uuidAi()]))
            ->postJson('/api/assistant/requests', [
                'request_type' => 'TAO_KE_HOACH',
                'prompt' => 'Tạo lịch tập.',
            ])->assertStatus(422)->assertJsonPath('code', 'AI_NO_CANDIDATES');
        $this->assertSame(0, $this->fake->callCount);
        $this->assertSame(0, (int) DB::table('ky_han_hoi_vien')->where('id', $term->getKey())->value('so_luot_tro_ly_giu_cho'));
        $this->assertSame(0, DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $memberId)->count());
    }

    public function test_same_idempotency_key_replays_one_request_and_different_payload_conflicts(): void
    {
        $bo = $this->taoBoAi(true, 3);
        $key = $this->uuidAi();
        $dau = $this->guiYeuCau($bo, $key)->assertCreated();
        $sau = $this->guiYeuCau($bo, $key)->assertCreated();
        $this->assertSame($dau->json('data.id'), $sau->json('data.id'));
        $this->assertSame($dau->json('data.proposal.id'), $sau->json('data.proposal.id'));
        $this->assertSame(1, $this->fake->callCount);
        $this->assertSame(1, DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $bo['member_id'])->count());
        $this->assertSame(1, (int) DB::table('ky_han_hoi_vien')->where('id', $bo['term']->getKey())->value('so_luot_tro_ly_da_dung'));

        $this->withHeaders(array_merge($this->bearer($bo['token']), ['Idempotency-Key' => $key]))
            ->postJson('/api/assistant/requests', [
                'request_type' => 'TAO_KE_HOACH',
                'prompt' => 'Một yêu cầu khác hoàn toàn.',
            ])->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    }

    public function test_failed_request_replay_returns_same_failure_without_second_provider_call(): void
    {
        $bo = $this->taoBoAi(true, 1);
        $fake = new FakeWorkoutAiProvider(FakeWorkoutAiProvider::TIMEOUT);
        app()->instance(WorkoutAiProvider::class, $fake);
        $key = $this->uuidAi();
        $this->guiYeuCau($bo, $key)->assertStatus(503)->assertJsonPath('code', 'AI_PROVIDER_TIMEOUT');
        $this->guiYeuCau($bo, $key)->assertStatus(503)->assertJsonPath('code', 'AI_PROVIDER_TIMEOUT');
        $this->assertSame(1, $fake->callCount);
        $this->assertSame(1, DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $bo['member_id'])->count());
    }

    public function test_success_and_refund_finalizers_are_idempotent(): void
    {
        $bo = $this->taoBoAi(true, 3);
        $success = $this->guiYeuCau($bo, $this->uuidAi())->assertCreated();
        $requestId = (int) $success->json('data.id');
        $callId = (int) DB::table('lan_goi_mo_hinh')->where('yeu_cau_tro_ly_id', $requestId)->value('id');
        $request = DB::table('yeu_cau_tro_ly')->find($requestId);
        $context = json_decode($request->ngu_canh_da_chot, true, 512, JSON_THROW_ON_ERROR);
        $output = json_decode(DB::table('lan_goi_mo_hinh')->find($callId)->ket_qua_cau_truc, true, 512, JSON_THROW_ON_ERROR);
        app(AiRequestService::class)->hoanTatThanhCong(
            $requestId,
            $callId,
            new WorkoutAiResult($output, 'late-retry'),
            $output,
        );
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->where('yeu_cau_tro_ly_id', $requestId)->count());
        $this->assertSame(1, (int) DB::table('ky_han_hoi_vien')->where('id', $bo['term']->getKey())->value('so_luot_tro_ly_da_dung'));

        $loi = $this->taoBoAi(true, 1);
        app()->instance(WorkoutAiProvider::class, new FakeWorkoutAiProvider(FakeWorkoutAiProvider::TIMEOUT));
        $this->guiYeuCau($loi, $this->uuidAi())->assertStatus(503);
        $requestLoi = DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $loi['member_id'])->sole();
        $callLoi = DB::table('lan_goi_mo_hinh')->where('yeu_cau_tro_ly_id', $requestLoi->id)->sole();
        app(AiRequestService::class)->hoanTraSauLoi((int) $requestLoi->id, (int) $callLoi->id, AiProviderException::timeout());
        $ky = DB::table('ky_han_hoi_vien')->find($loi['term']->getKey());
        $this->assertSame(0, (int) $ky->so_luot_tro_ly_da_dung);
        $this->assertSame(0, (int) $ky->so_luot_tro_ly_giu_cho);
    }

    public function test_stale_profile_and_equipment_refund_and_publish_no_proposal(): void
    {
        $bo = $this->taoBoAi(true, 2);
        $this->fake->beforeReturn(function () use ($bo): void {
            DB::table('ho_so_hoi_vien')->where('id', $bo['member_id'])->increment('phien_ban_ho_so');
        });
        $this->guiYeuCau($bo, $this->uuidAi())->assertStatus(409)->assertJsonPath('code', 'AI_CONTEXT_STALE');
        $this->assertSame(0, DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $bo['member_id'])->count());

        $bo2 = $this->taoBoAi(true, 2);
        $dungCu = $this->taoDungCuAi();
        $this->fake = new FakeWorkoutAiProvider;
        $this->fake->beforeReturn(function () use ($bo2, $dungCu): void {
            $this->ganDungCuAi($bo2['member_id'], $dungCu);
        });
        app()->instance(WorkoutAiProvider::class, $this->fake);
        $this->guiYeuCau($bo2, $this->uuidAi())->assertStatus(409)->assertJsonPath('code', 'AI_EQUIPMENT_STALE');
        $this->assertSame(0, (int) DB::table('ky_han_hoi_vien')->where('id', $bo2['term']->getKey())->value('so_luot_tro_ly_giu_cho'));
    }

    public function test_proposal_ttl_is_concrete_content_is_read_only_and_idor_is_concealed(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 12:00:00.654321', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $a = $this->taoBoAi(true, 2);
        $result = $this->guiYeuCau($a, $this->uuidAi())->assertCreated();
        $requestId = (int) $result->json('data.id');
        $proposalId = (int) $result->json('data.proposal.id');
        $this->assertSame($moc->addHours(24)->toISOString(), $result->json('data.proposal.expires_at'));
        $hash = (string) DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung');
        $this->withHeaders($this->bearer($a['token']))->patchJson('/api/assistant/proposals/'.$proposalId, [
            'content' => ['tamper' => true],
        ])->assertStatus(405);
        $this->assertSame($hash, DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('ma_bam_noi_dung'));

        CarbonImmutable::setTestNow($moc->addHours(24));
        $this->withHeaders($this->bearer($a['token']))->getJson('/api/assistant/proposals/'.$proposalId)
            ->assertOk()->assertJsonPath('data.is_expired', true);

        $b = $this->taoBoAi(false);
        $this->withHeaders($this->bearer($b['token']))->getJson('/api/assistant/requests/'.$requestId)->assertNotFound();
        $this->withHeaders($this->bearer($b['token']))->getJson('/api/assistant/proposals/'.$proposalId)->assertNotFound();
    }

    public function test_provider_context_is_minimized_and_contains_only_backend_candidates(): void
    {
        $bo = $this->taoBoAi(true, 2);
        $this->guiYeuCau($bo, $this->uuidAi())->assertCreated();
        $json = json_encode($this->fake->lastContext, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        foreach (['thu_dien_tu', 'so_dien_thoai', 'mat_khau_bam', 'access_token', 'lan_thanh_toan', 'su_kien_thanh_toan', 'ma_tai_khoan'] as $cam) {
            $this->assertStringNotContainsString($cam, $json);
        }
        $this->assertTrue($this->fake->lastContext['policy']['candidate_ids_only']);
        $this->assertSame(0, DB::table('ke_hoach_tap')->count());
        $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')->count());
        $this->assertSame(0, DB::table('buoi_tap_du_kien')->count());
        $this->assertSame(0, DB::table('phien_tap')->count());
    }

    /** @return array<string, mixed> */
    private function taoBoAi(bool $membership, int $limit = 3): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $this->taoBaiTapAi($fixture);
        $term = $membership ? $this->taoMembershipAi($fixture, $memberId, $limit) : null;
        $token = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');

        return compact('fixture', 'memberId', 'token', 'term') + ['member_id' => $memberId];
    }

    private function guiYeuCau(array $bo, string $key)
    {
        return $this->withHeaders(array_merge($this->bearer($bo['token']), ['Idempotency-Key' => $key]))
            ->postJson('/api/assistant/requests', [
                'request_type' => 'TAO_KE_HOACH',
                'prompt' => 'Tạo cho tôi kế hoạch tập ba buổi mỗi tuần.',
            ]);
    }

    /** @return array<string, mixed> */
    private function geminiOutput(int $exerciseId): array
    {
        return [
            'loai_thay_doi' => 'TAO_MOI',
            'tieu_de' => 'Kế hoạch Gemini đã kiểm tra',
            'giai_thich' => 'Chỉ sử dụng candidate đã được Backend cấp.',
            'ap_dung_tu_ngay' => CarbonImmutable::now('UTC')->addDay()->format('Y-m-d'),
            'ngay_trong_ke_hoach' => array_map(fn (int $day): array => [
                'thu_trong_tuan' => $day,
                'bai_tap_trong_ke_hoach' => [[
                    'bai_tap_id' => $exerciseId,
                    'thu_tu' => 1,
                    'so_hiep_muc_tieu' => 3,
                    'so_lan_lap_toi_thieu' => 8,
                    'so_lan_lap_toi_da' => 12,
                    'thoi_gian_nghi_giay' => 90,
                ]],
            ], [2, 4, 6]),
        ];
    }
}
