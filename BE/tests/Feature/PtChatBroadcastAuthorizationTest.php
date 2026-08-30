<?php

namespace Tests\Feature;

use App\Models\DangKyGoiTap;
use App\Services\Pt\Chat\PtChatAuthorizationService;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtChatFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\TestCase;

class PtChatBroadcastAuthorizationTest extends TestCase
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
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'pt-chat-test-key',
            'broadcasting.connections.reverb.secret' => 'pt-chat-test-secret',
            'broadcasting.connections.reverb.app_id' => 'pt-chat-test-app',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 18080,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        app(BroadcastManager::class)->purge('reverb');
        // routes/channels.php was booted against PHPUnit's null broadcaster;
        // register the same callback on the real Reverb/Pusher broadcaster used here.
        require base_path('routes/channels.php');
    }

    protected function tearDown(): void
    {
        app(BroadcastManager::class)->purge('reverb');
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_actual_broadcast_auth_allows_exact_historical_participants_only_without_activation(): void
    {
        $fixture = $this->taoBoPtChatFixtures(kemTheoToken: true);
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = $this->taoHoiThoaiPtChat($fixture['assignment_a_id']);
        DB::table('phan_cong_huan_luyen_vien')->where('id', $fixture['assignment_a_id'])->update([
            'ngay_ket_thuc' => now('UTC'),
            'ly_do_ket_thuc' => 'Historical channel auth test',
            'ngay_cap_nhat' => now('UTC'),
        ]);

        $this->assertTrue(app(PtChatAuthorizationService::class)->laNguoiThamGia(
            $fixture['member_a']['user'],
            (int) $hoiThoai->getKey(),
        ));
        $this->assertTrue(app(PtChatAuthorizationService::class)->laNguoiThamGia(
            $fixture['pt_a']['user'],
            (int) $hoiThoai->getKey(),
        ));

        foreach ([$fixture['member_a_token'], $fixture['pt_a_token']] as $token) {
            $this->postJson(
                '/api/broadcasting/auth',
                $this->duLieuKenh((int) $hoiThoai->getKey()),
                $this->bearer($token),
            )->assertOk()->assertJsonStructure(['auth']);
        }
        foreach ([$fixture['member_b_token'], $fixture['pt_b_token']] as $token) {
            $this->postJson(
                '/api/broadcasting/auth',
                $this->duLieuKenh((int) $hoiThoai->getKey()),
                $this->bearer($token),
            )->assertForbidden();
        }
        $this->postJson('/api/broadcasting/auth', $this->duLieuKenh((int) $hoiThoai->getKey()))
            ->assertUnauthorized();
        $this->postJson(
            '/api/broadcasting/auth',
            $this->duLieuKenh((int) $hoiThoai->getKey()),
            $this->bearer($fixture['admin_token']),
        )->assertForbidden();

        $this->assertSame('CHO_KICH_HOAT', $ky->fresh()->trang_thai);
        $this->assertNull(DangKyGoiTap::query()->findOrFail($ky->dang_ky_goi_tap_id)->ngay_bat_dau);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])->count());
    }

    /** @return array{socket_id: string, channel_name: string} */
    private function duLieuKenh(int $hoiThoaiId): array
    {
        return [
            'socket_id' => '123.456',
            'channel_name' => 'private-pt.conversation.'.$hoiThoaiId,
        ];
    }
}
