<?php

namespace Tests\Concerns;

use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesWorkoutFixtures
{
    use CreatesAiFixtures;
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesProfileFixtures;

    protected function taoHoiVienWorkout(): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $hoiVienId = $this->taoHoSoHoiVien($fixture, ['muc_tieu_tap_luyen' => 'TANG_SUC_MANH']);
        $token = $this->taoTheTruyCapThuCong($fixture['user'], CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addDay());

        return array_merge($fixture, ['member_id' => $hoiVienId, 'token' => $token]);
    }

    protected function taoBaiTapWorkout(array $fixture, array $overrides = []): int
    {
        return $this->taoBaiTapAi($fixture, [], $overrides);
    }

    protected function cauTrucWorkout(int $baiTapId, ?string $ngay = null, string $tenBai = 'Kế hoạch kiểm thử'): array
    {
        $ngayDiaPhuong = $ngay === null
            ? CarbonImmutable::now('Asia/Ho_Chi_Minh')
            : CarbonImmutable::parse($ngay, 'Asia/Ho_Chi_Minh');
        $thu = $ngayDiaPhuong->dayOfWeekIso === 7 ? 8 : $ngayDiaPhuong->dayOfWeekIso + 1;

        return [
            'name' => $tenBai,
            'goal' => 'TANG_SUC_MANH',
            'effective_from' => $ngayDiaPhuong->toDateString(),
            'days' => [[
                'logical_id' => (string) Str::uuid(),
                'order' => 1,
                'weekday' => $thu,
                'name' => 'Ngày sức mạnh',
                'estimated_minutes' => 60,
                'exercises' => [[
                    'exercise_id' => $baiTapId,
                    'logical_id' => (string) Str::uuid(),
                    'order' => 1,
                    'target_sets' => 3,
                    'min_reps' => 8,
                    'max_reps' => 12,
                    'target_weight_kg' => 20,
                    'rest_seconds' => 90,
                    'notes' => 'Snapshot kiểm thử',
                ]],
            ]],
        ];
    }

    protected function taoPlanWorkout(array $fixture, int $baiTapId, ?string $ngay = null, bool $kichHoat = true): array
    {
        $plan = app(WorkoutPlanService::class)->taoMoi(
            $fixture['user'],
            $this->cauTrucWorkout($baiTapId, $ngay),
            (string) Str::uuid(),
            $kichHoat,
        );
        $plan = $plan->fresh('phienBanHienTai.ngayTrongKeHoachs');

        return [
            'plan' => $plan,
            'version' => $plan->phienBanHienTai,
            'day' => $plan->phienBanHienTai->ngayTrongKeHoachs->firstOrFail(),
        ];
    }

    protected function taoLichWorkout(array $fixture, array $plan, ?string $ngay = null)
    {
        $ngay ??= CarbonImmutable::now('Asia/Ho_Chi_Minh')->toDateString();

        return app(WorkoutScheduleService::class)->taoMotBuoi(
            $fixture['member_id'],
            $plan['plan']->getKey(),
            $plan['version']->getKey(),
            $plan['day']->getKey(),
            $ngay,
        );
    }

    protected function taoGiaoAnMauWorkout(array $fixture, int $baiTapId, string $trangThai = 'HOAT_DONG'): int
    {
        $hienTai = CarbonImmutable::now('UTC');
        $hauTo = strtoupper(bin2hex(random_bytes(5)));
        $giaoAnId = DB::table('giao_an_mau')->insertGetId([
            'ma_giao_an' => 'GA_WO_'.$hauTo,
            'ten_giao_an' => 'Giáo án Workout '.$hauTo,
            'mo_ta' => 'Template kiểm thử',
            'muc_tieu' => 'TANG_SUC_MANH',
            'trinh_do' => 'MOI_BAT_DAU',
            'so_buoi_moi_tuan' => 1,
            'phien_ban_noi_dung' => 1,
            'trang_thai' => $trangThai,
            'nguoi_tao_id' => $fixture['user']->getKey(),
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
        $ngayId = DB::table('ngay_trong_giao_an')->insertGetId([
            'giao_an_mau_id' => $giaoAnId,
            'so_thu_tu' => 1,
            'ten_ngay' => 'Ngày mẫu',
            'thoi_luong_du_kien_phut' => 60,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
        DB::table('bai_tap_trong_giao_an')->insert([
            'ngay_trong_giao_an_id' => $ngayId,
            'bai_tap_id' => $baiTapId,
            'so_thu_tu' => 1,
            'so_hiep_muc_tieu' => 3,
            'so_lan_lap_toi_thieu' => 8,
            'so_lan_lap_toi_da' => 12,
            'thoi_gian_nghi_giay' => 90,
            'ghi_chu' => null,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);

        return $giaoAnId;
    }
}
