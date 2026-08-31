<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class LastActiveAdminGuardTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        $hienTai = CarbonImmutable::now('UTC');
        $vaiTroAdminId = DB::table('vai_tro')->where('ma_vai_tro', 'ADMIN')->value('id');
        DB::table('phan_quyen_nguoi_dung')
            ->where('vai_tro_id', $vaiTroAdminId)
            ->whereNull('thu_hoi_luc')
            ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_last_active_admin_role_cannot_be_revoked(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');

        $this->deleteJson('/api/admin/accounts/'.$admin['user']->getKey().'/roles/ADMIN', [], $this->bearer($token))
            ->assertConflict()
            ->assertJsonPath('code', 'LAST_ACTIVE_ADMIN_PROTECTED');

        $this->assertNull(DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->where('vai_tro_id', $admin['role_ids']['ADMIN'])
            ->value('thu_hoi_luc'));
    }

    public function test_last_active_admin_account_cannot_be_locked_or_deactivated(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');

        foreach (['BI_KHOA', 'NGUNG_HOAT_DONG'] as $trangThai) {
            $this->patchJson(
                '/api/admin/accounts/'.$admin['user']->getKey().'/status',
                ['status' => $trangThai],
                $this->bearer($token),
            )->assertConflict()->assertJsonPath('code', 'LAST_ACTIVE_ADMIN_PROTECTED');
            $this->assertSame('HOAT_DONG', DB::table('nguoi_dung')->where('id', $admin['user']->getKey())->value('trang_thai'));
        }
    }

    public function test_one_admin_can_disable_another_active_admin(): void
    {
        $actor = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['ADMIN']);
        $token = (string) $this->dangNhapApi($actor['user']->thu_dien_tu)->json('data.access_token');

        $this->patchJson(
            '/api/admin/accounts/'.$target['user']->getKey().'/status',
            ['status' => 'BI_KHOA'],
            $this->bearer($token),
        )->assertOk()->assertJsonPath('data.status', 'BI_KHOA');
    }
}
