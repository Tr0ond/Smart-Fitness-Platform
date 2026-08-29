<?php

namespace Tests\Concerns;

use App\Models\KyHanHoiVien;
use App\Services\MembershipActivationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesAiFixtures
{
    /** @param array<string, mixed> $profileOverrides */
    protected function taoHoiVienAi(array $fixture, array $profileOverrides = []): int
    {
        $hoiVienId = $this->taoHoSoHoiVien($fixture, array_merge([
            'muc_tieu_tap_luyen' => 'TANG_SUC_MANH',
            'kinh_nghiem_tap_luyen' => 'TRUNG_CAP',
            'so_ngay_tap_mong_muon' => 3,
            'thoi_luong_moi_buoi_phut' => 60,
        ], $profileOverrides));
        $hienTai = CarbonImmutable::now('UTC');
        foreach ([2, 4, 6] as $ngay) {
            DB::table('ngay_ranh_hoi_vien')->insert([
                'hoi_vien_id' => $hoiVienId,
                'thu_trong_tuan' => $ngay,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
        }

        return $hoiVienId;
    }

    /** @param array<string, mixed> $overrides */
    protected function taoDungCuAi(array $overrides = []): int
    {
        $hienTai = CarbonImmutable::now('UTC');
        $hauTo = strtoupper(bin2hex(random_bytes(5)));

        return DB::table('dung_cu')->insertGetId(array_merge([
            'ma_dung_cu' => 'DC_AI_'.$hauTo,
            'ten_dung_cu' => 'Dụng cụ AI '.$hauTo,
            'mo_ta' => null,
            'trang_thai' => 'HOAT_DONG',
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $overrides));
    }

    /** @param array<int, int> $dungCuIds @param array<string, mixed> $overrides */
    protected function taoBaiTapAi(array $fixture, array $dungCuIds = [], array $overrides = []): int
    {
        $hienTai = CarbonImmutable::now('UTC');
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $baiTapId = DB::table('bai_tap')->insertGetId(array_merge([
            'ma_bai_tap' => 'BT_AI_'.$hauTo,
            'ten_bai_tap' => 'Bài tập AI '.$hauTo,
            'do_kho' => 'TRUNG_BINH',
            'huong_dan' => 'Hướng dẫn kiểm thử.',
            'duong_dan_hinh_anh' => null,
            'duong_dan_video' => null,
            'thong_tin_bo_sung' => null,
            'phien_ban_noi_dung' => 1,
            'trang_thai' => 'HOAT_DONG',
            'nguoi_tao_id' => $fixture['user']->getKey(),
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $overrides));
        foreach ($dungCuIds as $dungCuId) {
            DB::table('bai_tap_dung_cu')->insert([
                'bai_tap_id' => $baiTapId,
                'dung_cu_id' => $dungCuId,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
        }

        return $baiTapId;
    }

    protected function ganDungCuAi(int $hoiVienId, int $dungCuId): void
    {
        $hienTai = CarbonImmutable::now('UTC');
        DB::table('dung_cu_hoi_vien')->insert([
            'hoi_vien_id' => $hoiVienId,
            'dung_cu_id' => $dungCuId,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
    }

    protected function taoMembershipAi(
        array $fixture,
        int $hoiVienId,
        ?int $gioiHan = 3,
        bool $choPhep = true,
        ?CarbonImmutable $moc = null,
    ): KyHanHoiVien {
        $moc ??= CarbonImmutable::now('UTC');
        $goi = $this->taoGoiTapMembership($fixture, [], [
            'cho_phep_tro_ly_tap_luyen' => $choPhep,
            'gioi_han_luot_tro_ly' => $choPhep ? $gioiHan : 0,
        ]);
        $ky = $this->taoDonVaSnapshotMembership($hoiVienId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);

        return $ky->fresh();
    }

    protected function kichHoatMembershipAi(KyHanHoiVien $ky, int $nguoiDungId, ?CarbonImmutable $moc = null): void
    {
        $usage = $this->taoUsageMembership($ky, $nguoiDungId, 'VAO_PHONG_TAP', $moc);
        app(MembershipActivationService::class)->kichHoatNeuCan((int) $usage->getKey());
    }

    protected function uuidAi(): string
    {
        return (string) Str::uuid();
    }
}
