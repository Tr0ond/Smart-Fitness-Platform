<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class TrainerProfileTest extends TestCase
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

    public function test_trainer_reads_and_updates_only_safe_own_fields(): void
    {
        $fixture = $this->taoNguoiDungAuth(['PT']);
        $profileId = $this->taoHoSoHuanLuyenVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->getJson('/api/profile/trainer', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.status', 'HOAT_DONG');

        $this->patchJson('/api/profile/trainer', [
            'introduction' => 'Giới thiệu mới',
            'specialties' => 'Sức mạnh, phục hồi',
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.introduction', 'Giới thiệu mới')
            ->assertJsonPath('data.specialties', 'Sức mạnh, phục hồi')
            ->assertJsonPath('data.status', 'HOAT_DONG');
    }

    public function test_trainer_route_enforces_role_revocation_and_missing_profile(): void
    {
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($member);
        $memberToken = $this->layTokenProfile($member);
        $this->getJson('/api/profile/trainer', $this->bearer($memberToken))->assertForbidden();

        $pt = $this->taoNguoiDungAuth(['PT']);
        $ptToken = $this->layTokenProfile($pt);
        $this->getJson('/api/profile/trainer', $this->bearer($ptToken))->assertNotFound();

        $this->taoHoSoHuanLuyenVien($pt);
        $this->thuHoiVaiTroProfile($pt, 'PT');
        $this->patchJson('/api/profile/trainer', ['introduction' => 'Không được ghi'], $this->bearer($ptToken))
            ->assertForbidden();
    }

    public function test_trainer_a_cannot_redirect_update_to_trainer_b_or_change_managed_state(): void
    {
        $ptA = $this->taoNguoiDungAuth(['PT']);
        $ptB = $this->taoNguoiDungAuth(['PT']);
        $profileA = $this->taoHoSoHuanLuyenVien($ptA, ['gioi_thieu' => 'A trước']);
        $profileB = $this->taoHoSoHuanLuyenVien($ptB, ['gioi_thieu' => 'B trước']);
        $tokenA = $this->layTokenProfile($ptA);
        $maA = DB::table('ho_so_huan_luyen_vien')->where('id', $profileA)->value('ma_huan_luyen_vien');
        $this->assertNotSame($ptA['user']->getKey(), $profileA);
        $this->assertNotSame($ptB['user']->getKey(), $profileB);

        $this->patchJson('/api/profile/trainer', [
            'id' => $profileB,
            'user_id' => $ptB['user']->getKey(),
            'nguoi_dung_id' => $ptB['user']->getKey(),
            'trainer_code' => 'CHIEM_QUYEN',
            'status' => 'NGUNG_NHAN_PHAN_CONG',
            'introduction' => 'A sau',
        ], $this->bearer($tokenA))
            ->assertOk()
            ->assertJsonPath('data.id', $profileA)
            ->assertJsonPath('data.introduction', 'A sau')
            ->assertJsonPath('data.status', 'HOAT_DONG');

        $sauA = DB::table('ho_so_huan_luyen_vien')->where('id', $profileA)->sole();
        $sauB = DB::table('ho_so_huan_luyen_vien')->where('id', $profileB)->sole();
        $this->assertSame($maA, $sauA->ma_huan_luyen_vien);
        $this->assertSame('HOAT_DONG', $sauA->trang_thai);
        $this->assertSame('B trước', $sauB->gioi_thieu);
    }

    public function test_trainer_profile_validation_rejects_overlong_values_without_write(): void
    {
        $fixture = $this->taoNguoiDungAuth(['PT']);
        $profileId = $this->taoHoSoHuanLuyenVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->patchJson('/api/profile/trainer', [
            'specialties' => str_repeat('x', 256),
        ], $this->bearer($token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['specialties']);

        $this->assertSame(
            'Sức mạnh',
            DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->value('chuyen_mon'),
        );
    }
}
