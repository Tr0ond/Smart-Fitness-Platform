<?php

namespace Tests\Feature;

use App\Events\PtChatMessageSent;
use App\Models\DangKyGoiTap;
use App\Models\TinNhan;
use App\Services\MembershipActivationService;
use App\Services\MembershipEntitlementService;
use App\Services\Pt\Chat\PtChatAuthorizationService;
use App\Services\Pt\Chat\PtChatConversationService;
use App\Services\Pt\Chat\PtChatDeliveryService;
use App\Services\Pt\Chat\PtChatMessageService;
use App\Services\Pt\Chat\PtChatQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtChatFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\TestCase;

/**
 * Không bọc outer transaction: callback transport phải quan sát business COMMIT thật.
 * Dữ liệu ngẫu nhiên chỉ nằm trong schema Chat test dùng một lần và được drop cuối run.
 */
class PtChatCommitDeliveryTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;
    use CreatesPtChatFixtures;
    use CreatesPtFixtures;

    /** @var array<int, array<string, mixed>> */
    private array $fixturesCanDon = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->fixturesCanDon) as $fixture) {
            $this->donFixtureDaCommit($fixture);
        }
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_transport_is_called_only_after_message_activation_and_outbox_commit(): void
    {
        $fixture = $this->taoBoPtChatFixtures();
        $this->fixturesCanDon[] = $fixture;
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = app(PtChatConversationService::class)->hienTai($fixture['member_a']['user']);
        $daQuanSatCommit = false;
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function (mixed $event) use ($ky, &$daQuanSatCommit): bool {
                if (! $event instanceof PtChatMessageSent) {
                    return false;
                }
                $messageId = (int) $event->broadcastWith()['message_id'];
                $daQuanSatCommit = DB::connection()->transactionLevel() === 0
                    && DB::table('tin_nhan')->where('id', $messageId)->exists()
                    && DB::table('su_kien_phat_tin_nhan')
                        ->where('tin_nhan_id', $messageId)
                        ->where('trang_thai', 'CHO_PHAT')
                        ->exists()
                    && $ky->fresh()->trang_thai === 'DANG_HOAT_DONG';

                return true;
            }))
            ->andReturn([]);

        $ketQua = $this->messageService($dispatcher)->gui(
            $fixture['member_a']['user'],
            (int) $hoiThoai['id'],
            ['client_message_id' => $this->uuidPtChat(), 'content' => 'Commit trước transport'],
        );

        $this->assertTrue($daQuanSatCommit);
        $this->assertSame('DA_PHAT', $ketQua['realtime_delivery']);
        $this->assertDatabaseHas('su_kien_phat_tin_nhan', [
            'tin_nhan_id' => $ketQua['message']['id'],
            'trang_thai' => 'DA_PHAT',
            'so_lan_thu' => 1,
        ]);
    }

    public function test_transport_failure_keeps_message_activation_and_usage_for_http_recovery(): void
    {
        $fixture = $this->taoBoPtChatFixtures();
        $this->fixturesCanDon[] = $fixture;
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = app(PtChatConversationService::class)->hienTai($fixture['member_a']['user']);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andThrow(new RuntimeException('Reverb test transport unavailable'));

        $ketQua = $this->messageService($dispatcher)->gui(
            $fixture['member_a']['user'],
            (int) $hoiThoai['id'],
            ['client_message_id' => $this->uuidPtChat(), 'content' => 'Tin vẫn phải tồn tại'],
        );

        $tinNhan = TinNhan::query()->findOrFail($ketQua['message']['id']);
        $this->assertSame('CHO_THU_LAI', $ketQua['realtime_delivery']);
        $this->assertNotNull($tinNhan->su_dung_quyen_loi_id);
        $this->assertSame('DANG_HOAT_DONG', $ky->fresh()->trang_thai);
        $this->assertSame(
            (int) $tinNhan->su_dung_quyen_loi_id,
            (int) DangKyGoiTap::query()->findOrFail($ky->dang_ky_goi_tap_id)->lan_su_dung_dau_tien_id,
        );
        $this->assertDatabaseHas('su_kien_phat_tin_nhan', [
            'tin_nhan_id' => $tinNhan->getKey(),
            'trang_thai' => 'CHO_THU_LAI',
            'so_lan_thu' => 1,
            'loi_gan_nhat' => 'Reverb test transport unavailable',
        ]);
        $this->assertSame('Tin vẫn phải tồn tại', app(PtChatQueryService::class)
            ->tinNhans($fixture['member_a']['user'], (int) $hoiThoai['id'])['data'][0]['content']);
    }

    public function test_message_storage_failure_rolls_back_activation_usage_and_outbox_atomically(): void
    {
        $fixture = $this->taoBoPtChatFixtures();
        $this->fixturesCanDon[] = $fixture;
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = app(PtChatConversationService::class)->hienTai($fixture['member_a']['user']);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        try {
            $this->messageService($dispatcher)->gui(
                $fixture['member_a']['user'],
                (int) $hoiThoai['id'],
                ['client_message_id' => $this->uuidPtChat(), 'content' => str_repeat('a', 65_536)],
            );
            $this->fail('MariaDB strict mode phải từ chối payload vượt giới hạn TEXT.');
        } catch (QueryException) {
            // Lỗi storage xảy ra sau bước tạo usage/kích hoạt, nên transaction phải đảo toàn bộ.
        }

        $this->assertSame('CHO_KICH_HOAT', $ky->fresh()->trang_thai);
        $this->assertNull($ky->fresh()->ngay_bat_dau);
        $this->assertSame(0, DB::table('su_dung_quyen_loi')
            ->where('hoi_vien_id', $fixture['member_a_id'])->count());
        $this->assertSame(0, DB::table('tin_nhan')
            ->where('hoi_thoai_id', $hoiThoai['id'])->count());
        $this->assertSame(0, DB::table('su_kien_phat_tin_nhan')->count());
    }

    private function messageService(Dispatcher $dispatcher): PtChatMessageService
    {
        return new PtChatMessageService(
            app(PtChatAuthorizationService::class),
            app(PtChatQueryService::class),
            new PtChatDeliveryService($dispatcher),
            app(MembershipEntitlementService::class),
            app(MembershipActivationService::class),
        );
    }

    /**
     * Dọn đúng graph fixture đã commit, không disable FK và không đụng dữ liệu
     * seed/fixture của test khác. Cycle Membership được đưa về CHO_KICH_HOAT
     * trước khi tháo pointer nullable rồi mới xóa theo thứ tự FK.
     *
     * @param  array<string, mixed>  $fixture
     */
    private function donFixtureDaCommit(array $fixture): void
    {
        $hoiVienIds = [(int) $fixture['member_a_id'], (int) $fixture['member_b_id']];
        $nguoiDungIds = collect(['admin', 'member_a', 'member_b', 'pt_a', 'pt_b'])
            ->map(fn (string $actor): int => (int) $fixture[$actor]['user']->getKey())
            ->all();
        $chiNhanhIds = collect(['admin', 'member_a', 'member_b', 'pt_a', 'pt_b'])
            ->map(fn (string $actor): int => (int) $fixture[$actor]['branch_id'])
            ->all();
        $donIds = DB::table('don_mua_goi')->whereIn('hoi_vien_id', $hoiVienIds)->pluck('id');
        $goiIds = DB::table('don_mua_goi')->whereIn('id', $donIds)->pluck('goi_tap_id');
        $chuoiIds = DB::table('dang_ky_goi_tap')->whereIn('hoi_vien_id', $hoiVienIds)->pluck('id');
        $tinNhanIds = DB::table('tin_nhan')->whereIn('hoi_vien_id', $hoiVienIds)->pluck('id');

        DB::table('su_kien_phat_tin_nhan')->whereIn('tin_nhan_id', $tinNhanIds)->delete();
        DB::table('tin_nhan')->whereIn('id', $tinNhanIds)->delete();
        DB::table('hoi_thoai')->whereIn('hoi_vien_id', $hoiVienIds)->delete();
        DB::table('dang_ky_goi_tap')->whereIn('id', $chuoiIds)->update([
            'trang_thai' => 'CHO_KICH_HOAT',
            'lan_su_dung_dau_tien_id' => null,
            'ngay_bat_dau' => null,
            'ket_thuc_ghi_nhan_luc' => null,
        ]);
        DB::table('ky_han_hoi_vien')->whereIn('dang_ky_goi_tap_id', $chuoiIds)->update([
            'trang_thai' => 'CHO_KICH_HOAT',
            'ngay_bat_dau' => null,
            'ngay_ket_thuc' => null,
        ]);
        DB::table('su_dung_quyen_loi')->whereIn('hoi_vien_id', $hoiVienIds)->delete();
        DB::table('ky_han_hoi_vien')->whereIn('dang_ky_goi_tap_id', $chuoiIds)->delete();
        DB::table('dang_ky_goi_tap')->whereIn('id', $chuoiIds)->delete();
        DB::table('lan_thanh_toan')->whereIn('don_mua_goi_id', $donIds)->delete();
        DB::table('don_mua_goi')->whereIn('id', $donIds)->delete();
        DB::table('quyen_loi_goi_tap')->whereIn('goi_tap_id', $goiIds)->delete();
        DB::table('goi_tap')->whereIn('id', $goiIds)->delete();
        DB::table('phan_cong_huan_luyen_vien')->whereIn('hoi_vien_id', $hoiVienIds)->delete();
        DB::table('ho_so_huan_luyen_vien')->whereIn('id', [
            (int) $fixture['pt_a_id'],
            (int) $fixture['pt_b_id'],
        ])->delete();
        DB::table('ho_so_hoi_vien')->whereIn('id', $hoiVienIds)->delete();
        DB::table('the_truy_cap')->whereIn('nguoi_dung_id', $nguoiDungIds)->delete();
        DB::table('phan_quyen_nguoi_dung')->whereIn('nguoi_dung_id', $nguoiDungIds)->delete();
        DB::table('nguoi_dung')->whereIn('id', $nguoiDungIds)->delete();
        DB::table('chi_nhanh')->whereIn('id', $chiNhanhIds)->delete();
    }
}
