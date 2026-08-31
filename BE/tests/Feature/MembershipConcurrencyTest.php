<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class MembershipConcurrencyTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    /** @var array<int, array{fixture: array<string, mixed>, member_id: int, package_id: int}> */
    private array $cacFixtureCanDon = [];

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cacFixtureCanDon) as $muc) {
            $this->donFixtureConcurrency($muc['fixture'], $muc['member_id'], $muc['package_id']);
        }
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_two_real_connections_activate_same_usage_exactly_once(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 22:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $this->ghiNhanFixtureCanDon($fixture, $memberId, $goi['package']->getKey());
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);
        $usage = $this->taoUsageMembership($ky, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $moc);

        $cacKetQua = $this->chayDongThoi('activate', $usage->getKey(), 0);
        $trangThai = collect($cacKetQua)->pluck('result')->sort()->values()->all();
        $chuoi = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->sole();
        $kySau = DB::table('ky_han_hoi_vien')->where('id', $ky->getKey())->sole();

        $this->assertSame(['DA_KICH_HOAT', 'MOI_KICH_HOAT'], $trangThai);
        $this->assertSame($usage->getKey(), $chuoi->lan_su_dung_dau_tien_id);
        $this->assertSame('DANG_HOAT_DONG', $chuoi->trang_thai);
        $this->assertSame('2026-08-29 22:00:00.123456', $chuoi->ngay_bat_dau);
        $this->assertSame('2026-09-28 22:00:00.123456', $kySau->ngay_ket_thuc);
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('id', $usage->getKey())->count());
    }

    public function test_two_real_connections_provision_same_payment_exactly_once(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 23:00:00.000001', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $this->ghiNhanFixtureCanDon($fixture, $memberId, $goi['package']->getKey());
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package'], $moc);
        $payment = $this->xacNhanThanhToanMembership($ky, $moc);

        $cacKetQua = $this->chayDongThoi('provision', $ky->don_mua_goi_id, $payment->getKey());
        $trangThai = collect($cacKetQua)->pluck('result')->sort()->values()->all();

        $this->assertSame(['DA_CAP_MOI', 'DA_CAP_TRUOC'], $trangThai);
        $this->assertSame(1, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->count());
        $this->assertSame(1, DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $ky->don_mua_goi_id)->count());
        $this->assertDatabaseHas('ky_han_hoi_vien', [
            'id' => $ky->getKey(),
            'lan_thanh_toan_id' => $payment->getKey(),
            'so_thu_tu' => 1,
            'trang_thai' => 'CHO_KICH_HOAT',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function chayDongThoi(string $cheDo, int $idThuNhat, int $idThuHai): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'membership_barrier_'.bin2hex(random_bytes(8));
        $script = base_path('tests/Support/run_membership_operation.php');
        $moTaOng = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $cacTienTrinh = [];
        for ($i = 0; $i < 2; $i++) {
            $cacOng = [];
            $tienTrinh = proc_open([
                PHP_BINARY,
                $script,
                $cheDo,
                $database,
                (string) $idThuNhat,
                (string) $idThuHai,
                $barrier,
            ], $moTaOng, $cacOng, base_path(), TestDatabaseGuard::moiTruongTienTrinhCon());
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
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }

        return $ketQua;
    }

    /** @param array<string, mixed> $fixture */
    private function ghiNhanFixtureCanDon(array $fixture, int $memberId, int $packageId): void
    {
        $this->cacFixtureCanDon[] = [
            'fixture' => $fixture,
            'member_id' => $memberId,
            'package_id' => $packageId,
        ];
    }

    /** @param array<string, mixed> $fixture */
    private function donFixtureConcurrency(array $fixture, int $memberId, int $packageId): void
    {
        DB::transaction(function () use ($fixture, $memberId, $packageId): void {
            $dangKyIds = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->pluck('id');
            DB::table('dang_ky_goi_tap')->whereIn('id', $dangKyIds)->update([
                'trang_thai' => 'HUY',
                'lan_su_dung_dau_tien_id' => null,
                'ngay_bat_dau' => null,
            ]);
            DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ky_han_hoi_vien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('dang_ky_goi_tap')->whereIn('id', $dangKyIds)->delete();

            $donIds = DB::table('don_mua_goi')->where('hoi_vien_id', $memberId)->pluck('id');
            DB::table('lan_thanh_toan')->whereIn('don_mua_goi_id', $donIds)->delete();
            DB::table('don_mua_goi')->whereIn('id', $donIds)->delete();
            DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $packageId)->delete();
            DB::table('goi_tap')->where('id', $packageId)->delete();
            DB::table('ho_so_hoi_vien')->where('id', $memberId)->delete();
            DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $fixture['user']->getKey())->delete();
            DB::table('nguoi_dung')->where('id', $fixture['user']->getKey())->delete();
            foreach ($fixture['role_ids'] as $roleId) {
                if (! DB::table('phan_quyen_nguoi_dung')->where('vai_tro_id', $roleId)->exists()) {
                    DB::table('vai_tro')->where('id', $roleId)->delete();
                }
            }
            DB::table('chi_nhanh')->where('id', $fixture['branch_id'])->delete();
        });
    }
}
