<?php

namespace Tests\Feature;

use App\Models\KyHanHoiVien;
use App\Services\MembershipActivationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesGymFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class GymCheckInTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesGymFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        config(['gym.qr_ttl_seconds' => 90]);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_first_valid_receptionist_check_in_atomically_activates_membership(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 09:00:00.654321', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $qr = $this->phatHanhQr($member['token']);

        $phanHoi = $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr]);

        $phanHoi->assertOk()
            ->assertJsonPath('data.member.id', $member['member_id'])
            ->assertJsonPath('data.membership_term_id', $member['term']->getKey())
            ->assertJsonPath('data.activation', 'MOI_KICH_HOAT');
        $usageId = (int) $phanHoi->json('data.usage_id');
        $checkInId = (int) $phanHoi->json('data.check_in_id');
        $this->assertDatabaseHas('su_dung_quyen_loi', [
            'id' => $usageId,
            'hoi_vien_id' => $member['member_id'],
            'ky_han_hoi_vien_id' => $member['term']->getKey(),
            'nguoi_thuc_hien_id' => $nhanVien['fixture']['user']->getKey(),
            'loai_su_dung' => 'VAO_PHONG_TAP',
            'chap_nhan_luc' => '2026-08-29 09:00:00.654321',
        ]);
        $this->assertDatabaseHas('dang_ky_goi_tap', [
            'hoi_vien_id' => $member['member_id'],
            'trang_thai' => 'DANG_HOAT_DONG',
            'lan_su_dung_dau_tien_id' => $usageId,
            'ngay_bat_dau' => '2026-08-29 09:00:00.654321',
        ]);
        $this->assertDatabaseHas('ky_han_hoi_vien', [
            'id' => $member['term']->getKey(),
            'trang_thai' => 'DANG_HOAT_DONG',
            'ngay_bat_dau' => '2026-08-29 09:00:00.654321',
            'ngay_ket_thuc' => '2026-09-28 09:00:00.654321',
        ]);
        $this->assertDatabaseHas('lich_su_vao_phong_tap', [
            'id' => $checkInId,
            'hoi_vien_id' => $member['member_id'],
            'ky_han_hoi_vien_id' => $member['term']->getKey(),
            'su_dung_quyen_loi_id' => $usageId,
            'nguoi_xac_nhan_id' => $nhanVien['fixture']['user']->getKey(),
            'vao_phong_luc' => '2026-08-29 09:00:00.654321',
        ]);
        $this->assertDatabaseHas('ma_vao_phong_tap', [
            'ma_bam_bi_mat' => hash('sha256', $qr),
            'da_su_dung_luc' => '2026-08-29 09:00:00.654321',
        ]);
    }

    public function test_replay_is_controlled_conflict_and_does_not_duplicate_effects(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 09:10:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $qr = $this->phatHanhQr($member['token']);
        $headers = $this->bearer($nhanVien['token']);

        $dau = $this->withHeaders($headers)->postJson('/api/gym/check-in', ['qr_token' => $qr]);
        $dau->assertOk();
        $nguonDau = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $member['member_id'])->value('lan_su_dung_dau_tien_id');
        $this->withHeaders($headers)
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertConflict()
            ->assertJsonPath('code', 'QR_ALREADY_USED');

        $this->assertSame(1, DB::table('lich_su_vao_phong_tap')->where('hoi_vien_id', $member['member_id'])->count());
        $this->assertSame(1, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $member['member_id'])->count());
        $this->assertSame($nguonDau, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $member['member_id'])->value('lan_su_dung_dau_tien_id'));
    }

    public function test_active_membership_check_in_does_not_reset_first_usage_or_clock(): void
    {
        $batDau = CarbonImmutable::parse('2026-08-29 09:20:00.000001', 'UTC');
        CarbonImmutable::setTestNow($batDau);
        $member = $this->taoMemberCoGym($batDau);
        $usageDau = $this->taoUsageMembership($member['term'], $member['fixture']['user']->getKey(), 'VAO_PHONG_TAP', $batDau);
        app(MembershipActivationService::class)->kichHoatNeuCan($usageDau->getKey());

        CarbonImmutable::setTestNow($batDau->addDay());
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $qr = $this->phatHanhQr($member['token']);
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertOk()
            ->assertJsonPath('data.activation', 'DA_KICH_HOAT');

        $chuoi = DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $member['member_id'])->sole();
        $ky = DB::table('ky_han_hoi_vien')->where('id', $member['term']->getKey())->sole();
        $this->assertSame($usageDau->getKey(), $chuoi->lan_su_dung_dau_tien_id);
        $this->assertSame('2026-08-29 09:20:00.000001', $chuoi->ngay_bat_dau);
        $this->assertSame('2026-09-28 09:20:00.000001', $ky->ngay_ket_thuc);
        $this->assertSame(2, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $member['member_id'])->count());
    }

    public function test_expiry_uses_half_open_boundary_without_sleep(): void
    {
        $phatHanh = CarbonImmutable::parse('2026-08-29 09:30:00.000000', 'UTC');
        CarbonImmutable::setTestNow($phatHanh);
        $member = $this->taoMemberCoGym($phatHanh);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $qrHetHan = $this->phatHanhQr($member['token']);

        CarbonImmutable::setTestNow($phatHanh->addSeconds(90));
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qrHetHan])
            ->assertStatus(410)
            ->assertJsonPath('code', 'QR_EXPIRED');
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $member['member_id'])->count());

        CarbonImmutable::setTestNow($phatHanh);
        $qrConHan = $this->phatHanhQr($member['token']);
        CarbonImmutable::setTestNow($phatHanh->addSeconds(90)->subMicrosecond());
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qrConHan])
            ->assertOk();
    }

    public function test_scan_rechecks_current_membership_and_blocks_cancelled_chain(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 09:40:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $qr = $this->phatHanhQr($member['token']);
        DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $member['member_id'])->update([
            'trang_thai' => 'HUY',
            'ket_thuc_ghi_nhan_luc' => $moc,
            'ngay_cap_nhat' => $moc,
        ]);
        DB::table('ky_han_hoi_vien')->where('id', $member['term']->getKey())->update([
            'trang_thai' => 'HUY',
            'ngay_cap_nhat' => $moc,
        ]);

        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertForbidden()
            ->assertJsonPath('code', 'GYM_ACCESS_DENIED');
        $this->assertSame(0, DB::table('lich_su_vao_phong_tap')->where('hoi_vien_id', $member['member_id'])->count());
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $member['member_id'])->count());
    }

    public function test_scan_rechecks_member_role_and_account_after_qr_issue(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 09:50:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);

        $matRole = $this->taoMemberCoGym($moc);
        $qrMatRole = $this->phatHanhQr($matRole['token']);
        DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $matRole['fixture']['user']->getKey())
            ->where('vai_tro_id', $matRole['fixture']['role_ids']['MEMBER'])
            ->update(['thu_hoi_luc' => $moc]);
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qrMatRole])
            ->assertForbidden()
            ->assertJsonPath('code', 'MEMBER_NOT_ELIGIBLE');

        $biKhoa = $this->taoMemberCoGym($moc);
        $qrBiKhoa = $this->phatHanhQr($biKhoa['token']);
        DB::table('nguoi_dung')->where('id', $biKhoa['fixture']['user']->getKey())->update(['trang_thai' => 'BI_KHOA']);
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qrBiKhoa])
            ->assertForbidden()
            ->assertJsonPath('code', 'MEMBER_NOT_ELIGIBLE');
    }

    public function test_only_receptionist_or_admin_can_scan_and_staff_revocation_is_immediate(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 10:00:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $qr = $this->phatHanhQr($member['token']);

        $this->withHeaders(['Authorization' => 'Bearer '.str_repeat('0', 64)])
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertUnauthorized();
        $this->withHeaders($this->bearer($member['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertForbidden();
        $pt = $this->taoNhanVien(['PT']);
        $this->withHeaders($this->bearer($pt['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertForbidden();

        $leTan = $this->taoNhanVien(['RECEPTIONIST']);
        DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $leTan['fixture']['user']->getKey())
            ->where('vai_tro_id', $leTan['fixture']['role_ids']['RECEPTIONIST'])
            ->update(['thu_hoi_luc' => $moc]);
        $this->withHeaders($this->bearer($leTan['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertForbidden();

        $admin = $this->taoNhanVien(['ADMIN']);
        $this->withHeaders($this->bearer($admin['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertOk();
    }

    public function test_malformed_unknown_and_revoked_qr_are_controlled(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 10:10:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $headers = $this->bearer($nhanVien['token']);

        $this->withHeaders($headers)->postJson('/api/gym/check-in', ['qr_token' => 'short'])->assertUnprocessable();
        $this->withHeaders($headers)->postJson('/api/gym/check-in', ['qr_token' => str_repeat('a', 63).'Z'])->assertUnprocessable();
        $this->withHeaders($headers)
            ->postJson('/api/gym/check-in', ['qr_token' => str_repeat('f', 64)])
            ->assertNotFound()
            ->assertJsonPath('code', 'QR_NOT_FOUND');

        $qrCu = $this->phatHanhQr($member['token']);
        CarbonImmutable::setTestNow($moc->addSecond());
        $this->phatHanhQr($member['token']);
        $this->withHeaders($headers)
            ->postJson('/api/gym/check-in', ['qr_token' => $qrCu])
            ->assertConflict()
            ->assertJsonPath('code', 'QR_REVOKED');
    }

    public function test_member_history_is_self_scoped_and_excludes_qr_secrets(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 10:20:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $nguoiKhac = $this->taoMemberCoGym($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $qr = $this->phatHanhQr($member['token']);
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertOk();

        $phanHoi = $this->withHeaders($this->bearer($member['token']))->getJson('/api/gym/check-ins');
        $phanHoi->assertOk()->assertJsonCount(1, 'data');
        $noiDung = $phanHoi->getContent();
        $this->assertStringNotContainsString($qr, $noiDung);
        $this->assertStringNotContainsString('ma_bam_bi_mat', $noiDung);
        $this->assertStringNotContainsString('qr_token', $noiDung);
        $this->assertStringNotContainsString('payment', $noiDung);

        $this->withHeaders($this->bearer($nguoiKhac['token']))
            ->getJson('/api/gym/check-ins')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->withHeaders(['Authorization' => 'Bearer '.str_repeat('0', 64)])
            ->getJson('/api/gym/check-ins')
            ->assertUnauthorized();
    }

    public function test_qr_created_before_term_boundary_uses_new_current_term(): void
    {
        $batDau = CarbonImmutable::parse('2026-08-01 00:00:00.000001', 'UTC');
        CarbonImmutable::setTestNow($batDau);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $kyMot = $this->taoDonVaSnapshotMembership($hoiVienId, $goi['package'], $batDau);
        $this->xacNhanVaCapMembership($kyMot, $batDau);
        $kyHai = $this->taoDonVaSnapshotMembership($hoiVienId, $goi['package'], $batDau->addSecond());
        $this->xacNhanVaCapMembership($kyHai, $batDau->addSecond());
        $usageDau = $this->taoUsageMembership($kyMot, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $batDau);
        app(MembershipActivationService::class)->kichHoatNeuCan($usageDau->getKey());
        $token = $this->layTokenProfile($fixture);

        CarbonImmutable::setTestNow($batDau->addDays(30)->subSeconds(30));
        $qr = $this->phatHanhQr($token);
        CarbonImmutable::setTestNow($batDau->addDays(30));
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $this->withHeaders($this->bearer($nhanVien['token']))
            ->postJson('/api/gym/check-in', ['qr_token' => $qr])
            ->assertOk()
            ->assertJsonPath('data.membership_term_id', $kyHai->getKey())
            ->assertJsonPath('data.activation', 'DA_KICH_HOAT');
        $this->assertDatabaseHas('ky_han_hoi_vien', ['id' => $kyMot->getKey(), 'trang_thai' => 'HET_HAN']);
        $this->assertDatabaseHas('ky_han_hoi_vien', ['id' => $kyHai->getKey(), 'trang_thai' => 'DANG_HOAT_DONG']);
    }

    public function test_multiple_new_qrs_allow_multiple_same_day_check_ins_without_daily_limit(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 10:30:00', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $member = $this->taoMemberCoGym($moc);
        $nhanVien = $this->taoNhanVien(['RECEPTIONIST']);
        $headers = $this->bearer($nhanVien['token']);

        $qrMot = $this->phatHanhQr($member['token']);
        $this->withHeaders($headers)->postJson('/api/gym/check-in', ['qr_token' => $qrMot])->assertOk();
        CarbonImmutable::setTestNow($moc->addMinute());
        $qrHai = $this->phatHanhQr($member['token']);
        $this->withHeaders($headers)->postJson('/api/gym/check-in', ['qr_token' => $qrHai])->assertOk();

        $this->assertSame(2, DB::table('lich_su_vao_phong_tap')->where('hoi_vien_id', $member['member_id'])->count());
        $this->assertSame(2, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $member['member_id'])->count());
    }

    /** @return array{fixture: array<string, mixed>, member_id: int, term: KyHanHoiVien, token: string} */
    private function taoMemberCoGym(CarbonImmutable $moc): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienId = $this->taoHoSoHoiVien($fixture);
        $membership = $this->taoMembershipGym($fixture, $hoiVienId, $moc);

        return [
            'fixture' => $fixture,
            'member_id' => $hoiVienId,
            'term' => $membership['term'],
            'token' => $this->layTokenProfile($fixture),
        ];
    }

    /** @param array<int, string> $vaiTro */
    private function taoNhanVien(array $vaiTro): array
    {
        $fixture = $this->taoNguoiDungAuth($vaiTro);

        return ['fixture' => $fixture, 'token' => $this->layTokenProfile($fixture)];
    }

    private function phatHanhQr(string $token): string
    {
        return (string) $this->withHeaders($this->bearer($token))
            ->postJson('/api/gym/qr')
            ->assertCreated()
            ->json('data.qr_token');
    }
}
