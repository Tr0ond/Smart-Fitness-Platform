<?php

namespace Tests\Feature;

use App\Services\MembershipActivationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class PackageMembershipApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_package_and_membership_routes_require_authentication(): void
    {
        $this->getJson('/api/packages')->assertUnauthorized();
        $this->getJson('/api/packages/1')->assertUnauthorized();
        $this->getJson('/api/membership')->assertUnauthorized();
    }

    public function test_empty_package_catalog_is_a_valid_empty_list(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $token = $this->layTokenProfile($fixture);

        $this->getJson('/api/packages', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_package_catalog_is_read_only_and_hides_stopped_packages(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $token = $this->layTokenProfile($fixture);
        $dangBan = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'Gói đang bán']);
        $ngungBan = $this->taoGoiTapMembership($fixture, [
            'ten_goi' => 'Gói ngừng bán',
            'trang_thai' => 'NGUNG_BAN',
        ]);
        $thieuQuyen = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'Gói thiếu quyền']);
        DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $thieuQuyen['package']->getKey())->delete();
        $truoc = [
            'orders' => DB::table('don_mua_goi')->count(),
            'terms' => DB::table('ky_han_hoi_vien')->count(),
            'usages' => DB::table('su_dung_quyen_loi')->count(),
        ];

        $danhSach = $this->getJson('/api/packages', $this->bearer($token))->assertOk();
        $cacId = collect($danhSach->json('data'))->pluck('id');
        $this->assertTrue($cacId->contains($dangBan['package']->getKey()));
        $this->assertFalse($cacId->contains($ngungBan['package']->getKey()));
        $this->assertFalse($cacId->contains($thieuQuyen['package']->getKey()));
        $this->getJson('/api/packages/'.$dangBan['package']->getKey(), $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.name', 'Gói đang bán')
            ->assertJsonPath('data.benefits.trainer_chat', true)
            ->assertJsonPath('data.benefits.direct_trainer_sessions', 4)
            ->assertJsonMissingPath('data.nguoi_tao_id');
        $this->getJson('/api/packages/'.$ngungBan['package']->getKey(), $this->bearer($token))
            ->assertNotFound();
        $this->getJson('/api/packages/999999999', $this->bearer($token))->assertNotFound();

        $this->assertSame($truoc['orders'], DB::table('don_mua_goi')->count());
        $this->assertSame($truoc['terms'], DB::table('ky_han_hoi_vien')->count());
        $this->assertSame($truoc['usages'], DB::table('su_dung_quyen_loi')->count());
    }

    public function test_member_without_membership_receives_controlled_empty_response(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->getJson('/api/membership', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.member_id', $memberId)
            ->assertJsonPath('data.current', null)
            ->assertJsonPath('data.pending_payment_terms', [])
            ->assertJsonPath('data.history', []);
    }

    public function test_pending_payment_snapshot_is_visible_without_becoming_current_membership(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'PENDING SNAPSHOT']);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package']);
        $token = $this->layTokenProfile($fixture);

        $this->getJson('/api/membership', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.current', null)
            ->assertJsonCount(1, 'data.pending_payment_terms')
            ->assertJsonPath('data.pending_payment_terms.0.id', $ky->getKey())
            ->assertJsonPath('data.pending_payment_terms.0.status', 'CHO_THANH_TOAN')
            ->assertJsonPath('data.pending_payment_terms.0.package_name', 'PENDING SNAPSHOT');
    }

    public function test_membership_requires_active_member_role_and_never_accepts_foreign_member_id(): void
    {
        $memberA = $this->taoNguoiDungAuth(['MEMBER']);
        $memberAId = $this->taoHoSoHoiVien($memberA);
        $tokenA = $this->layTokenProfile($memberA);
        $memberB = $this->taoNguoiDungAuth(['MEMBER']);
        $memberBId = $this->taoHoSoHoiVien($memberB);
        $goiB = $this->taoGoiTapMembership($memberB, ['ten_goi' => 'B-SECRET-PACKAGE']);
        $kyB = $this->taoDonVaSnapshotMembership($memberBId, $goiB['package']);
        $this->xacNhanVaCapMembership($kyB);

        $response = $this->getJson('/api/membership?member_id='.$memberBId, $this->bearer($tokenA))
            ->assertOk()
            ->assertJsonPath('data.member_id', $memberAId)
            ->assertJsonPath('data.current', null);
        $this->assertStringNotContainsString('B-SECRET-PACKAGE', $response->getContent());

        $pt = $this->taoNguoiDungAuth(['PT']);
        $this->getJson('/api/membership', $this->bearer($this->layTokenProfile($pt)))->assertForbidden();
        $this->thuHoiVaiTroProfile($memberA, 'MEMBER');
        $this->getJson('/api/membership', $this->bearer($tokenA))->assertForbidden();
    }

    public function test_membership_read_uses_snapshots_and_auth_profile_catalog_reads_do_not_activate(): void
    {
        $moc = CarbonImmutable::parse('2026-08-29 08:00:00.123456', 'UTC');
        CarbonImmutable::setTestNow($moc);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'SNAPSHOT V1']);
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package']);
        $this->xacNhanVaCapMembership($ky, $moc);
        DB::table('goi_tap')->where('id', $goi['package']->getKey())->update([
            'ten_goi' => 'CATALOG V2',
            'phien_ban_cau_hinh' => 2,
        ]);

        $usageTruoc = DB::table('su_dung_quyen_loi')->count();
        $token = $this->layTokenProfile($fixture);
        $this->getJson('/api/auth/me', $this->bearer($token))->assertOk();
        $this->getJson('/api/profile', $this->bearer($token))->assertOk();
        $this->patchJson('/api/profile', ['name' => 'Không kích hoạt'], $this->bearer($token))->assertOk();
        $this->getJson('/api/profile/member', $this->bearer($token))->assertOk();
        $this->getJson('/api/profile/member/availability', $this->bearer($token))->assertOk();
        $this->putJson('/api/profile/member/availability', ['days' => [2, 4]], $this->bearer($token))->assertOk();
        $dungCuId = $this->taoDungCuProfile();
        $this->getJson('/api/profile/member/equipment', $this->bearer($token))->assertOk();
        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => [$dungCuId]], $this->bearer($token))->assertOk();
        $this->getJson('/api/packages', $this->bearer($token))->assertOk();
        $this->getJson('/api/membership', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.current.registration.status', 'CHO_KICH_HOAT')
            ->assertJsonPath('data.current.applicable_term.package_name', 'SNAPSHOT V1')
            ->assertJsonPath('data.current.applicable_term.package_version', 1)
            ->assertJsonPath('data.current.applicable_term.starts_at', null)
            ->assertJsonMissingPath('data.current.applicable_term.payment_id')
            ->assertJsonMissingPath('data.current.applicable_term.provider_reference');
        $this->postJson('/api/auth/logout', [], $this->bearer($token))->assertOk();

        $this->assertSame($usageTruoc, DB::table('su_dung_quyen_loi')->count());
        $this->assertDatabaseHas('dang_ky_goi_tap', [
            'hoi_vien_id' => $memberId,
            'trang_thai' => 'CHO_KICH_HOAT',
            'lan_su_dung_dau_tien_id' => null,
            'ngay_bat_dau' => null,
        ]);
        $this->assertFalse(app()->bound('payment'));
        $this->assertInstanceOf(MembershipActivationService::class, app(MembershipActivationService::class));
    }

    public function test_cancelled_and_expired_memberships_remain_in_safe_history(): void
    {
        $mocCu = CarbonImmutable::parse('2026-06-01 08:00:00', 'UTC');
        CarbonImmutable::setTestNow($mocCu);
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goiHuy = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'HISTORY CANCELLED']);
        $kyHuy = $this->taoDonVaSnapshotMembership($memberId, $goiHuy['package'], $mocCu);
        $this->xacNhanVaCapMembership($kyHuy, $mocCu);
        DB::table('ky_han_hoi_vien')->where('id', $kyHuy->getKey())->update(['trang_thai' => 'HUY']);
        DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->update(['trang_thai' => 'HUY']);

        $mocKichHoat = $mocCu->addDay();
        CarbonImmutable::setTestNow($mocKichHoat);
        $goiHetHan = $this->taoGoiTapMembership($fixture, [
            'ten_goi' => 'HISTORY EXPIRED',
            'thoi_han_ngay' => 1,
        ]);
        $kyHetHan = $this->taoDonVaSnapshotMembership($memberId, $goiHetHan['package'], $mocKichHoat);
        $this->xacNhanVaCapMembership($kyHetHan, $mocKichHoat);
        $usage = $this->taoUsageMembership($kyHetHan, $fixture['user']->getKey(), 'VAO_PHONG_TAP', $mocKichHoat);
        app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());

        CarbonImmutable::setTestNow($mocKichHoat->addDays(2));
        $token = $this->layTokenProfile($fixture);
        $response = $this->getJson('/api/membership', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.current', null)
            ->assertJsonCount(2, 'data.history');
        $cacTrangThai = collect($response->json('data.history'))->pluck('registration.status')->sort()->values()->all();
        $this->assertSame(['HET_HAN', 'HUY'], $cacTrangThai);
        $this->assertStringContainsString('HISTORY CANCELLED', $response->getContent());
        $this->assertStringContainsString('HISTORY EXPIRED', $response->getContent());
    }
}
