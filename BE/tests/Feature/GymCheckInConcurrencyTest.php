<?php

namespace Tests\Feature;

use App\Services\Gym\GymQrService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesGymFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class GymCheckInConcurrencyTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesGymFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    /** @var array<string, mixed>|null */
    private ?array $duLieuCanDon = null;

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $this->assertMatchesRegularExpression('/^smart_fitness_.*test/i', $database);
        $this->assertNotSame('smart_fitness', strtolower($database));
        config(['gym.qr_ttl_seconds' => 90]);
    }

    protected function tearDown(): void
    {
        if ($this->duLieuCanDon !== null) {
            $this->donFixture();
        }
        parent::tearDown();
    }

    public function test_two_real_processes_scan_same_qr_exactly_once(): void
    {
        $moc = CarbonImmutable::now('UTC');
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($member);
        $membership = $this->taoMembershipGym($member, $memberId, $moc);
        $staff = $this->taoNguoiDungAuth(['RECEPTIONIST']);
        $this->duLieuCanDon = [
            'member' => $member,
            'member_id' => $memberId,
            'package_id' => $membership['package_id'],
            'staff' => $staff,
        ];
        $qr = app(GymQrService::class)->phatHanh($member['user'])['qr_token'];

        $cacKetQua = $this->chayDongThoi((int) $staff['user']->getKey(), $qr);
        $trangThai = collect($cacKetQua)->pluck('status')->sort()->values()->all();
        $maLoi = collect($cacKetQua)->where('status', 'error')->pluck('code')->values()->all();

        $this->assertSame(['error', 'success'], $trangThai);
        $this->assertSame(['QR_ALREADY_USED'], $maLoi);
        $this->assertSame(1, DB::table('lich_su_vao_phong_tap')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame(1, DB::table('ma_vao_phong_tap')
            ->where('hoi_vien_id', $memberId)
            ->whereNotNull('da_su_dung_luc')
            ->count());
        $chuoi = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->sole();
        $this->assertSame('DANG_HOAT_DONG', $chuoi->trang_thai);
        $this->assertNotNull($chuoi->lan_su_dung_dau_tien_id);
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('id', $chuoi->lan_su_dung_dau_tien_id)->count());
    }

    /** @return array<int, array<string, mixed>> */
    private function chayDongThoi(int $staffId, string $qr): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $hauTo = bin2hex(random_bytes(8));
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gym_barrier_'.$hauTo;
        $secretFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gym_secret_'.$hauTo;
        file_put_contents($secretFile, $qr, LOCK_EX);
        $script = base_path('tests/Support/run_gym_check_in.php');
        $moTaOng = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $cacTienTrinh = [];
        for ($i = 0; $i < 2; $i++) {
            $cacOng = [];
            $tienTrinh = proc_open([
                PHP_BINARY,
                $script,
                $database,
                (string) $staffId,
                $secretFile,
                $barrier,
            ], $moTaOng, $cacOng, base_path());
            $this->assertIsResource($tienTrinh);
            fclose($cacOng[0]);
            $cacTienTrinh[] = [$tienTrinh, $cacOng];
        }

        touch($barrier);
        $ketQua = [];
        try {
            foreach ($cacTienTrinh as [$tienTrinh, $cacOng]) {
                $stdout = stream_get_contents($cacOng[1]);
                $stderr = stream_get_contents($cacOng[2]);
                fclose($cacOng[1]);
                fclose($cacOng[2]);
                $maThoat = proc_close($tienTrinh);
                $this->assertSame(0, $maThoat, trim((string) $stderr));
                $duLieu = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
                $this->assertIsArray($duLieu);
                $ketQua[] = $duLieu;
            }
        } finally {
            foreach ([$barrier, $secretFile] as $tepTam) {
                if (is_file($tepTam)) {
                    unlink($tepTam);
                }
            }
        }

        return $ketQua;
    }

    private function donFixture(): void
    {
        $duLieu = $this->duLieuCanDon;
        DB::transaction(function () use ($duLieu): void {
            $memberId = $duLieu['member_id'];
            $dangKyIds = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->pluck('id');
            DB::table('lich_su_vao_phong_tap')->where('hoi_vien_id', $memberId)->delete();
            DB::table('dang_ky_goi_tap')->whereIn('id', $dangKyIds)->update([
                'trang_thai' => 'HUY',
                'lan_su_dung_dau_tien_id' => null,
                'ngay_bat_dau' => null,
            ]);
            DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ma_vao_phong_tap')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('dang_ky_goi_tap')->whereIn('id', $dangKyIds)->delete();
            $donIds = DB::table('don_mua_goi')->where('hoi_vien_id', $memberId)->pluck('id');
            DB::table('lan_thanh_toan')->whereIn('don_mua_goi_id', $donIds)->delete();
            DB::table('don_mua_goi')->whereIn('id', $donIds)->delete();
            DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $duLieu['package_id'])->delete();
            DB::table('goi_tap')->where('id', $duLieu['package_id'])->delete();
            DB::table('ho_so_hoi_vien')->where('id', $memberId)->delete();

            foreach ([$duLieu['member'], $duLieu['staff']] as $fixture) {
                DB::table('the_truy_cap')->where('nguoi_dung_id', $fixture['user']->getKey())->delete();
                DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $fixture['user']->getKey())->delete();
                DB::table('nguoi_dung')->where('id', $fixture['user']->getKey())->delete();
                DB::table('chi_nhanh')->where('id', $fixture['branch_id'])->delete();
            }
        });
        $this->duLieuCanDon = null;
    }
}
