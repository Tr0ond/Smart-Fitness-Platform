<?php

namespace Tests\Concerns;

use App\Models\KyHanHoiVien;
use App\Services\MembershipActivationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

trait CreatesPtFixtures
{
    /**
     * Tạo actor và hai cặp profile có ID khác user ID để bắt lỗi nhầm định danh.
     *
     * @return array<string, mixed>
     */
    protected function taoBoPtFixtures(): array
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $memberA = $this->taoNguoiDungAuth(['MEMBER']);
        $memberB = $this->taoNguoiDungAuth(['MEMBER']);
        $ptA = $this->taoNguoiDungAuth(['PT']);
        $ptB = $this->taoNguoiDungAuth(['PT']);

        return [
            'admin' => $admin,
            'member_a' => $memberA,
            'member_b' => $memberB,
            'pt_a' => $ptA,
            'pt_b' => $ptB,
            'member_a_id' => $this->taoHoSoHoiVien($memberA),
            'member_b_id' => $this->taoHoSoHoiVien($memberB),
            'pt_a_id' => $this->taoHoSoHuanLuyenVien($ptA),
            'pt_b_id' => $this->taoHoSoHuanLuyenVien($ptB),
        ];
    }

    protected function taoPhanCongPt(array $fixture, int $memberId, int $trainerId, ?CarbonImmutable $start = null, ?CarbonImmutable $end = null): int
    {
        $start ??= CarbonImmutable::now('UTC')->subMinute();
        $now = CarbonImmutable::now('UTC');

        return DB::table('phan_cong_huan_luyen_vien')->insertGetId([
            'hoi_vien_id' => $memberId,
            'huan_luyen_vien_id' => $trainerId,
            'nguoi_phan_cong_id' => $fixture['admin']['user']->getKey(),
            'ngay_bat_dau' => $start,
            'ngay_ket_thuc' => $end,
            'ly_do_ket_thuc' => null,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
    }

    protected function taoMembershipPt(array $fixture, int $memberId, array $benefitOverrides = [], bool $activate = false): KyHanHoiVien
    {
        $goi = $this->taoGoiTapMembership($fixture['admin'], [], array_merge([
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => false,
            'so_buoi_huan_luyen_vien' => 1,
        ], $benefitOverrides));
        $ky = $this->taoDonVaSnapshotMembership($memberId, $goi['package']);
        $this->xacNhanVaCapMembership($ky);
        if ($activate) {
            $usage = $this->taoUsageMembership($ky->fresh(), $fixture['member_a']['user']->getKey(), 'BUOI_HUAN_LUYEN');
            app(MembershipActivationService::class)->kichHoatNeuCan($usage->getKey());
        }

        return $ky->fresh();
    }
}
