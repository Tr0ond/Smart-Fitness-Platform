<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class MemberPreferencesTest extends TestCase
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

    public function test_availability_replace_set_is_sorted_versioned_and_idempotent(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $profileId = $this->taoHoSoHoiVien($fixture);
        $token = $this->layTokenProfile($fixture);

        $this->putJson('/api/profile/member/availability', ['days' => [7, 2, 4]], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.days', [2, 4, 7])
            ->assertJsonPath('data.profile_version', 2);
        $this->assertSame(3, DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $profileId)->count());

        $this->putJson('/api/profile/member/availability', ['days' => [4, 7, 2]], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.days', [2, 4, 7])
            ->assertJsonPath('data.profile_version', 2);
        $this->getJson('/api/profile/member/availability', $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.days', [2, 4, 7]);
    }

    public function test_availability_can_be_cleared_and_invalid_replace_rolls_back(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $profileId = $this->taoHoSoHoiVien($fixture);
        $token = $this->layTokenProfile($fixture);
        $this->putJson('/api/profile/member/availability', ['days' => [2, 8]], $this->bearer($token))->assertOk();

        $this->putJson('/api/profile/member/availability', ['days' => [2, 2, 9]], $this->bearer($token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['days.1', 'days.2']);
        $this->assertSame([2, 8], DB::table('ngay_ranh_hoi_vien')
            ->where('hoi_vien_id', $profileId)->orderBy('thu_trong_tuan')
            ->pluck('thu_trong_tuan')->map(fn ($day) => (int) $day)->all());
        $this->assertSame(2, (int) DB::table('ho_so_hoi_vien')->where('id', $profileId)->value('phien_ban_ho_so'));

        $this->putJson('/api/profile/member/availability', ['days' => []], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.days', [])
            ->assertJsonPath('data.profile_version', 3);
    }

    public function test_equipment_replace_set_returns_master_data_and_is_idempotent(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $profileId = $this->taoHoSoHoiVien($fixture);
        $dungCuA = $this->taoDungCuProfile(['ten_dung_cu' => 'Tạ đơn']);
        $dungCuB = $this->taoDungCuProfile(['ten_dung_cu' => 'Dây kháng lực']);
        $token = $this->layTokenProfile($fixture);

        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => [$dungCuB, $dungCuA]], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.equipment.0.id', min($dungCuA, $dungCuB))
            ->assertJsonPath('data.equipment.1.id', max($dungCuA, $dungCuB))
            ->assertJsonPath('data.profile_version', 2);
        $this->assertSame(2, DB::table('dung_cu_hoi_vien')->where('hoi_vien_id', $profileId)->count());

        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => [$dungCuA, $dungCuB]], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.profile_version', 2);
        $this->getJson('/api/profile/member/equipment', $this->bearer($token))
            ->assertOk()
            ->assertJsonCount(2, 'data.equipment');

        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => []], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.equipment', [])
            ->assertJsonPath('data.profile_version', 3);
        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => []], $this->bearer($token))
            ->assertOk()
            ->assertJsonPath('data.profile_version', 3);
    }

    public function test_unknown_or_duplicate_equipment_is_rejected_without_partial_replace(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $profileId = $this->taoHoSoHoiVien($fixture);
        $dungCu = $this->taoDungCuProfile();
        $token = $this->layTokenProfile($fixture);
        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => [$dungCu]], $this->bearer($token))->assertOk();

        $this->putJson('/api/profile/member/equipment', ['equipment_ids' => [$dungCu, $dungCu, 2147483647]], $this->bearer($token))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['equipment_ids.1', 'equipment_ids.2']);

        $this->assertSame([$dungCu], DB::table('dung_cu_hoi_vien')
            ->where('hoi_vien_id', $profileId)
            ->pluck('dung_cu_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(2, (int) DB::table('ho_so_hoi_vien')->where('id', $profileId)->value('phien_ban_ho_so'));
    }

    public function test_member_preferences_are_owner_derived_and_cannot_target_member_b(): void
    {
        $memberA = $this->taoNguoiDungAuth(['MEMBER']);
        $memberB = $this->taoNguoiDungAuth(['MEMBER']);
        $profileA = $this->taoHoSoHoiVien($memberA);
        $profileB = $this->taoHoSoHoiVien($memberB);
        $dungCu = $this->taoDungCuProfile();
        $tokenA = $this->layTokenProfile($memberA);

        $this->putJson('/api/profile/member/availability', [
            'days' => [3],
            'member_id' => $profileB,
            'hoi_vien_id' => $profileB,
        ], $this->bearer($tokenA))->assertOk();
        $this->putJson('/api/profile/member/equipment', [
            'equipment_ids' => [$dungCu],
            'member_id' => $profileB,
            'hoi_vien_id' => $profileB,
        ], $this->bearer($tokenA))->assertOk();

        $this->assertSame(1, DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $profileA)->count());
        $this->assertSame(0, DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $profileB)->count());
        $this->assertSame(1, DB::table('dung_cu_hoi_vien')->where('hoi_vien_id', $profileA)->count());
        $this->assertSame(0, DB::table('dung_cu_hoi_vien')->where('hoi_vien_id', $profileB)->count());
    }
}
