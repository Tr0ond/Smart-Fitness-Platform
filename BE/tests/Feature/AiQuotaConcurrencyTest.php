<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAiFixtures;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class AiQuotaConcurrencyTest extends TestCase
{
    use CreatesAiFixtures;
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    /** @var array<string, mixed>|null */
    private ?array $cleanup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $this->assertMatchesRegularExpression('/^smart_fitness_.*test/i', $database);
        $this->assertNotSame('smart_fitness', strtolower($database));
    }

    protected function tearDown(): void
    {
        if ($this->cleanup !== null) {
            $this->donFixture();
        }
        parent::tearDown();
    }

    public function test_two_actual_processes_cannot_oversubscribe_last_ai_quota(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $exerciseId = $this->taoBaiTapAi($fixture);
        $term = $this->taoMembershipAi($fixture, $memberId, 1);
        $order = DB::table('don_mua_goi')->find($term->don_mua_goi_id);
        $this->cleanup = [
            'fixture' => $fixture,
            'member_id' => $memberId,
            'exercise_id' => $exerciseId,
            'term_id' => (int) $term->getKey(),
            'registration_id' => (int) $term->dang_ky_goi_tap_id,
            'order_id' => (int) $term->don_mua_goi_id,
            'payment_id' => (int) $term->lan_thanh_toan_id,
            'package_id' => (int) $order->goi_tap_id,
        ];

        $results = $this->chayDongThoi((int) $fixture['user']->getKey());
        $this->assertSame(['error', 'success'], collect($results)->pluck('status')->sort()->values()->all());
        $this->assertSame(['AI_QUOTA_EXHAUSTED'], collect($results)->where('status', 'error')->pluck('code')->values()->all());
        $ky = DB::table('ky_han_hoi_vien')->find($term->getKey());
        $this->assertSame(1, (int) $ky->so_luot_tro_ly_da_dung);
        $this->assertSame(0, (int) $ky->so_luot_tro_ly_giu_cho);
        $this->assertLessThanOrEqual((int) $ky->gioi_han_luot_tro_ly, (int) $ky->so_luot_tro_ly_da_dung + (int) $ky->so_luot_tro_ly_giu_cho);
        $this->assertSame(1, DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $memberId)->count());
    }

    /** @return array<int, array<string, mixed>> */
    private function chayDongThoi(int $userId): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $suffix = bin2hex(random_bytes(8));
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $start = $tmp.'ai_start_'.$suffix;
        $entered = $tmp.'ai_entered_'.$suffix;
        $release = $tmp.'ai_release_'.$suffix;
        $calls = $tmp.'ai_calls_'.$suffix;
        $script = base_path('tests/Support/run_ai_request.php');
        $pipesSpec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        foreach ([$this->uuidAi(), $this->uuidAi()] as $key) {
            $pipes = [];
            $process = proc_open([
                PHP_BINARY,
                $script,
                $database,
                (string) $userId,
                $key,
                $start,
                $entered,
                $release,
                $calls,
            ], $pipesSpec, $pipes, base_path());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        touch($start);
        try {
            $deadline = microtime(true) + 20;
            while (! is_file($entered) && microtime(true) < $deadline) {
                usleep(10000);
            }
            $this->assertFileExists($entered, 'No process reached the provider after reserving quota.');

            $oneExited = false;
            while (! $oneExited && microtime(true) < $deadline) {
                foreach ($processes as [$process]) {
                    if (! proc_get_status($process)['running']) {
                        $oneExited = true;
                        break;
                    }
                }
                usleep(10000);
            }
            $this->assertTrue($oneExited, 'The quota loser did not exit while the winner held provider barrier.');
            touch($release);

            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->assertSame(0, $exit, trim((string) $stderr));
                $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
            }
            $callLines = is_file($calls) ? file($calls, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            $this->assertCount(1, $callLines, 'Provider must be called at most once for quota limit=1.');

            return $results;
        } finally {
            foreach ([$start, $entered, $release, $calls] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function donFixture(): void
    {
        $data = $this->cleanup;
        DB::transaction(function () use ($data): void {
            $requestIds = DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $data['member_id'])->pluck('id');
            DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $data['member_id'])->delete();
            DB::table('lan_goi_mo_hinh')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('bai_tap_ung_vien')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('giao_an_ung_vien')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('tin_nhan_tro_ly')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('yeu_cau_tro_ly')->whereIn('id', $requestIds)->delete();
            DB::table('tin_nhan_tro_ly')->where('hoi_thoai_tro_ly_id', function ($query) use ($data): void {
                $query->select('id')->from('hoi_thoai_tro_ly')->where('hoi_vien_id', $data['member_id']);
            })->delete();
            DB::table('hoi_thoai_tro_ly')->where('hoi_vien_id', $data['member_id'])->delete();
            DB::table('yeu_cau_chong_lap')->where('nguoi_dung_id', $data['fixture']['user']->getKey())->delete();
            DB::table('dang_ky_goi_tap')->where('id', $data['registration_id'])->update([
                'trang_thai' => 'HUY',
                'lan_su_dung_dau_tien_id' => null,
                'ngay_bat_dau' => null,
            ]);
            DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $data['member_id'])->delete();
            DB::table('ky_han_hoi_vien')->where('id', $data['term_id'])->delete();
            DB::table('dang_ky_goi_tap')->where('id', $data['registration_id'])->delete();
            DB::table('lan_thanh_toan')->where('id', $data['payment_id'])->delete();
            DB::table('don_mua_goi')->where('id', $data['order_id'])->delete();
            DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $data['package_id'])->delete();
            DB::table('goi_tap')->where('id', $data['package_id'])->delete();
            DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $data['member_id'])->delete();
            DB::table('ho_so_hoi_vien')->where('id', $data['member_id'])->delete();
            DB::table('bai_tap')->where('id', $data['exercise_id'])->delete();
            DB::table('the_truy_cap')->where('nguoi_dung_id', $data['fixture']['user']->getKey())->delete();
            DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $data['fixture']['user']->getKey())->delete();
            DB::table('nguoi_dung')->where('id', $data['fixture']['user']->getKey())->delete();
            DB::table('chi_nhanh')->where('id', $data['fixture']['branch_id'])->delete();
        });
        $this->cleanup = null;
        CarbonImmutable::setTestNow();
    }
}
