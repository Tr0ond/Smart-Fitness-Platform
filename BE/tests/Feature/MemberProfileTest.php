<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class MemberProfileTest extends TestCase
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

    public function test_member_reads_and_updates_own_profile_and_increments_version_once(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $profileId = $this->taoHoSoHoiVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->getJson('/api/profile/member', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.member_code', DB::table('ho_so_hoi_vien')->where('id', $profileId)->value('ma_hoi_vien'));

        $this->patchJson('/api/profile/member', [
            'birth_date' => '1999-12-31',
            'gender' => 'NU',
            'training_goal' => 'TANG_SUC_BEN',
            'training_experience' => 'CO_BAN',
            'desired_training_days' => 5,
            'session_duration_minutes' => 75,
        ], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.birth_date', '1999-12-31')
            ->assertJsonPath('data.desired_training_days', 5)
            ->assertJsonPath('data.profile_version', 2);

        $hoSo = DB::table('ho_so_hoi_vien')->where('id', $profileId)->sole();
        $this->assertSame('TANG_SUC_BEN', $hoSo->muc_tieu_tap_luyen);
        $this->assertSame(75, (int) $hoSo->thoi_luong_moi_buoi_phut);
        $this->assertSame(2, (int) $hoSo->phien_ban_ho_so);
    }

    public function test_member_profile_rejects_invalid_date_ranges_and_lengths_atomically(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $profileId = $this->taoHoSoHoiVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->patchJson('/api/profile/member', [
            'birth_date' => 'not-a-date',
            'gender' => str_repeat('g', 31),
            'training_goal' => str_repeat('m', 101),
            'training_experience' => str_repeat('e', 51),
            'desired_training_days' => 8,
            'session_duration_minutes' => 0,
        ], $this->bearer($token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'birth_date',
                'gender',
                'training_goal',
                'training_experience',
                'desired_training_days',
                'session_duration_minutes',
            ]);

        $hoSo = DB::table('ho_so_hoi_vien')->where('id', $profileId)->sole();
        $this->assertSame('GIAM_MO', $hoSo->muc_tieu_tap_luyen);
        $this->assertSame(1, (int) $hoSo->phien_ban_ho_so);
    }

    public function test_member_route_enforces_role_revocation_and_missing_profile(): void
    {
        $pt = $this->taoNguoiDungAuth(['PT']);
        $this->taoHoSoHuanLuyenVien($pt);
        $ptToken = $this->layTokenProfile($pt);
        $this->getJson('/api/profile/member', $this->bearer($ptToken))->assertForbidden();

        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $memberToken = $this->layTokenProfile($member);
        $this->getJson('/api/profile/member', $this->bearer($memberToken))->assertNotFound();

        $this->taoHoSoHoiVien($member);
        $this->thuHoiVaiTroProfile($member, 'MEMBER');
        $this->getJson('/api/profile/member', $this->bearer($memberToken))->assertForbidden();
    }

    public function test_member_a_cannot_redirect_update_to_member_b_or_mass_assign_system_fields(): void
    {
        $memberA = $this->taoNguoiDungAuth(['MEMBER']);
        $memberB = $this->taoNguoiDungAuth(['MEMBER']);
        $profileA = $this->taoHoSoHoiVien($memberA, ['muc_tieu_tap_luyen' => 'A_TRUOC']);
        $profileB = $this->taoHoSoHoiVien($memberB, ['muc_tieu_tap_luyen' => 'B_TRUOC']);
        $tokenA = $this->layTokenProfile($memberA);
        $maA = DB::table('ho_so_hoi_vien')->where('id', $profileA)->value('ma_hoi_vien');
        $this->assertNotSame($memberA['user']->getKey(), $profileA);
        $this->assertNotSame($memberB['user']->getKey(), $profileB);

        $this->patchJson('/api/profile/member', [
            'id' => $profileB,
            'user_id' => $memberB['user']->getKey(),
            'nguoi_dung_id' => $memberB['user']->getKey(),
            'member_code' => 'CHIEM_QUYEN',
            'ma_hoi_vien' => 'CHIEM_QUYEN',
            'profile_version' => 999,
            'plan_change_marker' => 999,
            'training_goal' => 'A_SAU',
        ], $this->bearer($tokenA))
            ->assertOk()
            ->assertJsonPath('data.id', $profileA)
            ->assertJsonPath('data.training_goal', 'A_SAU')
            ->assertJsonPath('data.profile_version', 2);

        $sauA = DB::table('ho_so_hoi_vien')->where('id', $profileA)->sole();
        $sauB = DB::table('ho_so_hoi_vien')->where('id', $profileB)->sole();
        $this->assertSame($maA, $sauA->ma_hoi_vien);
        $this->assertSame(0, (int) $sauA->moc_thay_doi_ke_hoach);
        $this->assertSame('B_TRUOC', $sauB->muc_tieu_tap_luyen);
        $this->assertSame(1, (int) $sauB->phien_ban_ho_so);
    }
}
