<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();

        foreach (['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'] as $maVaiTro) {
            Route::middleware(['auth:api', 'role:'.$maVaiTro])
                ->get('/api/__test/role/'.strtolower($maVaiTro), fn () => response()->json(['data' => 'OK']));
        }
        Route::middleware(['auth:api', 'role:ADMIN,PT'])
            ->get('/api/__test/role/multiple', fn () => response()->json(['data' => 'OK']));
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public static function cacVaiTroChinhThuc(): array
    {
        return [['MEMBER'], ['PT'], ['RECEPTIONIST'], ['ADMIN']];
    }

    #[DataProvider('cacVaiTroChinhThuc')]
    public function test_each_official_active_role_is_allowed_on_its_route(string $maVaiTro): void
    {
        $fixture = $this->taoNguoiDungAuth([$maVaiTro]);
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');

        $this->getJson('/api/__test/role/'.strtolower($maVaiTro), $this->bearer($rawToken))->assertOk();
    }

    public function test_authenticated_user_with_wrong_role_receives_403(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');

        $this->getJson('/api/__test/role/admin', $this->bearer($rawToken))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Không có quyền truy cập.']);
    }

    public function test_unauthenticated_role_request_receives_401(): void
    {
        $this->getJson('/api/__test/role/member')->assertUnauthorized();
    }

    public function test_revoking_role_changes_authorization_to_403_while_same_token_stays_valid(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
        $this->getJson('/api/__test/role/member', $this->bearer($rawToken))->assertOk();

        DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $fixture['user']->getKey())
            ->where('vai_tro_id', $fixture['role_ids']['MEMBER'])
            ->update(['thu_hoi_luc' => CarbonImmutable::now('UTC'), 'ngay_cap_nhat' => CarbonImmutable::now('UTC')]);

        $this->getJson('/api/auth/me', $this->bearer($rawToken))
            ->assertOk()
            ->assertJsonPath('data.roles', []);
        $this->getJson('/api/__test/role/member', $this->bearer($rawToken))->assertForbidden();
    }

    public function test_multiple_active_roles_are_accepted_when_any_allowed_role_matches(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER', 'PT']);
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');

        $this->getJson('/api/__test/role/multiple', $this->bearer($rawToken))->assertOk();
    }

    public function test_role_regrant_updates_same_assignment_and_authorization_follows_current_state(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $rawToken = (string) $this->dangNhapApi($fixture['user']->thu_dien_tu)->json('data.access_token');
        $phanQuyen = DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $fixture['user']->getKey())
            ->where('vai_tro_id', $fixture['role_ids']['MEMBER'])
            ->sole();
        $hienTai = CarbonImmutable::now('UTC');

        DB::table('phan_quyen_nguoi_dung')->where('id', $phanQuyen->id)->update([
            'thu_hoi_luc' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
        $this->getJson('/api/__test/role/member', $this->bearer($rawToken))->assertForbidden();

        DB::table('phan_quyen_nguoi_dung')->where('id', $phanQuyen->id)->update([
            'cap_luc' => $hienTai->addSecond(),
            'thu_hoi_luc' => null,
            'ngay_cap_nhat' => $hienTai->addSecond(),
        ]);
        $this->getJson('/api/__test/role/member', $this->bearer($rawToken))->assertOk();
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $fixture['user']->getKey())
            ->where('vai_tro_id', $fixture['role_ids']['MEMBER'])
            ->count());
        $this->assertSame($phanQuyen->id, DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $fixture['user']->getKey())
            ->where('vai_tro_id', $fixture['role_ids']['MEMBER'])
            ->value('id'));
    }
}
