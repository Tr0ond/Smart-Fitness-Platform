<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;
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

    public function test_all_profile_routes_require_authentication(): void
    {
        foreach ([
            ['GET', '/api/profile'],
            ['PATCH', '/api/profile'],
            ['GET', '/api/profile/member'],
            ['PATCH', '/api/profile/member'],
            ['GET', '/api/profile/member/availability'],
            ['PUT', '/api/profile/member/availability'],
            ['GET', '/api/profile/member/equipment'],
            ['PUT', '/api/profile/member/equipment'],
            ['GET', '/api/profile/trainer'],
            ['PATCH', '/api/profile/trainer'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
    }

    public function test_role_aware_aggregate_includes_only_existing_profiles_for_active_roles(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER', 'PT']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $trainerId = $this->taoHoSoHuanLuyenVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->getJson('/api/profile', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.user.id', $fixture['user']->getKey())
            ->assertJsonPath('data.active_roles', ['MEMBER', 'PT'])
            ->assertJsonPath('data.profiles.member.id', $memberId)
            ->assertJsonPath('data.profiles.trainer.id', $trainerId)
            ->assertJsonMissingPath('data.user.mat_khau_bam')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.user.token');

        $chiAdmin = $this->taoNguoiDungAuth(['ADMIN']);
        $adminToken = $this->layTokenProfile($chiAdmin);
        $this->getJson('/api/profile', $this->bearer($adminToken))
            ->assertOk()
            ->assertJsonPath('data.profiles', []);
    }

    public function test_current_account_update_uses_allowlist_and_keeps_sensitive_fields_unchanged(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $token = $this->layTokenProfile($fixture);
        $truoc = DB::table('nguoi_dung')->where('id', $fixture['user']->getKey())->sole();

        $this->patchJson('/api/profile', [
            'name' => 'Tên an toàn',
            'phone' => '0909000111',
            'avatar_url' => 'avatars/current-user.png',
            'email' => 'attacker@example.com',
            'status' => 'BI_KHOA',
            'branch_id' => 999999,
            'password' => 'plain-text',
            'mat_khau_bam' => 'plain-text',
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.name', 'Tên an toàn')
            ->assertJsonPath('data.phone', '0909000111')
            ->assertJsonPath('data.avatar_url', 'avatars/current-user.png')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.mat_khau_bam');

        $sau = DB::table('nguoi_dung')->where('id', $fixture['user']->getKey())->sole();
        $this->assertSame('Tên an toàn', $sau->ho_ten);
        $this->assertSame($truoc->thu_dien_tu, $sau->thu_dien_tu);
        $this->assertSame($truoc->trang_thai, $sau->trang_thai);
        $this->assertSame($truoc->chi_nhanh_id, $sau->chi_nhanh_id);
        $this->assertSame($truoc->mat_khau_bam, $sau->mat_khau_bam);
    }

    public function test_profile_updates_validate_lengths_without_partial_write(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $token = $this->layTokenProfile($fixture);

        $this->patchJson('/api/profile', [
            'name' => str_repeat('a', 151),
            'phone' => str_repeat('1', 21),
            'avatar_url' => str_repeat('x', 501),
        ], $this->bearer($token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone', 'avatar_url']);

        $fixture['user']->refresh();
        $this->assertSame('Nguoi dung test auth', $fixture['user']->ho_ten);
        $this->assertNull($fixture['user']->so_dien_thoai);
    }

    public function test_profile_reads_and_updates_do_not_activate_membership(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $membership = $this->taoMembershipChoKichHoat($fixture);
        $token = $this->layTokenProfile($fixture);
        $usageCount = DB::table('su_dung_quyen_loi')->count();

        $this->getJson('/api/profile', $this->bearer($token))->assertOk();
        $this->patchJson('/api/profile', ['name' => 'Profile không kích hoạt'], $this->bearer($token))->assertOk();
        $this->getJson('/api/profile/member', $this->bearer($token))->assertOk();
        $this->patchJson('/api/profile/member', ['training_goal' => 'SUC_KHOE'], $this->bearer($token))->assertOk();
        $this->putJson('/api/profile/member/availability', ['days' => [2, 4]], $this->bearer($token))->assertOk();

        $dangKy = DB::table('dang_ky_goi_tap')->where('id', $membership['dang_ky_id'])->sole();
        $this->assertSame('CHO_KICH_HOAT', $dangKy->trang_thai);
        $this->assertNull($dangKy->lan_su_dung_dau_tien_id);
        $this->assertNull($dangKy->ngay_bat_dau);
        $this->assertSame($usageCount, DB::table('su_dung_quyen_loi')->count());
    }
}
