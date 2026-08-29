<?php

namespace Tests\Concerns;

use App\Models\KyHanHoiVien;
use Carbon\CarbonImmutable;

trait CreatesGymFixtures
{
    /**
     * @param  array<string, mixed>  $fixture
     * @return array{term: KyHanHoiVien, package_id: int}
     */
    protected function taoMembershipGym(
        array $fixture,
        int $hoiVienId,
        ?CarbonImmutable $moc = null,
        bool $choPhepGym = true,
    ): array {
        $moc ??= CarbonImmutable::now('UTC');
        $goi = $this->taoGoiTapMembership($fixture, [], [
            'cho_phep_vao_phong_tap' => $choPhepGym,
        ]);
        $ky = $this->taoDonVaSnapshotMembership($hoiVienId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);

        return ['term' => $ky->fresh(), 'package_id' => (int) $goi['package']->getKey()];
    }
}
