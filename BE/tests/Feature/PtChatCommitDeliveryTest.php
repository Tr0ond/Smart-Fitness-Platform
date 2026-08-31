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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Concerns\CreatesPtChatFixtures;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Support\TestDatabaseGuard;
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
            'loi_gan_nhat' => 'REALTIME_DELIVERY_FAILED',
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

    public function test_scheduler_retry_respects_due_time_and_has_no_business_side_effects(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 04:00:00.123456', 'UTC'));
        config([
            'pt_chat.outbox_retry_base_seconds' => 15,
            'pt_chat.outbox_retry_max_seconds' => 60,
            'pt_chat.outbox_retry_batch_size' => 10,
        ]);
        $fixture = $this->taoBoPtChatFixtures();
        $this->fixturesCanDon[] = $fixture;
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = app(PtChatConversationService::class)->hienTai($fixture['member_a']['user']);
        $failure = Mockery::mock(Dispatcher::class);
        $failure->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('secret transport detail'));
        $ketQua = $this->messageService($failure)->gui(
            $fixture['member_a']['user'],
            (int) $hoiThoai['id'],
            ['client_message_id' => $this->uuidPtChat(), 'content' => 'Tin cần scheduler retry'],
        );
        $tinNhanId = (int) $ketQua['message']['id'];
        $termBefore = DB::table('ky_han_hoi_vien')->find($ky->getKey());
        $usageBefore = DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $fixture['member_a_id'])->count();

        $success = Mockery::mock(Dispatcher::class);
        $success->shouldReceive('dispatch')->once()->with(Mockery::type(PtChatMessageSent::class))->andReturn([]);
        app()->instance(Dispatcher::class, $success);
        Artisan::call('pt-chat:retry-outbox');
        $this->assertSame(0, json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR)['selected']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 04:00:16.123456', 'UTC'));
        Artisan::call('pt-chat:retry-outbox');
        $this->assertSame(
            ['selected' => 1, 'delivered' => 1, 'retrying' => 0],
            json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR),
        );
        $this->assertDatabaseHas('su_kien_phat_tin_nhan', [
            'tin_nhan_id' => $tinNhanId,
            'trang_thai' => 'DA_PHAT',
            'so_lan_thu' => 2,
            'loi_gan_nhat' => null,
        ]);
        $termAfter = DB::table('ky_han_hoi_vien')->find($ky->getKey());
        $this->assertSame($termBefore->ngay_bat_dau, $termAfter->ngay_bat_dau);
        $this->assertSame($termBefore->ngay_ket_thuc, $termAfter->ngay_ket_thuc);
        $this->assertSame($usageBefore, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $fixture['member_a_id'])->count());
        $this->assertSame(1, DB::table('tin_nhan')->where('id', $tinNhanId)->count());
    }

    public function test_retry_failure_uses_capped_exponential_backoff_and_safe_error(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 05:00:00.000001', 'UTC'));
        config([
            'pt_chat.outbox_retry_base_seconds' => 10,
            'pt_chat.outbox_retry_max_seconds' => 15,
        ]);
        $fixture = $this->taoBoPtChatFixtures();
        $this->fixturesCanDon[] = $fixture;
        $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = app(PtChatConversationService::class)->hienTai($fixture['member_a']['user']);
        $failure = Mockery::mock(Dispatcher::class);
        $failure->shouldReceive('dispatch')->twice()->andThrow(new RuntimeException('must not persist raw detail'));
        $ketQua = $this->messageService($failure)->gui(
            $fixture['member_a']['user'],
            (int) $hoiThoai['id'],
            ['client_message_id' => $this->uuidPtChat(), 'content' => 'Backoff'],
        );
        $tinNhanId = (int) $ketQua['message']['id'];
        $first = DB::table('su_kien_phat_tin_nhan')->where('tin_nhan_id', $tinNhanId)->first();
        $this->assertSame('2026-08-31 05:00:10.000001', $first->thu_lai_luc);

        CarbonImmutable::setTestNow(CarbonImmutable::parse($first->thu_lai_luc, 'UTC'));
        $this->assertSame('CHO_THU_LAI', (new PtChatDeliveryService($failure))->phat($tinNhanId));
        $second = DB::table('su_kien_phat_tin_nhan')->where('tin_nhan_id', $tinNhanId)->first();
        $this->assertSame(2, (int) $second->so_lan_thu);
        $this->assertSame('2026-08-31 05:00:25.000001', $second->thu_lai_luc);
        $this->assertSame('REALTIME_DELIVERY_FAILED', $second->loi_gan_nhat);
        $this->assertStringNotContainsString('raw detail', (string) $second->loi_gan_nhat);
    }

    public function test_two_actual_processes_retry_same_outbox_with_one_dispatch_and_no_business_mutation(): void
    {
        $fixture = $this->taoBoPtChatFixtures();
        $this->fixturesCanDon[] = $fixture;
        $ky = $this->taoMembershipPtChat($fixture, $fixture['member_a_id']);
        $hoiThoai = app(PtChatConversationService::class)->hienTai($fixture['member_a']['user']);
        $failure = Mockery::mock(Dispatcher::class);
        $failure->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('initial failure'));
        $ketQua = $this->messageService($failure)->gui(
            $fixture['member_a']['user'],
            (int) $hoiThoai['id'],
            ['client_message_id' => $this->uuidPtChat(), 'content' => 'Retry concurrency'],
        );
        $tinNhanId = (int) $ketQua['message']['id'];
        DB::table('su_kien_phat_tin_nhan')->where('tin_nhan_id', $tinNhanId)->update([
            'thu_lai_luc' => CarbonImmutable::now('UTC')->subSecond(),
        ]);
        $usageBefore = DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $fixture['member_a_id'])->count();
        $termBefore = DB::table('ky_han_hoi_vien')->find($ky->getKey());

        [$results, $dispatchCount] = $this->chayHaiTienTrinhRetry($tinNhanId);

        $this->assertSame(['DA_PHAT', 'DA_PHAT'], collect($results)->sort()->values()->all());
        $this->assertSame(1, $dispatchCount);
        $this->assertDatabaseHas('su_kien_phat_tin_nhan', [
            'tin_nhan_id' => $tinNhanId,
            'trang_thai' => 'DA_PHAT',
            'so_lan_thu' => 2,
        ]);
        $this->assertSame(1, DB::table('tin_nhan')->where('id', $tinNhanId)->count());
        $this->assertSame($usageBefore, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $fixture['member_a_id'])->count());
        $termAfter = DB::table('ky_han_hoi_vien')->find($ky->getKey());
        $this->assertSame($termBefore->ngay_bat_dau, $termAfter->ngay_bat_dau);
        $this->assertSame($termBefore->ngay_ket_thuc, $termAfter->ngay_ket_thuc);
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

    /** @return array{0: array<int, string>, 1: int} */
    private function chayHaiTienTrinhRetry(int $tinNhanId): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $suffix = bin2hex(random_bytes(8));
        $start = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pt_chat_retry_start_'.$suffix;
        $marker = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pt_chat_retry_dispatch_'.$suffix;
        $script = base_path('tests/Support/run_pt_chat_outbox_retry.php');
        $spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        foreach ([0, 1] as $index) {
            $pipes = [];
            $process = proc_open([
                PHP_BINARY,
                $script,
                $database,
                (string) $tinNhanId,
                $start,
                $marker,
            ], $spec, $pipes, base_path(), TestDatabaseGuard::moiTruongTienTrinhCon());
            $this->assertIsResource($process, 'Cannot start retry process '.$index);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        touch($start);

        try {
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->assertSame(0, $exit, trim((string) $stderr));
                $results[] = (string) json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR)['status'];
            }
            $lines = is_file($marker) ? file($marker, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

            return [$results, count($lines ?: [])];
        } finally {
            foreach ([$start, $marker] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
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
