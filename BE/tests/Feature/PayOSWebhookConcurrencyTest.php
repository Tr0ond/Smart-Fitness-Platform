<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesPaymentFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class PayOSWebhookConcurrencyTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesPaymentFixtures;
    use CreatesProfileFixtures;

    /** @var null|array<string, mixed> */
    private ?array $fixtureCanDon = null;

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $this->assertMatchesRegularExpression('/\Asmart_fitness_[a-z0-9_]*test(?:_[a-z0-9_]+)?\z/i', $database);
        $this->assertNotSame('smart_fitness', strtolower($database));
        $this->cauHinhPaymentTest();
    }

    protected function tearDown(): void
    {
        if ($this->fixtureCanDon !== null) {
            $this->donFixture();
        }
        parent::tearDown();
    }

    public function test_two_real_processes_finalize_same_webhook_exactly_once(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $created = $this->taoDonPaymentQuaApi($fixture, $goi['package']->getKey());
        $this->fixtureCanDon = [
            'fixture' => $fixture,
            'member_id' => $memberId,
            'package_id' => $goi['package']->getKey(),
        ];
        $payload = $this->webhookPayment($created['payment'], ['reference' => 'REF-CONCURRENT']);

        $cacKetQua = $this->chayHaiTienTrinh($payload);
        $trangThai = collect($cacKetQua)->pluck('result')->sort()->values()->all();
        $this->assertSame(['DA_XAC_NHAN', 'DA_XU_LY_TRUOC'], $trangThai);
        $this->assertDatabaseHas('don_mua_goi', ['id' => $created['order']->getKey(), 'trang_thai' => 'DA_THANH_TOAN']);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'THANH_CONG']);
        $this->assertSame(1, DB::table('su_kien_thanh_toan')->where('lan_thanh_toan_id', $created['payment']->getKey())->count());
        $this->assertSame(2, (int) DB::table('su_kien_thanh_toan')->where('lan_thanh_toan_id', $created['payment']->getKey())->value('so_lan_nhan'));
        $this->assertSame(1, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame(1, DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $created['order']->getKey())->count());
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count());
    }

    /** @param array<string, mixed> $payload @return array<int, array<string, mixed>> */
    private function chayHaiTienTrinh(array $payload): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $suffix = bin2hex(random_bytes(8));
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'payment_barrier_'.$suffix;
        $payloadPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'payment_payload_'.$suffix.'.json';
        file_put_contents($payloadPath, json_encode($payload, JSON_THROW_ON_ERROR));
        $script = base_path('tests/Support/run_payment_webhook.php');
        $descriptor = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];

        for ($i = 0; $i < 2; $i++) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, $script, $database, $payloadPath, $barrier], $descriptor, $pipes, base_path());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        touch($barrier);
        $results = [];
        try {
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
                $this->assertSame(0, $exitCode, trim((string) $stderr));
                $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
            }
        } finally {
            foreach ([$barrier, $payloadPath] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return $results;
    }

    private function donFixture(): void
    {
        $muc = $this->fixtureCanDon;
        DB::transaction(function () use ($muc): void {
            $memberId = $muc['member_id'];
            $userId = $muc['fixture']['user']->getKey();
            $orderIds = DB::table('don_mua_goi')->where('hoi_vien_id', $memberId)->pluck('id');
            $paymentIds = DB::table('lan_thanh_toan')->whereIn('don_mua_goi_id', $orderIds)->pluck('id');
            DB::table('su_kien_thanh_toan')->whereIn('lan_thanh_toan_id', $paymentIds)->delete();
            DB::table('ky_han_hoi_vien')->whereIn('don_mua_goi_id', $orderIds)->delete();
            DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->delete();
            DB::table('lan_thanh_toan')->whereIn('id', $paymentIds)->delete();
            DB::table('don_mua_goi')->whereIn('id', $orderIds)->delete();
            DB::table('yeu_cau_chong_lap')->where('nguoi_dung_id', $userId)->delete();
            DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $muc['package_id'])->delete();
            DB::table('goi_tap')->where('id', $muc['package_id'])->delete();
            DB::table('the_truy_cap')->where('nguoi_dung_id', $userId)->delete();
            DB::table('ho_so_hoi_vien')->where('id', $memberId)->delete();
            DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $userId)->delete();
            DB::table('nguoi_dung')->where('id', $userId)->delete();
            foreach ($muc['fixture']['role_ids'] as $roleId) {
                if (! DB::table('phan_quyen_nguoi_dung')->where('vai_tro_id', $roleId)->exists()) {
                    DB::table('vai_tro')->where('id', $roleId)->delete();
                }
            }
            DB::table('chi_nhanh')->where('id', $muc['fixture']['branch_id'])->delete();
        });
        $this->fixtureCanDon = null;
    }
}
