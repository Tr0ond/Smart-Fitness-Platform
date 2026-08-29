<?php

namespace Tests\Feature;

use App\Models\HoSoHoiVien;
use App\Services\Ai\AiCandidateRuleEngine;
use Tests\Concerns\CreatesAiFixtures;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class AiCandidateRuleEngineTest extends TestCase
{
    use CreatesAiFixtures;
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

    public function test_equipment_relations_are_and_no_equipment_is_allowed_and_order_is_stable(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $a = $this->taoDungCuAi();
        $b = $this->taoDungCuAi();
        $canHai = $this->taoBaiTapAi($fixture, [$a, $b]);
        $khongCan = $this->taoBaiTapAi($fixture);
        $this->ganDungCuAi($memberId, $a);

        $engine = app(AiCandidateRuleEngine::class);
        $request = ['loai_yeu_cau' => 'TAO_KE_HOACH', 'prompt' => 'Tạo lịch tập.'];
        $contextMot = $engine->taoNguCanh(HoSoHoiVien::query()->findOrFail($memberId), $request);
        $idsMot = array_column($contextMot['bai_tap_ung_vien'], 'id');
        $this->assertNotContains($canHai, $idsMot);
        $this->assertContains($khongCan, $idsMot);

        $this->ganDungCuAi($memberId, $b);
        $contextHai = $engine->taoNguCanh(HoSoHoiVien::query()->findOrFail($memberId), $request);
        $idsHai = array_column($contextHai['bai_tap_ung_vien'], 'id');
        $this->assertContains($canHai, $idsHai);
        $this->assertSame($idsHai, collect($idsHai)->sort()->values()->all());
        $this->assertSame($idsHai, array_column(
            $engine->taoNguCanh(HoSoHoiVien::query()->findOrFail($memberId), $request)['bai_tap_ung_vien'],
            'id',
        ));
    }

    public function test_inactive_exercise_is_never_a_candidate(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $inactive = $this->taoBaiTapAi($fixture, [], ['trang_thai' => 'NGUNG_SU_DUNG']);
        $active = $this->taoBaiTapAi($fixture);
        $context = app(AiCandidateRuleEngine::class)->taoNguCanh(
            HoSoHoiVien::query()->findOrFail($memberId),
            ['loai_yeu_cau' => 'TAO_KE_HOACH', 'prompt' => 'Tạo lịch tập.'],
        );

        $ids = array_column($context['bai_tap_ung_vien'], 'id');
        $this->assertNotContains($inactive, $ids);
        $this->assertContains($active, $ids);
    }
}
