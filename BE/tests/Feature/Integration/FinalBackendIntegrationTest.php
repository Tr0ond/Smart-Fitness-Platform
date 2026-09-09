<?php

namespace Tests\Feature\Integration;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Events\PtChatMessageSent;
use App\Models\KyHanHoiVien;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPaymentFixtures;
use Tests\Concerns\CreatesPtChatFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\Fakes\FakeWorkoutAiProvider;
use Tests\TestCase;

class FinalBackendIntegrationTest extends TestCase
{
    use CreatesPaymentFixtures;
    use CreatesPtChatFixtures;
    use CreatesPtFixtures;
    use CreatesWorkoutFixtures;

    private FakeWorkoutAiProvider $aiProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-30 02:00:00.123456', 'UTC'));
        $this->cauHinhPaymentTest();
        config(['ai.idempotency_ttl_hours' => 24, 'ai.proposal_ttl_hours' => 24]);
        $this->aiProvider = new FakeWorkoutAiProvider;
        app()->instance(WorkoutAiProvider::class, $this->aiProvider);
        Event::fake([PtChatMessageSent::class]);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_payment_ai_apply_and_workout_share_one_membership_clock_without_second_charge(): void
    {
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($member);
        $this->taoBaiTapAi($member);
        $package = $this->taoGoiTapMembership($member, [], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => true,
            'gioi_han_luot_tro_ly' => 5,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 2,
        ]);
        $order = $this->taoDonPaymentQuaApi($member, $package['package']->getKey());
        $this->dungGatewayPayOSXacMinhChuKy();

        $this->postJson('/api/webhooks/payos', $this->webhookPayment($order['payment']))
            ->assertOk()
            ->assertJsonPath('data.result', 'DA_XAC_NHAN');

        $term = KyHanHoiVien::query()->where('don_mua_goi_id', $order['order']->getKey())->sole();
        $this->assertSame($memberId, (int) $term->hoi_vien_id);
        $this->assertSame('CHO_KICH_HOAT', $term->trang_thai);
        $this->assertNull($term->ngay_bat_dau);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count());

        $token = $this->layTokenProfile($member);
        $request = $this->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => 'Tạo kế hoạch tích hợp cuối cùng từ candidate Backend.',
        ], $this->headers($token))->assertCreated();
        $proposalId = (int) $request->json('data.proposal.id');
        $callsAfterRequest = $this->aiProvider->callCount;
        $termAfterAi = $term->fresh();
        $usageAfterAi = DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count();

        $this->assertSame('DANG_HOAT_DONG', $termAfterAi->trang_thai);
        $this->assertNotNull($termAfterAi->ngay_bat_dau);
        $this->assertSame(1, (int) $termAfterAi->so_luot_tro_ly_da_dung);
        $this->assertSame(1, $usageAfterAi);

        $applied = $this->postJson(
            "/api/assistant/proposals/{$proposalId}/apply",
            [],
            $this->headers($token),
        )->assertOk()->assertJsonPath('data.proposal.status', 'DA_AP_DUNG');
        $versionId = (int) $applied->json('data.plan.current_version.id');

        $this->assertSame($callsAfterRequest, $this->aiProvider->callCount);
        $this->assertSame($usageAfterAi, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count());
        $this->assertMembershipCountersUnchanged($termAfterAi, $term->fresh());

        $schedule = DB::table('buoi_tap_du_kien')
            ->where('hoi_vien_id', $memberId)
            ->where('phien_ban_ke_hoach_tap_id', $versionId)
            ->where('trang_thai', 'CHUA_TAP')
            ->orderBy('ngay_tap')
            ->first();
        $this->assertNotNull($schedule);
        CarbonImmutable::setTestNow(CarbonImmutable::parse($schedule->ngay_tap.' 02:00:00.123456', 'UTC'));

        $started = $this->postJson(
            '/api/workout/scheduled-sessions/'.$schedule->id.'/start',
            [],
            $this->headers($token),
        )->assertCreated();
        $sessionId = (int) $started->json('data.id');
        $sessionExerciseId = (int) DB::table('bai_tap_trong_phien')
            ->where('phien_tap_id', $sessionId)
            ->value('id');
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/exercises/{$sessionExerciseId}/sets",
            ['order' => 1, 'reps' => 10, 'weight_kg' => 20, 'actual_rest_seconds' => 60],
            $this->headers($token),
        )->assertCreated();
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            ['notes' => 'Final integration'],
            $this->headers($token),
        )->assertOk();

        $this->assertDatabaseHas('phien_tap', ['id' => $sessionId, 'trang_thai' => 'HOAN_THANH']);
        $this->assertSame(1, DB::table('hiep_tap')
            ->whereIn('bai_tap_trong_phien_id', DB::table('bai_tap_trong_phien')->where('phien_tap_id', $sessionId)->pluck('id'))
            ->count());
        $this->assertSame($usageAfterAi, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count());
        $this->assertMembershipCountersUnchanged($termAfterAi, $term->fresh());
    }

    public function test_payment_chat_direct_service_and_reassignment_preserve_rights_and_history(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->dongBoChiNhanhCacActorTrongScenario($fixture);
        $package = $this->taoGoiTapMembership($fixture['admin'], [], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => true,
            'gioi_han_luot_tro_ly' => 2,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 2,
        ]);
        $order = $this->taoDonPaymentQuaApi($fixture['member_a'], $package['package']->getKey());
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($order['payment']))->assertOk();
        $term = KyHanHoiVien::query()->where('don_mua_goi_id', $order['order']->getKey())->sole();

        $conversationId = (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            [],
            $this->bearer($fixture['member_a_token']),
        )->assertOk()->json('data.id');
        $this->getJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->bearer($fixture['member_a_token']),
        )->assertOk();
        $this->postJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->message('PT có thể trả lời trước'),
            $this->bearer($fixture['pt_a_token']),
        )->assertCreated();
        $this->assertSame('CHO_KICH_HOAT', $term->fresh()->trang_thai);

        $this->postJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->message('Member sử dụng Chat lần đầu'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        $afterChat = $term->fresh();
        $this->assertSame('DANG_HOAT_DONG', $afterChat->trang_thai);
        $this->assertSame(1, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->where('loai_su_dung', 'TRO_CHUYEN_HUAN_LUYEN')
            ->count());
        $this->assertSame(0, (int) $afterChat->so_buoi_huan_luyen_vien_da_dung);

        $direct = $this->postJson('/api/pt/direct-sessions/complete', [
            'assignment_id' => $fixture['assignment_a_id'],
            'notes' => 'Buổi trực tiếp sau Chat',
        ], $this->headers($fixture['pt_a_token']))->assertCreated();
        $historyId = (int) $direct->json('data.history_id');
        $afterDirect = $term->fresh();
        $this->assertSame(1, (int) $afterDirect->so_buoi_huan_luyen_vien_da_dung);
        $this->assertSame(0, (int) $afterDirect->so_luot_tro_ly_da_dung);
        $this->assertSame($afterChat->ngay_bat_dau?->format('Y-m-d H:i:s.u'), $afterDirect->ngay_bat_dau?->format('Y-m-d H:i:s.u'));

        $workout = $fixture['member_a'] + [
            'member_id' => $fixture['member_a_id'],
            'token' => $fixture['member_a_token'],
        ];
        $exerciseId = $this->taoBaiTapWorkout($fixture['member_a']);
        $plan = $this->taoPlanWorkout($workout, $exerciseId);
        $schedule = $this->taoLichWorkout($workout, $plan);
        $sessionId = (int) $this->postJson(
            '/api/workout/scheduled-sessions/'.$schedule->getKey().'/start',
            [],
            $this->headers($fixture['member_a_token']),
        )->assertCreated()->json('data.id');
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            [],
            $this->headers($fixture['member_a_token']),
        )->assertOk();
        $this->assertMembershipCountersUnchanged($afterDirect, $term->fresh());

        $newAssignment = $this->postJson(
            '/api/pt/assignments/'.$fixture['assignment_a_id'].'/reassign',
            [
                'trainer_id' => $fixture['pt_b_id'],
                'start_at' => CarbonImmutable::now('UTC')->toISOString(),
                'reason' => 'Final integration reassignment',
            ],
            $this->bearer($fixture['admin_token']),
        )->assertCreated();
        $newConversationId = (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            ['assignment_id' => (int) $newAssignment->json('data.id')],
            $this->bearer($fixture['pt_b_token']),
        )->assertOk()->json('data.id');

        $this->assertNotSame($conversationId, $newConversationId);
        $this->getJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->bearer($fixture['pt_a_token']),
        )->assertNotFound();
        $this->postJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->message('Không được sửa lịch sử'),
            $this->bearer($fixture['pt_a_token']),
        )->assertNotFound()->assertJsonPath('code', 'CHAT_CONVERSATION_NOT_FOUND');
        $this->getJson(
            "/api/pt/chat/conversations/{$conversationId}",
            $this->bearer($fixture['pt_b_token']),
        )->assertNotFound();

        $newHistoryId = (int) $this->postJson('/api/pt/direct-sessions/complete', [
            'assignment_id' => (int) $newAssignment->json('data.id'),
            'notes' => 'PT B chỉ dùng assignment mới',
        ], $this->headers($fixture['pt_b_token']))->assertCreated()->json('data.history_id');
        $this->assertDatabaseHas('lich_su_su_dung_huan_luyen_vien', [
            'id' => $historyId,
            'phan_cong_huan_luyen_vien_id' => $fixture['assignment_a_id'],
            'huan_luyen_vien_id' => $fixture['pt_a_id'],
        ]);
        $this->assertDatabaseHas('lich_su_su_dung_huan_luyen_vien', [
            'id' => $newHistoryId,
            'phan_cong_huan_luyen_vien_id' => (int) $newAssignment->json('data.id'),
            'huan_luyen_vien_id' => $fixture['pt_b_id'],
        ]);
        $this->assertSame(2, DB::table('tin_nhan')->where('hoi_thoai_id', $conversationId)->count());
        $this->assertSame(2, (int) $term->fresh()->so_buoi_huan_luyen_vien_da_dung);
    }

    public function test_chat_direct_ai_and_workout_use_independent_ledgers_without_future_borrowing(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        DB::table('ho_so_hoi_vien')->where('id', $fixture['member_a_id'])->update([
            'muc_tieu_tap_luyen' => 'TANG_SUC_MANH',
            'kinh_nghiem_tap_luyen' => 'TRUNG_CAP',
            'so_ngay_tap_mong_muon' => 3,
            'thoi_luong_moi_buoi_phut' => 60,
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);
        foreach ([2, 4, 6] as $weekday) {
            DB::table('ngay_ranh_hoi_vien')->insert([
                'hoi_vien_id' => $fixture['member_a_id'],
                'thu_trong_tuan' => $weekday,
                'ngay_tao' => CarbonImmutable::now('UTC'),
                'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
            ]);
        }
        $exerciseId = $this->taoBaiTapAi($fixture['member_a']);
        $package = $this->taoGoiTapMembership($fixture['admin'], [], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => true,
            'gioi_han_luot_tro_ly' => 1,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 1,
        ]);
        $term = $this->taoDonVaSnapshotMembership($fixture['member_a_id'], $package['package']);
        $this->xacNhanVaCapMembership($term);

        $conversationId = (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            [],
            $this->bearer($fixture['member_a_token']),
        )->assertOk()->json('data.id');
        $this->postJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->message('Chat kích hoạt trước'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        $this->postJson('/api/pt/direct-sessions/complete', [
            'assignment_id' => $fixture['assignment_a_id'],
        ], $this->headers($fixture['pt_a_token']))->assertCreated();
        $this->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => 'Dùng đúng quota AI độc lập trong integration scenario C.',
        ], $this->headers($fixture['member_a_token']))->assertCreated();

        $afterBenefits = $term->fresh();
        $this->assertSame('DANG_HOAT_DONG', $afterBenefits->trang_thai);
        $this->assertSame(1, (int) $afterBenefits->so_buoi_huan_luyen_vien_da_dung);
        $this->assertSame(1, (int) $afterBenefits->so_luot_tro_ly_da_dung);
        foreach (['TRO_CHUYEN_HUAN_LUYEN', 'BUOI_HUAN_LUYEN', 'YEU_CAU_TRO_LY'] as $usageType) {
            $this->assertSame(1, DB::table('su_dung_quyen_loi')
                ->where('hoi_vien_id', $fixture['member_a_id'])
                ->where('loai_su_dung', $usageType)
                ->count());
        }

        $workout = $fixture['member_a'] + [
            'member_id' => $fixture['member_a_id'],
            'token' => $fixture['member_a_token'],
        ];
        $plan = $this->taoPlanWorkout($workout, $exerciseId);
        $schedule = $this->taoLichWorkout($workout, $plan);
        $sessionId = (int) $this->postJson(
            '/api/workout/scheduled-sessions/'.$schedule->getKey().'/start',
            [],
            $this->headers($fixture['member_a_token']),
        )->assertCreated()->json('data.id');
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            [],
            $this->headers($fixture['member_a_token']),
        )->assertOk();
        $this->assertMembershipCountersUnchanged($afterBenefits, $term->fresh());

        $futurePackage = $this->taoGoiTapMembership($fixture['admin'], [], [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => true,
            'gioi_han_luot_tro_ly' => 5,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $futureTerm = $this->taoDonVaSnapshotMembership($fixture['member_a_id'], $futurePackage['package']);
        $this->xacNhanVaCapMembership($futureTerm);
        $this->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => 'Không được mượn quota của kỳ kế tiếp.',
        ], $this->headers($fixture['member_a_token']))
            ->assertStatus(429)
            ->assertJsonPath('code', 'AI_QUOTA_EXHAUSTED');
        $this->assertSame('CHO_DEN_LUOT', $futureTerm->fresh()->trang_thai);
        $this->assertSame(0, (int) $futureTerm->fresh()->so_luot_tro_ly_da_dung);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('ky_han_hoi_vien_id', $futureTerm->getKey())->count());
    }

    public function test_workout_can_run_without_membership_and_cannot_mutate_foreign_history(): void
    {
        $owner = $this->taoHoiVienWorkout();
        $foreign = $this->taoHoiVienWorkout();
        $exerciseId = $this->taoBaiTapWorkout($owner);
        $plan = $this->taoPlanWorkout($owner, $exerciseId);
        $schedule = $this->taoLichWorkout($owner, $plan);

        $started = $this->postJson(
            '/api/workout/scheduled-sessions/'.$schedule->getKey().'/start',
            [],
            $this->headers($owner['token']),
        )->assertCreated();
        $sessionId = (int) $started->json('data.id');
        $exerciseInSessionId = (int) DB::table('bai_tap_trong_phien')
            ->where('phien_tap_id', $sessionId)
            ->value('id');

        $this->postJson(
            "/api/workout/sessions/{$sessionId}/exercises/{$exerciseInSessionId}/sets",
            ['order' => 1, 'reps' => 8, 'member_id' => $foreign['member_id']],
            $this->headers($owner['token']),
        )->assertUnprocessable();
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            [],
            $this->headers($foreign['token']),
        )->assertNotFound();
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            [],
            $this->headers($owner['token']),
        )->assertOk();

        $this->assertSame(0, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $owner['member_id'])->count());
        $this->assertSame(0, DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $owner['member_id'])->count());
        $this->assertDatabaseHas('phien_tap', ['id' => $sessionId, 'trang_thai' => 'HOAN_THANH']);
    }

    /**
     * Dong bo chi nhanh cua cac actor trong scenario de kiem chung reassign cung pham vi.
     *
     * Dau vao: fixture PT Chat co Admin, Member A/B va PT A/B cung branch_id.
     * Cach hoat dong: gan chi_nhanh_id cua bon tai khoan ve chi nhanh Admin fixture.
     * Ket qua: Member A co the duoc phan cong lai cho PT B trong cung chi nhanh.
     * Side effect: chi cap nhat cac dong nguoi_dung cua fixture nay trong giao dich test.
     * Business Rule: assignment chi cho phep hai actor thuoc cung chi nhanh cua Admin.
     */
    private function dongBoChiNhanhCacActorTrongScenario(array $fixture): void
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

    /** @return array<string, string> */
    private function headers(string $token): array
    {
        return array_merge($this->bearer($token), ['Idempotency-Key' => (string) Str::uuid()]);
    }

    /** @return array<string, string> */
    private function message(string $content): array
    {
        return ['client_message_id' => (string) Str::uuid(), 'content' => $content];
    }

    private function assertMembershipCountersUnchanged(KyHanHoiVien $before, KyHanHoiVien $after): void
    {
        $this->assertSame($before->ngay_bat_dau?->format('Y-m-d H:i:s.u'), $after->ngay_bat_dau?->format('Y-m-d H:i:s.u'));
        $this->assertSame($before->ngay_ket_thuc?->format('Y-m-d H:i:s.u'), $after->ngay_ket_thuc?->format('Y-m-d H:i:s.u'));
        $this->assertSame((int) $before->so_luot_tro_ly_da_dung, (int) $after->so_luot_tro_ly_da_dung);
        $this->assertSame((int) $before->so_luot_tro_ly_dang_giu, (int) $after->so_luot_tro_ly_dang_giu);
        $this->assertSame((int) $before->so_buoi_huan_luyen_vien_da_dung, (int) $after->so_buoi_huan_luyen_vien_da_dung);
    }
}
