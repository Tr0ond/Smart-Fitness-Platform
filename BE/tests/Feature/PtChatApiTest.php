<?php

namespace Tests\Feature;

use App\Events\PtChatMessageSent;
use App\Models\DangKyGoiTap;
use App\Models\HoiThoai;
use App\Models\TinNhan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtChatFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\TestCase;

class PtChatApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;
    use CreatesPtChatFixtures;
    use CreatesPtFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        Event::fake([PtChatMessageSent::class]);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_routes_require_authentication_and_member_or_pt_role(): void
    {
        $this->getJson('/api/pt/chat/conversations')->assertUnauthorized();
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);

        $this->getJson('/api/pt/chat/conversations', $this->bearer($fixture['admin_token']))
            ->assertForbidden();
    }

    public function test_disabled_member_account_is_rechecked_before_each_send(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id'], 'DANG_HOAT_DONG');
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        DB::table('nguoi_dung')->where('id', $fixture['member_a']['user']->getKey())->update([
            'trang_thai' => 'BI_KHOA',
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);

        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tài khoản đã khóa'),
            $this->bearer($fixture['member_a_token']),
        )->assertUnauthorized();
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
    }

    public function test_revoked_pt_role_is_rechecked_before_each_send(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id'], 'DANG_HOAT_DONG');
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        $vaiTroPtId = DB::table('vai_tro')->where('ma_vai_tro', 'PT')->value('id');
        DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $fixture['pt_a']['user']->getKey())
            ->where('vai_tro_id', $vaiTroPtId)
            ->update([
                'thu_hoi_luc' => CarbonImmutable::now('UTC'),
                'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
            ]);

        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Role PT đã thu hồi'),
            $this->bearer($fixture['pt_a_token']),
        )->assertForbidden();
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
    }

    public function test_inactive_pt_profile_is_rechecked_before_each_send(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id'], 'DANG_HOAT_DONG');
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        DB::table('ho_so_huan_luyen_vien')->where('id', $fixture['pt_a_id'])->update([
            'trang_thai' => 'NGUNG_NHAN_PHAN_CONG',
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ]);

        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Hồ sơ PT đã ngừng'),
            $this->bearer($fixture['pt_a_token']),
        )->assertNotFound();
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
    }

    public function test_member_and_pt_resolve_one_conversation_per_assignment_without_activation(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);

        $member = $this->postJson(
            '/api/pt/chat/conversations/current',
            [],
            $this->bearer($fixture['member_a_token']),
        )->assertOk();
        $hoiThoaiId = (int) $member->json('data.id');

        $this->postJson(
            '/api/pt/chat/conversations/current',
            ['assignment_id' => $fixture['assignment_a_id']],
            $this->bearer($fixture['pt_a_token']),
        )->assertOk()->assertJsonPath('data.id', $hoiThoaiId);
        $this->postJson(
            '/api/pt/chat/conversations/current',
            [],
            $this->bearer($fixture['member_a_token']),
        )->assertOk()->assertJsonPath('data.id', $hoiThoaiId);
        $this->postJson(
            '/api/pt/chat/conversations/current',
            ['assignment_id' => $fixture['assignment_a_id']],
            $this->bearer($fixture['member_a_token']),
        )->assertUnprocessable()->assertJsonPath('code', 'CHAT_ASSIGNMENT_FIELD_NOT_ALLOWED');
        $this->postJson(
            '/api/pt/chat/conversations/current',
            ['assignment_id' => $fixture['assignment_b_id']],
            $this->bearer($fixture['pt_a_token']),
        )->assertNotFound();

        $this->assertSame(1, HoiThoai::query()
            ->where('phan_cong_huan_luyen_vien_id', $fixture['assignment_a_id'])
            ->count());
        $this->getJson('/api/pt/chat/conversations', $this->bearer($fixture['member_a_token']))
            ->assertOk()->assertJsonPath('data.0.id', $hoiThoaiId);
        $this->getJson('/api/pt/chat/conversations', $this->bearer($fixture['pt_a_token']))
            ->assertOk()->assertJsonPath('data.0.id', $hoiThoaiId);
        $this->assertSame('CHO_KICH_HOAT', $ky->fresh()->trang_thai);
        $this->assertNull($ky->fresh()->ngay_bat_dau);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->count());
    }

    public function test_conversation_and_message_endpoints_conceal_foreign_participants(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            [],
            $this->bearer($fixture['member_a_token']),
        )->assertOk()->json('data.id');

        foreach ([$fixture['member_b_token'], $fixture['pt_b_token']] as $token) {
            $this->getJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}",
                $this->bearer($token),
            )->assertNotFound();
            $this->getJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->bearer($token),
            )->assertNotFound();
            $this->postJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->tinMoi('Không thuộc hội thoại'),
                $this->bearer($token),
            )->assertNotFound();
        }
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
        Event::assertNothingDispatched();
    }

    public function test_first_member_message_activates_once_and_only_that_message_points_to_usage(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);

        $tinDau = $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tin Member kích hoạt'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        $tinDauId = (int) $tinDau->json('data.message.id');
        $usageId = TinNhan::query()->findOrFail($tinDauId)->su_dung_quyen_loi_id;

        $this->assertSame([
            'id', 'conversation_id', 'sequence', 'client_message_id', 'sender', 'content', 'sent_at',
        ], array_keys($tinDau->json('data.message')));
        $this->assertSame(['id', 'type'], array_keys($tinDau->json('data.message.sender')));

        $this->assertNotNull($usageId);
        $this->assertSame('DANG_HOAT_DONG', $ky->fresh()->trang_thai);
        $chuoi = DangKyGoiTap::query()->findOrFail($ky->dang_ky_goi_tap_id);
        $this->assertSame((int) $usageId, (int) $chuoi->lan_su_dung_dau_tien_id);
        $this->assertSame(1, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->where('loai_su_dung', 'TRO_CHUYEN_HUAN_LUYEN')
            ->count());

        $tinHaiId = (int) $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tin Member tiếp theo'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated()->json('data.message.id');
        $tinPtId = (int) $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tin PT'),
            $this->bearer($fixture['pt_a_token']),
        )->assertCreated()->json('data.message.id');
        $this->assertNull(TinNhan::query()->findOrFail($tinHaiId)->su_dung_quyen_loi_id);
        $this->assertNull(TinNhan::query()->findOrFail($tinPtId)->su_dung_quyen_loi_id);
        $this->assertSame(0, (int) $ky->fresh()->so_buoi_huan_luyen_vien_da_dung);
        $this->assertDatabaseHas('su_kien_phat_tin_nhan', [
            'tin_nhan_id' => $tinDauId,
            'trang_thai' => 'DA_PHAT',
            'so_lan_thu' => 1,
        ]);
        Event::assertDispatched(PtChatMessageSent::class, function (PtChatMessageSent $event) use ($hoiThoaiId, $tinDauId): bool {
            $payload = $event->broadcastWith();

            return $payload['conversation_id'] === $hoiThoaiId
                && $payload['message_id'] === $tinDauId
                && array_keys($payload) === [
                    'conversation_id', 'message_id', 'sequence', 'sender_id', 'sender_type', 'content', 'sent_at',
                ]
                && $event->broadcastOn()[0]->name === 'private-pt.conversation.'.$hoiThoaiId;
        });
    }

    public function test_pt_can_send_before_activation_but_never_activates_membership(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture, $fixture['pt_a_token']);

        $tinPtId = (int) $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('PT gửi trước'),
            $this->bearer($fixture['pt_a_token']),
        )->assertCreated()->json('data.message.id');
        $this->assertSame('CHO_KICH_HOAT', $ky->fresh()->trang_thai);
        $this->assertNull($ky->fresh()->ngay_bat_dau);
        $this->assertNull(TinNhan::query()->findOrFail($tinPtId)->su_dung_quyen_loi_id);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])->count());

        $tinMemberId = (int) $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Member dùng Chat'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated()->json('data.message.id');
        $this->assertSame('DANG_HOAT_DONG', $ky->fresh()->trang_thai);
        $this->assertNotNull(TinNhan::query()->findOrFail($tinMemberId)->su_dung_quyen_loi_id);
    }

    public function test_read_actions_do_not_activate_membership(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = $this->taoHoiThoaiPtChat($fixture['assignment_a_id']);

        foreach ([$fixture['member_a_token'], $fixture['pt_a_token']] as $token) {
            $this->getJson('/api/pt/chat/conversations', $this->bearer($token))->assertOk();
            $this->getJson('/api/pt/chat/conversations/'.$hoiThoai->getKey(), $this->bearer($token))->assertOk();
            $this->getJson('/api/pt/chat/conversations/'.$hoiThoai->getKey().'/messages', $this->bearer($token))->assertOk();
        }

        $this->assertSame('CHO_KICH_HOAT', $ky->fresh()->trang_thai);
        $this->assertNull($ky->fresh()->ngay_bat_dau);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])->count());
    }

    public function test_chat_and_direct_pt_quota_are_independent(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat(
            $fixture,
            $fixture['member_a_id'],
            choPhepChat: true,
            soBuoiTrucTiep: 0,
        );
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Chat có, direct bằng không'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        $this->assertSame(0, (int) $ky->fresh()->so_buoi_huan_luyen_vien);

        $fixtureB = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat(
            $fixtureB,
            $fixtureB['member_a_id'],
            choPhepChat: false,
            soBuoiTrucTiep: 10,
        );
        $hoiThoaiB = $this->taoHoiThoaiPtChat($fixtureB['assignment_a_id']);
        $this->postJson(
            '/api/pt/chat/conversations/'.$hoiThoaiB->getKey().'/messages',
            $this->tinMoi('Direct có nhưng Chat không'),
            $this->bearer($fixtureB['member_a_token']),
        )->assertStatus(409)->assertJsonPath('code', 'CHAT_ENTITLEMENT_DENIED');
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiB->getKey())->count());
    }

    public function test_chat_entitlement_is_not_borrowed_from_a_future_term(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $quyenHienTai = $this->taoGoiTapMembership($fixture['admin'], [], [
            'cho_phep_vao_phong_tap' => true,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $kyHienTai = $this->taoDonVaSnapshotMembership(
            $fixture['member_a_id'],
            $quyenHienTai['package'],
        );
        $this->xacNhanVaCapMembership($kyHienTai);

        $quyenTuongLai = $this->taoGoiTapMembership($fixture['admin'], [], [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => true,
            'so_buoi_huan_luyen_vien' => 0,
        ]);
        $kyTuongLai = $this->taoDonVaSnapshotMembership(
            $fixture['member_a_id'],
            $quyenTuongLai['package'],
        );
        $this->xacNhanVaCapMembership($kyTuongLai);
        $hoiThoai = $this->taoHoiThoaiPtChat($fixture['assignment_a_id']);

        $this->assertSame('CHO_KICH_HOAT', $kyHienTai->fresh()->trang_thai);
        $this->assertSame('CHO_DEN_LUOT', $kyTuongLai->fresh()->trang_thai);
        $this->postJson(
            '/api/pt/chat/conversations/'.$hoiThoai->getKey().'/messages',
            $this->tinMoi('Không mượn Chat của kỳ sau'),
            $this->bearer($fixture['member_a_token']),
        )->assertStatus(409)->assertJsonPath('code', 'CHAT_ENTITLEMENT_DENIED');
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoai->getKey())->count());
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])->count());
    }

    public function test_invalid_and_authority_fields_are_rejected_before_activation(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        $url = "/api/pt/chat/conversations/{$hoiThoaiId}/messages";
        $headers = $this->bearer($fixture['member_a_token']);

        foreach ([
            ['client_message_id' => $this->uuidPtChat(), 'content' => '   '],
            ['client_message_id' => $this->uuidPtChat(), 'content' => ['khong-phai-chuoi']],
            ['client_message_id' => $this->uuidPtChat(), 'content' => str_repeat('a', 65_536)],
            array_merge($this->tinMoi('Có authority field'), ['sender_id' => 999]),
        ] as $payload) {
            $this->postJson($url, $payload, $headers)->assertUnprocessable();
        }

        $this->assertSame('CHO_KICH_HOAT', $ky->fresh()->trang_thai);
        $this->assertSame(0, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])->count());
        Event::assertNothingDispatched();
    }

    public function test_client_message_id_is_idempotent_and_conflicting_payload_returns_409(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        $payload = $this->tinMoi('Nội dung ổn định');
        $url = "/api/pt/chat/conversations/{$hoiThoaiId}/messages";
        $headers = $this->bearer($fixture['member_a_token']);

        $first = $this->postJson($url, $payload, $headers)->assertCreated();
        $this->postJson($url, $payload, $headers)
            ->assertOk()
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.message.id', $first->json('data.message.id'));
        $this->postJson($url, array_merge($payload, ['content' => 'Nội dung khác']), $headers)
            ->assertStatus(409)
            ->assertJsonPath('code', 'CHAT_IDEMPOTENCY_CONFLICT');

        $this->assertSame(1, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
        $this->assertSame(1, DB::table('su_kien_phat_tin_nhan')
            ->whereIn('tin_nhan_id', TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->pluck('id'))
            ->count());
        $this->assertSame(1, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->where('loai_su_dung', 'TRO_CHUYEN_HUAN_LUYEN')->count());
        Event::assertDispatchedTimes(PtChatMessageSent::class, 1);
    }

    public function test_reassignment_preserves_old_history_but_blocks_old_send_and_new_pt_access(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id'], 'DANG_HOAT_DONG');
        $hoiThoaiCuId = $this->taoHoiThoaiHienTai($fixture);
        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiCuId}/messages",
            $this->tinMoi('Lịch sử cũ'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();

        $mocDoi = CarbonImmutable::now('UTC');
        DB::table('phan_cong_huan_luyen_vien')->where('id', $fixture['assignment_a_id'])->update([
            'ngay_ket_thuc' => $mocDoi,
            'ly_do_ket_thuc' => 'Đổi PT trong test Chat',
            'ngay_cap_nhat' => $mocDoi,
        ]);
        $phanCongMoiId = $this->taoPhanCongPtChat(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_b_id'],
            $mocDoi,
        );
        $hoiThoaiMoiId = (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            ['assignment_id' => $phanCongMoiId],
            $this->bearer($fixture['pt_b_token']),
        )->assertOk()->json('data.id');

        $this->assertNotSame($hoiThoaiCuId, $hoiThoaiMoiId);
        foreach ([$fixture['member_a_token'], $fixture['pt_a_token']] as $token) {
            $this->getJson(
                "/api/pt/chat/conversations/{$hoiThoaiCuId}/messages",
                $this->bearer($token),
            )->assertOk()->assertJsonPath('data.0.content', 'Lịch sử cũ');
            $this->postJson(
                "/api/pt/chat/conversations/{$hoiThoaiCuId}/messages",
                $this->tinMoi('Không được gửi hội thoại cũ'),
                $this->bearer($token),
            )->assertStatus(409)->assertJsonPath('code', 'CHAT_ASSIGNMENT_NOT_ACTIVE');
        }
        $this->getJson(
            "/api/pt/chat/conversations/{$hoiThoaiCuId}",
            $this->bearer($fixture['pt_b_token']),
        )->assertNotFound();
        $this->getJson('/api/pt/chat/conversations', $this->bearer($fixture['member_a_token']))
            ->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_history_remains_readable_after_membership_end_but_new_send_is_blocked(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id'], 'DANG_HOAT_DONG');
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tin trước khi hết hạn'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        CarbonImmutable::setTestNow(CarbonImmutable::instance($ky->fresh()->ngay_ket_thuc)->subMicrosecond());
        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tin ngay trước end'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();
        CarbonImmutable::setTestNow(CarbonImmutable::instance($ky->fresh()->ngay_ket_thuc));

        $memberTokenTaiBien = $this->layTokenPtChat($fixture['member_a']);
        $ptTokenTaiBien = $this->layTokenPtChat($fixture['pt_a']);

        foreach ([$memberTokenTaiBien, $ptTokenTaiBien] as $token) {
            $this->getJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->bearer($token),
            )->assertOk()->assertJsonPath('data.0.content', 'Tin trước khi hết hạn');
            $this->postJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->tinMoi('Tin tại đúng end'),
                $this->bearer($token),
            )->assertStatus(409)->assertJsonPath('code', 'CHAT_ENTITLEMENT_DENIED');
        }
        $this->assertSame(2, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
    }

    public function test_assignment_end_boundary_is_half_open_for_send_and_history(): void
    {
        $moc = CarbonImmutable::parse('2026-08-30 12:00:00.000000', 'UTC');
        CarbonImmutable::setTestNow($moc->subMicrosecond());
        $fixture = $this->taoBoPtChatFixtures(taoPhanCong: false, kemTheoToken: true);
        $phanCongId = $this->taoPhanCongPtChat(
            $fixture,
            $fixture['member_a_id'],
            $fixture['pt_a_id'],
            $moc->subHour(),
            $moc,
        );
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            [],
            $this->bearer($fixture['member_a_token']),
        )->assertOk()->json('data.id');
        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Hợp lệ ngay trước end'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();

        CarbonImmutable::setTestNow($moc);

        $this->getJson(
            '/api/pt/chat/conversations/'.$hoiThoaiId,
            $this->bearer($fixture['member_a_token']),
        )->assertOk()->assertJsonPath('data.current_assignment', false);
        $this->postJson(
            '/api/pt/chat/conversations/'.$hoiThoaiId.'/messages',
            $this->tinMoi('Không hợp lệ tại end'),
            $this->bearer($fixture['member_a_token']),
        )->assertStatus(409)->assertJsonPath('code', 'CHAT_ASSIGNMENT_NOT_ACTIVE');
    }

    public function test_message_history_uses_stable_sequence_cursor_without_duplicates(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id'], 'DANG_HOAT_DONG');
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        for ($i = 1; $i <= 7; $i++) {
            $this->postJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->tinMoi('Tin '.$i),
                $this->bearer($fixture['member_a_token']),
            )->assertCreated()->assertJsonPath('data.message.sequence', $i);
        }

        $trangMot = $this->getJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages?limit=3",
            $this->bearer($fixture['member_a_token']),
        )->assertOk();
        $this->assertSame([5, 6, 7], $trangMot->json('data.*.sequence'));
        $this->assertSame(5, $trangMot->json('meta.next_before_sequence'));
        $trangHai = $this->getJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages?limit=3&before_sequence=5",
            $this->bearer($fixture['member_a_token']),
        )->assertOk();
        $this->assertSame([2, 3, 4], $trangHai->json('data.*.sequence'));
        $trangBa = $this->getJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages?limit=3&before_sequence=2",
            $this->bearer($fixture['member_a_token']),
        )->assertOk();
        $this->assertSame([1], $trangBa->json('data.*.sequence'));
    }

    public function test_chat_usage_is_not_created_per_message(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoaiId = $this->taoHoiThoaiHienTai($fixture);
        $this->postJson(
            "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
            $this->tinMoi('Tin kích hoạt duy nhất'),
            $this->bearer($fixture['member_a_token']),
        )->assertCreated();

        for ($i = 1; $i <= 20; $i++) {
            $this->postJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->tinMoi('Member '.$i),
                $this->bearer($fixture['member_a_token']),
            )->assertCreated();
            $this->postJson(
                "/api/pt/chat/conversations/{$hoiThoaiId}/messages",
                $this->tinMoi('PT '.$i),
                $this->bearer($fixture['pt_a_token']),
            )->assertCreated();
        }

        $this->assertSame(1, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])
            ->where('loai_su_dung', 'TRO_CHUYEN_HUAN_LUYEN')
            ->count());
        $this->assertSame(1, TinNhan::query()
            ->where('hoi_thoai_id', $hoiThoaiId)
            ->whereNotNull('su_dung_quyen_loi_id')
            ->count());
        $this->assertSame(41, TinNhan::query()->where('hoi_thoai_id', $hoiThoaiId)->count());
    }

    /** @param array<string, mixed> $fixture */
    private function taoHoiThoaiHienTai(array $fixture, ?string $token = null): int
    {
        $token ??= $fixture['member_a_token'];
        $payload = $token === $fixture['pt_a_token']
            ? ['assignment_id' => $fixture['assignment_a_id']]
            : [];

        return (int) $this->postJson(
            '/api/pt/chat/conversations/current',
            $payload,
            $this->bearer($token),
        )->assertOk()->json('data.id');
    }

    /** @return array{client_message_id: string, content: string} */
    private function tinMoi(string $noiDung): array
    {
        return ['client_message_id' => $this->uuidPtChat(), 'content' => $noiDung];
    }
}
