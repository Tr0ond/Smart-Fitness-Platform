<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\BaiTapTrongPhien;
use App\Models\BuoiTapDuKien;
use App\Models\ChiSoCoThe;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\KeHoachTap;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\PhienTap;
use App\Services\Progress\BodyMeasurementService;
use App\Services\Pt\PtAssignmentService;
use App\Services\Pt\PtNoteService;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Fixture FE5 rieng biet: chi chay tren schema demo local, khong dang ky vao DatabaseSeeder. */
class Fe5WorkspaceDemoSeeder extends Seeder
{
    private const DATABASE = 'smart_fitness_fe5_demo';

    private const PLAN_KEY = 'a0e50000-0000-4000-8000-000000000001';

    private const BODY_KEY_OLD = 'a0e50000-0000-4000-8000-000000000002';

    private const BODY_KEY_NEW = 'a0e50000-0000-4000-8000-000000000003';

    private const NOTE = 'FE5 DEMO: Da kiem tra ky thuat, tiep tuc tap dung lich va ghi nhan tien do.';

    public function run(): void
    {
        if (! app()->environment('local') || config('database.default') !== 'mysql'
            || DB::connection()->getDatabaseName() !== self::DATABASE) {
            throw new \RuntimeException('Fe5WorkspaceDemoSeeder chi duoc chay tren local/'.self::DATABASE.'.');
        }

        DB::transaction(function (): void {
            $admin = $this->nguoiDung('dev.admin@smartfitness.local');
            $pt = $this->nguoiDung('dev.pt01@smartfitness.local');
            $member = $this->nguoiDung('dev.member01@smartfitness.local');
            $ptProfile = HoSoHuanLuyenVien::query()->where('nguoi_dung_id', $pt->getKey())->firstOrFail();
            $memberProfile = HoSoHoiVien::query()->where('nguoi_dung_id', $member->getKey())->firstOrFail();
            $now = CarbonImmutable::now('Asia/Ho_Chi_Minh');

            $assignment = PhanCongHuanLuyenVien::query()
                ->where('hoi_vien_id', $memberProfile->getKey())
                ->whereNull('ngay_ket_thuc')
                ->first();
            if ($assignment !== null && (int) $assignment->huan_luyen_vien_id !== (int) $ptProfile->getKey()) {
                throw new \RuntimeException('Demo member already has another open PT assignment.');
            }
            if ($assignment === null) {
                if (PhanCongHuanLuyenVien::query()->where('hoi_vien_id', $memberProfile->getKey())->exists()) {
                    throw new \RuntimeException('Demo assignment was closed; refusing to restore it.');
                }
                app(PtAssignmentService::class)->tao($admin, [
                    'member_id' => $memberProfile->getKey(),
                    'trainer_id' => $ptProfile->getKey(),
                    'start_at' => $now->subDay()->utc()->toISOString(),
                ]);
            }

            $exercise = BaiTap::query()->where('trang_thai', 'HOAT_DONG')
                ->with('baiTapDungCus')->orderBy('id')->firstOrFail();
            foreach ($exercise->baiTapDungCus as $requiredEquipment) {
                DB::table('dung_cu_hoi_vien')->insertOrIgnore([
                    'hoi_vien_id' => $memberProfile->getKey(),
                    'dung_cu_id' => $requiredEquipment->dung_cu_id,
                    'ngay_tao' => $now->utc(),
                    'ngay_cap_nhat' => $now->utc(),
                ]);
            }
            $plan = KeHoachTap::query()->where('ma_lan_tao', self::PLAN_KEY)->first();
            $otherActivePlan = KeHoachTap::query()->where('hoi_vien_id', $memberProfile->getKey())
                ->where('trang_thai', 'DANG_SU_DUNG')
                ->when($plan !== null, fn ($query) => $query->where('id', '<>', $plan->getKey()))
                ->exists();
            if ($otherActivePlan) {
                throw new \RuntimeException('Demo member has another active plan; refusing to archive it.');
            }
            if ($plan === null) {
                $weekday = $now->dayOfWeekIso === 7 ? 8 : $now->dayOfWeekIso + 1;
                $plan = app(WorkoutPlanService::class)->taoMoi($member, [
                    'name' => 'FE5 Demo - Ke hoach suc manh',
                    'goal' => 'GIAM_MO',
                    'effective_from' => $now->toDateString(),
                    'days' => [[
                        'logical_id' => (string) Str::uuid(),
                        'order' => 1,
                        'weekday' => $weekday,
                        'name' => 'Buoi tap FE5 demo',
                        'estimated_minutes' => 45,
                        'exercises' => [[
                            'exercise_id' => $exercise->getKey(),
                            'logical_id' => (string) Str::uuid(),
                            'order' => 1,
                            'target_sets' => 3,
                            'min_reps' => 8,
                            'max_reps' => 12,
                            'target_weight_kg' => 10,
                            'rest_seconds' => 60,
                            'notes' => 'Du lieu FE5 demo',
                        ]],
                    ]],
                ], self::PLAN_KEY);
            }
            if ((int) $plan->hoi_vien_id !== (int) $memberProfile->getKey()) {
                throw new \RuntimeException('FE5 demo plan key is owned by another member.');
            }
            if ($plan->trang_thai !== 'DANG_SU_DUNG') {
                throw new \RuntimeException('FE5 demo plan is not active; refusing to change it.');
            }
            $plan = $plan->fresh('phienBanHienTai.ngayTrongKeHoachs');
            $version = $plan->phienBanHienTai;
            $planDay = $version?->ngayTrongKeHoachs->first();
            if ($planDay === null) {
                throw new \RuntimeException('FE5 demo plan day is missing.');
            }

            $this->taoChiSoNeuThieu($member, $memberProfile, self::BODY_KEY_OLD, $now->subDays(14), 73.5);
            $this->taoChiSoNeuThieu($member, $memberProfile, self::BODY_KEY_NEW, $now->subDay(), 72.8);

            $session = PhienTap::query()->where('hoi_vien_id', $memberProfile->getKey())
                ->where('trang_thai', 'HOAN_THANH')->orderBy('id')->first();
            if ($session === null) {
                if (PhienTap::query()->where('hoi_vien_id', $memberProfile->getKey())->exists()) {
                    throw new \RuntimeException('Demo member has an unfinished workout; refusing to replace it.');
                }
                $schedule = app(WorkoutScheduleService::class)->taoMotBuoi(
                    $memberProfile->getKey(), $plan->getKey(), $version->getKey(), $planDay->getKey(), $now->toDateString(),
                );
                $workout = app(WorkoutSessionService::class);
                $started = $workout->batDau($member, $schedule->getKey(), (string) Str::uuid());
                $exerciseInSession = BaiTapTrongPhien::query()->where('phien_tap_id', $started['id'])->firstOrFail();
                $workout->ghiHiep($member, $started['id'], $exerciseInSession->getKey(), [
                    'order' => 1,
                    'reps' => 10,
                    'weight_kg' => 10,
                    'actual_rest_seconds' => 60,
                ], (string) Str::uuid());
                $workout->hoanThanh($member, $started['id'], (string) Str::uuid(), 'Buoi tap FE5 demo da hoan thanh.');
                $session = PhienTap::query()->findOrFail($started['id']);
            }

            $hasFuture = BuoiTapDuKien::query()->where('hoi_vien_id', $memberProfile->getKey())
                ->where('trang_thai', 'CHUA_TAP')->where('ngay_tap', '>', $now->toDateString())->exists();
            if (! $hasFuture) {
                $nextDate = $now->addDay();
                while (($nextDate->dayOfWeekIso === 7 ? 8 : $nextDate->dayOfWeekIso + 1) !== (int) $planDay->thu_trong_tuan) {
                    $nextDate = $nextDate->addDay();
                }
                app(WorkoutScheduleService::class)->taoMotBuoi(
                    $memberProfile->getKey(), $plan->getKey(), $version->getKey(), $planDay->getKey(), $nextDate->toDateString(),
                );
            }

            $hasNote = DB::table('ghi_chu_huan_luyen')->where('hoi_vien_id', $memberProfile->getKey())
                ->where('noi_dung', self::NOTE)->exists();
            if (! $hasNote) {
                app(PtNoteService::class)->tao($pt, $memberProfile->getKey(), [
                    'content' => self::NOTE,
                    'plan_id' => $plan->getKey(),
                    'session_id' => $session->getKey(),
                ]);
            }

            $this->command?->info('FE5 demo ready: PT dev.pt01@smartfitness.local -> Member dev.member01@smartfitness.local (ID '.$memberProfile->getKey().').');
        }, 3);
    }

    private function nguoiDung(string $email): NguoiDung
    {
        return NguoiDung::query()->where('thu_dien_tu', $email)->firstOrFail();
    }

    private function taoChiSoNeuThieu(NguoiDung $member, HoSoHoiVien $profile, string $key, CarbonImmutable $measuredAt, float $weight): void
    {
        if (ChiSoCoThe::query()->where('hoi_vien_id', $profile->getKey())->where('ma_lan_ghi', $key)->exists()) {
            return;
        }
        app(BodyMeasurementService::class)->tao($member, $profile, [
            'entry_id' => $key,
            'measured_at' => $measuredAt->utc()->toISOString(),
            'weight_kg' => $weight,
            'height_cm' => 172,
            'waist_cm' => $weight > 73 ? 85 : 84,
            'notes' => 'FE5 demo measurement',
        ]);
    }
}
