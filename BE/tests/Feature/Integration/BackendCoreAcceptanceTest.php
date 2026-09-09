<?php

namespace Tests\Feature\Integration;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Events\PtChatMessageSent;
use App\Gateways\GeminiWorkoutAiProvider;
use App\Models\DonMuaGoi;
use App\Models\KyHanHoiVien;
use App\Models\LanThanhToan;
use App\Models\NguoiDung;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPaymentFixtures;
use Tests\Concerns\CreatesPtChatFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\TestCase;

class BackendCoreAcceptanceTest extends TestCase
{
    use CreatesPaymentFixtures;
    use CreatesPtChatFixtures;
    use CreatesPtFixtures;
    use CreatesWorkoutFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-30 02:00:00.123456', 'UTC'));
        $this->cauHinhPaymentTest();
        Event::fake([PtChatMessageSent::class]);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_register_login_package_payment_gemini_apply_workout_and_progress_end_to_end(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $email = 'acceptance.'.bin2hex(random_bytes(5)).'@example.com';
        $password = self::MAT_KHAU_HOP_LE;
        $registered = $this->postJson('/api/auth/register', [
            'name' => 'Hội viên acceptance',
            'email' => $email,
            'phone' => '0901234567',
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertCreated()->assertJsonPath('data.roles', ['MEMBER']);

        $member = NguoiDung::query()->findOrFail((int) $registered->json('data.id'));
        $member->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $memberId = (int) DB::table('ho_so_hoi_vien')->where('nguoi_dung_id', $member->getKey())->value('id');
        DB::table('ho_so_hoi_vien')->where('id', $memberId)->update([
            'muc_tieu_tap_luyen' => 'TANG_SUC_MANH',
            'kinh_nghiem_tap_luyen' => 'MOI_BAT_DAU',
            'so_ngay_tap_mong_muon' => 1,
            'thoi_luong_moi_buoi_phut' => 60,
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);
        DB::table('ngay_ranh_hoi_vien')->insert([
            'hoi_vien_id' => $memberId,
            'thu_trong_tuan' => 2,
            'ngay_tao' => CarbonImmutable::now('UTC'),
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password,
            'device_name' => 'Backend acceptance',
        ])->assertOk()->assertJsonPath('data.user.roles', ['MEMBER']);
        $token = (string) $login->json('data.access_token');
        $headers = $this->headers($token);

        $exerciseId = $this->taoBaiTapAi($admin);
        $package = $this->taoGoiTapMembership($admin, ['ten_goi' => 'Gói Gemini Acceptance'], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => true,
            'gioi_han_luot_tro_ly' => 3,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $this->getJson('/api/packages', $this->bearer($token))
            ->assertOk()->assertJsonFragment(['name' => 'Gói Gemini Acceptance']);
        $this->getJson('/api/packages/'.$package['package']->getKey(), $this->bearer($token))
            ->assertOk()->assertJsonPath('data.benefits.fitness_assistant', true);

        $orderResponse = $this->postJson(
            '/api/packages/'.$package['package']->getKey().'/orders',
            [],
            $headers,
        )->assertCreated();
        $order = DonMuaGoi::query()->findOrFail((int) $orderResponse->json('data.id'));
        $payment = LanThanhToan::query()->where('don_mua_goi_id', $order->getKey())->sole();
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($payment))
            ->assertOk()->assertJsonPath('data.result', 'DA_XAC_NHAN');

        $term = KyHanHoiVien::query()->where('don_mua_goi_id', $order->getKey())->sole();
        $this->assertSame('CHO_KICH_HOAT', $term->trang_thai);
        $this->dungGeminiGiaLap($exerciseId);

        $request = $this->postJson('/api/assistant/requests', [
            'request_type' => 'TAO_KE_HOACH',
            'prompt' => 'Tạo một kế hoạch từ candidate đã được Backend cho phép.',
        ], $this->headers($token))->assertCreated();
        $proposalId = (int) $request->json('data.proposal.id');
        Http::assertSentCount(1);

        $applied = $this->postJson(
            "/api/assistant/proposals/{$proposalId}/apply",
            [],
            $this->headers($token),
        )->assertOk()->assertJsonPath('data.proposal.status', 'DA_AP_DUNG');
        Http::assertSentCount(1);
        $versionId = (int) $applied->json('data.plan.current_version.id');
        $schedule = DB::table('buoi_tap_du_kien')
            ->where('hoi_vien_id', $memberId)
            ->where('phien_ban_ke_hoach_tap_id', $versionId)
            ->where('trang_thai', 'CHUA_TAP')
            ->orderBy('ngay_tap')
            ->first();
        $this->assertNotNull($schedule);

        CarbonImmutable::setTestNow(CarbonImmutable::parse($schedule->ngay_tap.' 00:00:00', 'UTC'));
        $sessionId = (int) $this->postJson(
            '/api/workout/scheduled-sessions/'.$schedule->id.'/start',
            [],
            $this->headers($token),
        )->assertCreated()->json('data.id');
        $sessionExerciseId = (int) DB::table('bai_tap_trong_phien')
            ->where('phien_tap_id', $sessionId)->value('id');
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/exercises/{$sessionExerciseId}/sets",
            ['order' => 1, 'reps' => 10, 'weight_kg' => 20, 'actual_rest_seconds' => 60],
            $this->headers($token),
        )->assertCreated();
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            ['notes' => 'Acceptance Gemini'],
            $this->headers($token),
        )->assertOk();
        $this->getJson('/api/progress/overview', $this->bearer($token))
            ->assertOk()->assertJsonPath('data.completed_sessions_count', 1);
        $this->getJson('/api/progress/exercises/'.$exerciseId, $this->bearer($token))
            ->assertOk()->assertJsonCount(1, 'data.items');

        $termAfter = $term->fresh();
        $this->assertSame('DANG_HOAT_DONG', $termAfter->trang_thai);
        $this->assertSame(1, (int) $termAfter->so_luot_tro_ly_da_dung);
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count());
    }

    public function test_pt_assignment_chat_direct_proposal_confirm_workout_and_progress_end_to_end(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $package = $this->taoGoiTapMembership($fixture['admin'], [], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
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
        $this->postJson(
            "/api/pt/chat/conversations/{$conversationId}/messages",
            $this->message('Bắt đầu chuỗi PT acceptance.'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        $this->postJson('/api/pt/direct-sessions/complete', [
            'assignment_id' => $fixture['assignment_a_id'],
            'notes' => 'Buổi PT acceptance',
        ], $this->headers($fixture['pt_a_token']))->assertCreated();

        $exerciseId = $this->taoBaiTapWorkout($fixture['pt_a']);
        $proposal = $this->postJson(
            '/api/pt/members/'.$fixture['member_a_id'].'/proposals',
            $this->ptProposalPayload($exerciseId),
            $this->headers($fixture['pt_a_token']),
        )->assertCreated();
        $proposalId = (int) $proposal->json('data.id');
        $confirmed = $this->postJson(
            "/api/pt/proposals/{$proposalId}/confirm",
            [],
            $this->headers($fixture['member_a_token']),
        )->assertOk()->assertJsonPath('data.proposal.status', 'DA_AP_DUNG');
        $versionId = (int) $confirmed->json('data.plan.current_version.id');
        $schedule = DB::table('buoi_tap_du_kien')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->where('phien_ban_ke_hoach_tap_id', $versionId)
            ->where('trang_thai', 'CHUA_TAP')
            ->orderBy('ngay_tap')
            ->first();
        $this->assertNotNull($schedule);

        CarbonImmutable::setTestNow(CarbonImmutable::parse($schedule->ngay_tap.' 00:00:00', 'UTC'));
        $sessionId = (int) $this->postJson(
            '/api/workout/scheduled-sessions/'.$schedule->id.'/start',
            [],
            $this->headers($fixture['member_a_token']),
        )->assertCreated()->json('data.id');
        $this->postJson(
            "/api/workout/sessions/{$sessionId}/complete",
            [],
            $this->headers($fixture['member_a_token']),
        )->assertOk();
        $this->getJson('/api/progress/overview', $this->bearer($fixture['member_a_token']))
            ->assertOk()->assertJsonPath('data.completed_sessions_count', 1);
        $this->getJson(
            '/api/pt/members/'.$fixture['member_a_id'].'/progress/overview',
            $this->bearer($fixture['pt_a_token']),
        )->assertOk()->assertJsonPath('data.completed_sessions_count', 1);

        $termAfter = $term->fresh();
        $this->assertSame(1, (int) $termAfter->so_buoi_huan_luyen_vien_da_dung);
        $this->assertSame(0, (int) $termAfter->so_luot_tro_ly_da_dung);
        $this->assertSame(2, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $fixture['member_a_id'])->count());
    }

    public function test_admin_account_role_catalog_and_dashboard_end_to_end(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $target['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->assertOk()->json('data.access_token');
        $headers = $this->bearer($token);

        $this->getJson('/api/admin/accounts?search='.$target['user']->thu_dien_tu, $headers)
            ->assertOk()->assertJsonPath('data.items.0.id', $target['user']->getKey());
        $roleUri = '/api/admin/accounts/'.$target['user']->getKey().'/roles/PT';
        $this->postJson('/api/admin/accounts/'.$target['user']->getKey().'/trainer-profile', [], $this->headers($token))
            ->assertOk()->assertJsonPath('data.role.transition', 'GRANTED');

        $code = 'ACCEPT_'.strtoupper(bin2hex(random_bytes(4)));
        $packageId = (int) $this->postJson('/api/admin/packages', [
            'code' => $code,
            'name' => 'Gói Admin Acceptance',
            'price' => 450000,
            'duration_days' => 30,
            'description' => 'Kiểm tra liên mô đun Admin.',
            'status' => 'DANG_BAN',
            'benefits' => [
                'gym_access' => true,
                'fitness_assistant' => true,
                'fitness_assistant_limit' => 3,
                'trainer_chat' => true,
                'direct_trainer_sessions' => 1,
            ],
        ], $headers)->assertCreated()->json('data.id');
        $this->getJson('/api/admin/packages/'.$packageId, $headers)
            ->assertOk()->assertJsonPath('data.code', $code);
        $this->getJson('/api/admin/dashboard', $headers)
            ->assertOk()->assertJsonPath('data.branch.id', $admin['branch_id']);
        $this->deleteJson($roleUri, [], $headers)
            ->assertOk()->assertJsonPath('data.transition', 'REVOKED');
    }

    private function dungGeminiGiaLap(int $exerciseId): void
    {
        config([
            'ai.provider' => 'gemini',
            'ai.model' => 'gemini-3.1-flash-lite',
            'ai.timeout_seconds' => 10,
            'ai.gemini.api_key' => 'dummy-acceptance-key',
            'ai.gemini.base_url' => 'https://generativelanguage.googleapis.com',
        ]);
        app()->instance(WorkoutAiProvider::class, app(GeminiWorkoutAiProvider::class));
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([
            'responseId' => 'gemini-acceptance-response',
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode([
                    'loai_thay_doi' => 'TAO_MOI',
                    'tieu_de' => 'Kế hoạch Gemini acceptance',
                    'giai_thich' => 'Chỉ dùng candidate do Backend cấp.',
                    'ap_dung_tu_ngay' => CarbonImmutable::now('UTC')->addDay()->toDateString(),
                    'ngay_trong_ke_hoach' => [[
                        'thu_trong_tuan' => 2,
                        'bai_tap_trong_ke_hoach' => [[
                            'bai_tap_id' => $exerciseId,
                            'thu_tu' => 1,
                            'so_hiep_muc_tieu' => 3,
                            'so_lan_lap_toi_thieu' => 8,
                            'so_lan_lap_toi_da' => 12,
                            'thoi_gian_nghi_giay' => 90,
                        ]],
                    ]],
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]]],
            ]],
            'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 50],
        ], 200)]);
    }

    /** @return array<string, mixed> */
    private function ptProposalPayload(int $exerciseId): array
    {
        return [
            'change_type' => 'TAO_MOI',
            'title' => 'Đề xuất PT acceptance',
            'explanation' => 'Hội viên phải xác nhận trước khi áp dụng.',
            'effective_from' => CarbonImmutable::now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'plan' => [
                'name' => 'Kế hoạch PT acceptance',
                'goal' => 'TANG_SUC_MANH',
                'days' => [[
                    'order' => 1,
                    'weekday' => 2,
                    'name' => 'Ngày PT acceptance',
                    'estimated_minutes' => 60,
                    'exercises' => [[
                        'exercise_id' => $exerciseId,
                        'order' => 1,
                        'target_sets' => 3,
                        'min_reps' => 8,
                        'max_reps' => 12,
                        'target_weight_kg' => 20,
                        'rest_seconds' => 90,
                        'notes' => 'Chỉ áp dụng sau xác nhận.',
                    ]],
                ]],
            ],
        ];
    }

    /** @return array<string, string> */
    private function headers(string $token): array
    {
        return [...$this->bearer($token), 'Idempotency-Key' => (string) Str::uuid()];
    }

    /** @return array<string, string> */
    private function message(string $content): array
    {
        return ['client_message_id' => (string) Str::uuid(), 'content' => $content];
    }
}
