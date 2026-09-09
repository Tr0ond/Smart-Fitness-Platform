<?php

namespace Tests\Feature;

use App\Models\NhatKyHeThong;
use App\Services\Admin\RoleManagementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class AdminAccountRoleApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.creating: '.NhatKyHeThong::class);
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_admin_can_list_search_and_read_safe_paginated_account_dtos(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN'], thuDienTu: 'admin.accounts@example.com');
        $target = $this->taoNguoiDungAuth(['MEMBER'], thuDienTu: 'member.search@example.com');
        $target['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.31')
            ->json('data.access_token');

        $response = $this->getJson('/api/admin/accounts?search=MEMBER.SEARCH&role=MEMBER&per_page=5', $this->bearer($token));
        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id', $target['user']->getKey())
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonMissingPath('data.items.0.mat_khau_bam')
            ->assertJsonMissingPath('data.items.0.tokens')
            ->assertJsonMissingPath('data.items.0.reset_tokens');

        $detail = $this->getJson('/api/admin/accounts/'.$target['user']->getKey(), $this->bearer($token));
        $detail->assertOk()->assertJsonPath('data.email', 'member.search@example.com');
        $body = (string) $detail->getContent();
        $this->assertStringNotContainsString((string) $target['user']->mat_khau_bam, $body);
        $this->assertStringNotContainsString('ma_bam_the', $body);
        $this->assertStringNotContainsString('ma_bam_xac_nhan', $body);
    }

    public function test_admin_account_list_and_detail_are_scoped_to_actor_branch(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN'], thuDienTu: 'admin.branch-scope@example.com');
        $sameBranch = $this->taoNguoiDungAuth(['MEMBER'], thuDienTu: 'member.same-branch@example.com');
        $sameBranch['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $foreign = $this->taoNguoiDungAuth(['MEMBER'], thuDienTu: 'member.foreign-branch@example.com');
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.32')
            ->json('data.access_token');

        $list = $this->getJson('/api/admin/accounts?per_page=100', $this->bearer($token));
        $list->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonFragment(['id' => $admin['user']->getKey()])
            ->assertJsonFragment(['id' => $sameBranch['user']->getKey()]);
        $listedIds = collect($list->json('data.items'))->pluck('id')->all();
        $this->assertNotContains($foreign['user']->getKey(), $listedIds);

        $this->getJson('/api/admin/accounts/'.$sameBranch['user']->getKey(), $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.id', $sameBranch['user']->getKey());
        $this->getJson('/api/admin/accounts/'.$foreign['user']->getKey(), $this->bearer($token))
            ->assertNotFound()
            ->assertJsonPath('code', 'ACCOUNT_NOT_FOUND');
    }

    public function test_non_admin_roles_and_unauthenticated_clients_are_blocked_from_all_admin_account_routes(): void
    {
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $routes = [
            ['GET', '/api/admin/accounts'],
            ['GET', '/api/admin/accounts/'.$target['user']->getKey()],
            ['PATCH', '/api/admin/accounts/'.$target['user']->getKey().'/status'],
            ['PUT', '/api/admin/accounts/'.$target['user']->getKey().'/roles/PT'],
            ['DELETE', '/api/admin/accounts/'.$target['user']->getKey().'/roles/PT'],
            ['GET', '/api/admin/accounts/'.$target['user']->getKey().'/trainer-profile'],
        ];

        foreach ($routes as [$method, $uri]) {
            $this->json($method, $uri, $method === 'PATCH' ? ['status' => 'BI_KHOA'] : [])->assertUnauthorized();
        }

        foreach (['MEMBER', 'PT', 'RECEPTIONIST'] as $role) {
            $actor = $this->taoNguoiDungAuth([$role]);
            $token = (string) $this->dangNhapApi(
                $actor['user']->thu_dien_tu,
                diaChiIp: '192.0.2.'.random_int(40, 200),
            )->json('data.access_token');
            foreach ($routes as [$method, $uri]) {
                $this->json(
                    $method,
                    $uri,
                    $method === 'PATCH' ? ['status' => 'BI_KHOA'] : [],
                    $this->bearer($token),
                )->assertForbidden();
            }
        }
    }

    public function test_admin_status_mutation_uses_exact_states_never_deletes_and_revokes_sessions(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.51')
            ->json('data.access_token');
        $targetToken = (string) $this->dangNhapApi($target['user']->thu_dien_tu, diaChiIp: '192.0.2.52')
            ->json('data.access_token');

        $this->patchJson(
            '/api/admin/accounts/'.$target['user']->getKey().'/status',
            ['status' => 'BI_KHOA'],
            $this->bearer($adminToken),
        )->assertOk()->assertJsonPath('data.status', 'BI_KHOA');
        $this->assertDatabaseHas('nguoi_dung', ['id' => $target['user']->getKey(), 'trang_thai' => 'BI_KHOA']);
        $this->getJson('/api/auth/me', $this->bearer($targetToken))->assertUnauthorized();
        $this->assertNotNull(DB::table('the_truy_cap')->where('ma_bam_the', hash('sha256', $targetToken))->value('thu_hoi_luc'));

        $this->patchJson(
            '/api/admin/accounts/'.$target['user']->getKey().'/status',
            ['status' => 'HOAT_DONG'],
            $this->bearer($adminToken),
        )->assertOk()->assertJsonPath('data.status', 'HOAT_DONG');
        $this->getJson('/api/auth/me', $this->bearer($targetToken))->assertUnauthorized();
        $this->assertSame(1, DB::table('nguoi_dung')->where('id', $target['user']->getKey())->count());

        $this->patchJson(
            '/api/admin/accounts/'.$target['user']->getKey().'/status',
            ['status' => 'DELETED'],
            $this->bearer($adminToken),
        )->assertUnprocessable();
    }

    public function test_role_grant_revoke_and_regrant_preserve_same_row_and_write_one_audit_per_transition(): void
    {
        $hienTai = CarbonImmutable::parse('2026-08-31 11:00:00.000001', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.61')
            ->json('data.access_token');
        $uri = '/api/admin/accounts/'.$target['user']->getKey().'/roles/RECEPTIONIST';

        $grant = $this->putJson($uri, [], $this->bearer($token));
        $grant->assertOk()
            ->assertJsonPath('data.transition', 'GRANTED')
            ->assertJsonPath('data.changed', true);
        $assignmentId = (int) $grant->json('data.assignment_id');
        $createdAt = DB::table('phan_quyen_nguoi_dung')->where('id', $assignmentId)->value('ngay_tao');
        $this->putJson($uri, [], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.transition', 'UNCHANGED')
            ->assertJsonPath('data.changed', false);

        CarbonImmutable::setTestNow($hienTai->addSecond());
        $this->deleteJson($uri, [], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.transition', 'REVOKED');
        $this->deleteJson($uri, [], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.transition', 'UNCHANGED');

        CarbonImmutable::setTestNow($hienTai->addSeconds(2));
        $regrant = $this->putJson($uri, [], $this->bearer($token));
        $regrant->assertOk()
            ->assertJsonPath('data.transition', 'REGRANTED')
            ->assertJsonPath('data.assignment_id', $assignmentId)
            ->assertJsonPath('data.active', true);

        $row = DB::table('phan_quyen_nguoi_dung')->where('id', $assignmentId)->sole();
        $this->assertSame($createdAt, $row->ngay_tao);
        $this->assertSame($admin['user']->getKey(), (int) $row->nguoi_cap_id);
        $this->assertNull($row->thu_hoi_luc);
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $target['user']->getKey())
            ->where('vai_tro_id', $target['role_ids']['RECEPTIONIST'] ?? DB::table('vai_tro')->where('ma_vai_tro', 'RECEPTIONIST')->value('id'))
            ->count());
        $audits = DB::table('nhat_ky_he_thong')
            ->where('loai_doi_tuong', 'PHAN_QUYEN_NGUOI_DUNG')
            ->where('dinh_danh_doi_tuong', $assignmentId)
            ->orderBy('id')
            ->get();
        $this->assertSame(['CAP_VAI_TRO', 'THU_HOI_VAI_TRO', 'CAP_LAI_VAI_TRO'], $audits->pluck('hanh_dong')->all());
        $this->assertSame([$admin['user']->getKey()], $audits->pluck('nguoi_thuc_hien_id')->unique()->values()->all());

    }

    public function test_role_revocation_affects_existing_token_immediately_and_regrant_does_not_create_resource_scope(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['PT']);
        $this->taoHoSoHuanLuyenVien($target['user']->getKey());
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.71')
            ->json('data.access_token');
        $targetToken = (string) $this->dangNhapApi($target['user']->thu_dien_tu, diaChiIp: '192.0.2.72')
            ->json('data.access_token');
        $uri = '/api/admin/accounts/'.$target['user']->getKey().'/roles/PT';

        $this->getJson('/api/pt/members', $this->bearer($targetToken))
            ->assertOk()
            ->assertExactJson(['data' => []]);
        $this->deleteJson($uri, [], $this->bearer($adminToken))->assertOk();
        $this->getJson('/api/pt/members', $this->bearer($targetToken))
            ->assertForbidden()
            ->assertJsonMissingPath('code');
        $this->putJson($uri, [], $this->bearer($adminToken))->assertOk();
        $this->getJson('/api/pt/members', $this->bearer($targetToken))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_regranting_pt_role_does_not_revive_an_ended_assignment(): void
    {
        $now = CarbonImmutable::parse('2026-08-31 12:00:00.000000', 'UTC');
        CarbonImmutable::setTestNow($now);
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $trainer = $this->taoNguoiDungAuth(['PT']);
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $trainerProfileId = DB::table('ho_so_huan_luyen_vien')->insertGetId([
            'nguoi_dung_id' => $trainer['user']->getKey(),
            'ma_huan_luyen_vien' => 'PT_ENDED_'.strtoupper(bin2hex(random_bytes(4))),
            'gioi_thieu' => null,
            'chuyen_mon' => null,
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $memberProfileId = DB::table('ho_so_hoi_vien')->insertGetId([
            'nguoi_dung_id' => $member['user']->getKey(),
            'ma_hoi_vien' => 'HV_ENDED_'.strtoupper(bin2hex(random_bytes(4))),
            'ngay_sinh' => null,
            'gioi_tinh' => null,
            'muc_tieu_tap_luyen' => null,
            'kinh_nghiem_tap_luyen' => null,
            'so_ngay_tap_mong_muon' => null,
            'thoi_luong_moi_buoi_phut' => null,
            'phien_ban_ho_so' => 1,
            'moc_thay_doi_ke_hoach' => 0,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
        $assignmentId = DB::table('phan_cong_huan_luyen_vien')->insertGetId([
            'hoi_vien_id' => $memberProfileId,
            'huan_luyen_vien_id' => $trainerProfileId,
            'nguoi_phan_cong_id' => $admin['user']->getKey(),
            'ngay_bat_dau' => $now->subDays(10),
            'ngay_ket_thuc' => $now->subDay(),
            'ly_do_ket_thuc' => 'Đã kết thúc trước khi cấp lại Role.',
            'ngay_tao' => $now->subDays(10),
            'ngay_cap_nhat' => $now->subDay(),
        ]);
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.73')
            ->json('data.access_token');
        $trainerToken = (string) $this->dangNhapApi($trainer['user']->thu_dien_tu, diaChiIp: '192.0.2.74')
            ->json('data.access_token');
        $roleUri = '/api/admin/accounts/'.$trainer['user']->getKey().'/roles/PT';

        $this->getJson('/api/pt/members', $this->bearer($trainerToken))->assertOk()->assertExactJson(['data' => []]);
        $this->deleteJson($roleUri, [], $this->bearer($adminToken))->assertOk();
        $this->putJson($roleUri, [], $this->bearer($adminToken))->assertOk();
        $this->getJson('/api/pt/members', $this->bearer($trainerToken))->assertOk()->assertExactJson(['data' => []]);
        $assignment = DB::table('phan_cong_huan_luyen_vien')->where('id', $assignmentId)->sole();
        $this->assertSame($now->subDay()->format('Y-m-d H:i:s.u'), $assignment->ngay_ket_thuc);
    }

    public function test_role_change_and_audit_roll_back_together_when_audit_fails(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHuanLuyenVien($target['user']->getKey());
        $ptRoleId = (int) DB::table('vai_tro')->where('ma_vai_tro', 'PT')->value('id');
        Event::listen('eloquent.creating: '.NhatKyHeThong::class, static function (): never {
            throw new RuntimeException('Forced audit failure');
        });

        try {
            app(RoleManagementService::class)->gan($admin['user'], $target['user']->getKey(), 'PT');
            $this->fail('Expected audit failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('phan_quyen_nguoi_dung', [
            'nguoi_dung_id' => $target['user']->getKey(),
            'vai_tro_id' => $ptRoleId,
        ]);
        $this->assertSame(0, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'CAP_VAI_TRO')->count());
    }

    public function test_direct_pt_role_grant_requires_trainer_profile_onboarding(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');

        $this->putJson(
            '/api/admin/accounts/'.$target['user']->getKey().'/roles/PT',
            [],
            $this->bearer($token),
        )->assertConflict()->assertJsonPath('code', 'TRAINER_PROFILE_REQUIRED');
        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('nguoi_dung_id', $target['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
    }

    private function taoHoSoHuanLuyenVien(int $nguoiDungId): int
    {
        $hienTai = CarbonImmutable::now('UTC');

        return DB::table('ho_so_huan_luyen_vien')->insertGetId([
            'nguoi_dung_id' => $nguoiDungId,
            'ma_huan_luyen_vien' => 'PT_ROLE_'.strtoupper(bin2hex(random_bytes(5))),
            'gioi_thieu' => null,
            'chuyen_mon' => null,
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
    }
}
