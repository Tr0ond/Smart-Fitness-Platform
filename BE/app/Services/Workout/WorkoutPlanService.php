<?php

namespace App\Services\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\BaiTap;
use App\Models\HoSoHoiVien;
use App\Models\KeHoachTap;
use App\Models\NgayTrongKeHoach;
use App\Models\NguoiDung;
use App\Models\PhienBanKeHoachTap;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkoutPlanService
{
    public function __construct(private readonly WorkoutMemberService $members) {}

    /** Revalidate cấu trúc Plan mà workflow Proposal sắp công bố, không tạo dữ liệu. */
    public function kiemTraCauTruc(NguoiDung $nguoiDung, array $cauTruc): void
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);
        $this->damBaoCauTruc($hoiVien, $cauTruc);
    }

    /**
     * Tạo Plan cùng snapshot version 1 từ cấu trúc đã được Backend kiểm định.
     *
     * Đây là API service nội bộ dùng chung cho fixture và các workflow Apply đã
     * revalidate; không mở HTTP mutation thủ công. Member → Plan được khóa cố định.
     *
     * @param  array{name:string,goal:string,effective_from:string,template_id?:int|null,template_name?:string|null,days:array<int,array{logical_id?:string,order:int,weekday:int,name:string,estimated_minutes:int,exercises:array<int,array{exercise_id:int,logical_id?:string,order:int,target_sets:int,min_reps:int,max_reps:int,target_weight_kg?:float|int|string|null,rest_seconds:int,notes?:string|null}>}>}  $cauTruc
     */
    public function taoMoi(
        NguoiDung $nguoiDung,
        array $cauTruc,
        string $maLanTao,
        bool $kichHoat = true,
        array $nguonSnapshot = [],
    ): KeHoachTap {
        return DB::transaction(function () use ($nguoiDung, $cauTruc, $maLanTao, $kichHoat, $nguonSnapshot): KeHoachTap {
            $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
            $this->damBaoUuid($maLanTao, 'INVALID_PLAN_REQUEST_ID');

            $keHoachCu = KeHoachTap::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->where('ma_lan_tao', $maLanTao)
                ->lockForUpdate()
                ->first();
            if ($keHoachCu instanceof KeHoachTap) {
                return $keHoachCu->fresh('phienBanHienTai');
            }

            $this->damBaoCauTruc($hoiVien, $cauTruc);
            $hienTai = CarbonImmutable::now('UTC');
            $nguoiTaoId = (int) ($nguonSnapshot['creator_user_id'] ?? $nguoiDung->getKey());
            $keHoach = KeHoachTap::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'ten_ke_hoach' => trim($cauTruc['name']),
                'trang_thai' => 'LUU_TRU',
                'phien_ban_hien_tai_id' => null,
                'nguoi_tao_id' => $nguoiTaoId,
                'ma_lan_tao' => $maLanTao,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);

            $phienBan = $this->taoSnapshot($keHoach, $nguoiDung, $cauTruc, null, 1, $nguonSnapshot);
            $keHoach->forceFill(['phien_ban_hien_tai_id' => $phienBan->getKey(), 'ngay_cap_nhat' => $hienTai])->save();
            if ($kichHoat) {
                $this->kichHoatDaKhoa($hoiVien, $keHoach, $hienTai);
            }

            return $keHoach->fresh('phienBanHienTai');
        }, 3);
    }

    /**
     * Công bố version kế tiếp, không sửa snapshot cũ và không đụng Session history.
     *
     * @param  array{name:string,goal:string,effective_from:string,template_id?:int|null,template_name?:string|null,days:array<int,array{logical_id?:string,order:int,weekday:int,name:string,estimated_minutes:int,exercises:array<int,array{exercise_id:int,logical_id?:string,order:int,target_sets:int,min_reps:int,max_reps:int,target_weight_kg?:float|int|string|null,rest_seconds:int,notes?:string|null}>}>}  $cauTruc
     */
    public function taoPhienBanTiepTheo(
        NguoiDung $nguoiDung,
        int $keHoachId,
        array $cauTruc,
        array $nguonSnapshot = [],
    ): PhienBanKeHoachTap {
        return DB::transaction(function () use ($nguoiDung, $keHoachId, $cauTruc, $nguonSnapshot): PhienBanKeHoachTap {
            $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
            $keHoach = KeHoachTap::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->lockForUpdate()
                ->find($keHoachId);
            if (! $keHoach instanceof KeHoachTap) {
                throw new WorkoutWorkflowException('Không tìm thấy kế hoạch tập.', 404, 'WORKOUT_PLAN_NOT_FOUND');
            }

            $this->damBaoCauTruc($hoiVien, $cauTruc);
            $phienBanTruoc = PhienBanKeHoachTap::query()
                ->where('ke_hoach_tap_id', $keHoach->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->firstWhere('id', $keHoach->phien_ban_hien_tai_id);
            if (! $phienBanTruoc instanceof PhienBanKeHoachTap) {
                throw new WorkoutWorkflowException('Plan chưa có phiên bản hiện tại hợp lệ.', 409, 'WORKOUT_PLAN_VERSION_CONFLICT');
            }

            $soPhienBan = (int) PhienBanKeHoachTap::query()
                ->where('ke_hoach_tap_id', $keHoach->getKey())
                ->max('so_phien_ban') + 1;
            $phienBan = $this->taoSnapshot($keHoach, $nguoiDung, $cauTruc, $phienBanTruoc, $soPhienBan, $nguonSnapshot);
            $keHoach->forceFill([
                'ten_ke_hoach' => trim($cauTruc['name']),
                'phien_ban_hien_tai_id' => $phienBan->getKey(),
                'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
            ])->save();

            return $phienBan->fresh('ngayTrongKeHoachs.baiTapTrongKeHoachs');
        }, 3);
    }

    /** Chuyển Plan đang dùng dưới khóa Member; generated UNIQUE là hàng rào cuối. */
    public function kichHoat(NguoiDung $nguoiDung, int $keHoachId): KeHoachTap
    {
        try {
            return DB::transaction(function () use ($nguoiDung, $keHoachId): KeHoachTap {
                $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
                $cacKeHoach = KeHoachTap::query()
                    ->where('hoi_vien_id', $hoiVien->getKey())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $keHoach = $cacKeHoach->firstWhere('id', $keHoachId);
                if (! $keHoach instanceof KeHoachTap) {
                    throw new WorkoutWorkflowException('Không tìm thấy kế hoạch tập.', 404, 'WORKOUT_PLAN_NOT_FOUND');
                }
                if ($keHoach->phien_ban_hien_tai_id === null) {
                    throw new WorkoutWorkflowException('Plan chưa có phiên bản hiện tại.', 409, 'WORKOUT_PLAN_VERSION_REQUIRED');
                }

                $this->kichHoatDaKhoa($hoiVien, $keHoach, CarbonImmutable::now('UTC'));

                return $keHoach->fresh();
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new WorkoutWorkflowException('Một kế hoạch khác đã được kích hoạt đồng thời.', 409, 'ACTIVE_PLAN_CONFLICT');
            }
            throw $exception;
        }
    }

    private function kichHoatDaKhoa(HoSoHoiVien $hoiVien, KeHoachTap $keHoach, CarbonImmutable $hienTai): void
    {
        KeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('id', '<>', $keHoach->getKey())
            ->where('trang_thai', 'DANG_SU_DUNG')
            ->update(['trang_thai' => 'LUU_TRU', 'ngay_cap_nhat' => $hienTai]);
        $keHoach->forceFill(['trang_thai' => 'DANG_SU_DUNG', 'ngay_cap_nhat' => $hienTai])->save();
    }

    private function taoSnapshot(
        KeHoachTap $keHoach,
        NguoiDung $nguoiDung,
        array $cauTruc,
        ?PhienBanKeHoachTap $truoc,
        int $soPhienBan,
        array $nguonSnapshot,
    ): PhienBanKeHoachTap {
        $hienTai = CarbonImmutable::now('UTC');
        $phienBan = PhienBanKeHoachTap::query()->create([
            'ke_hoach_tap_id' => $keHoach->getKey(),
            'so_phien_ban' => $soPhienBan,
            'phien_ban_truoc_id' => $truoc?->getKey(),
            'giao_an_mau_id' => $cauTruc['template_id'] ?? null,
            'ten_giao_an_da_chon' => $cauTruc['template_name'] ?? null,
            'de_xuat_ke_hoach_tap_id' => $nguonSnapshot['proposal_id'] ?? null,
            'nguon_tao' => $nguonSnapshot['source'] ?? 'HOI_VIEN',
            'nguoi_tao_id' => (int) ($nguonSnapshot['creator_user_id'] ?? $nguoiDung->getKey()),
            'muc_tieu' => trim($cauTruc['goal']),
            'ap_dung_tu_ngay' => $cauTruc['effective_from'],
            'ly_do_thay_doi' => $nguonSnapshot['reason'] ?? ($truoc === null ? null : 'Cập nhật kế hoạch tập.'),
            'ma_bam_noi_dung' => hash('sha256', json_encode($cauTruc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
            'ngay_tao' => $hienTai,
        ]);

        foreach ($cauTruc['days'] as $ngayDuLieu) {
            $ngay = NgayTrongKeHoach::query()->create([
                'phien_ban_ke_hoach_tap_id' => $phienBan->getKey(),
                'ma_ngay_logic' => $ngayDuLieu['logical_id'] ?? (string) Str::uuid(),
                'so_thu_tu' => $ngayDuLieu['order'],
                'thu_trong_tuan' => $ngayDuLieu['weekday'],
                'ten_ngay' => trim($ngayDuLieu['name']),
                'thoi_luong_du_kien_phut' => $ngayDuLieu['estimated_minutes'],
                'ngay_tao' => $hienTai,
            ]);
            foreach ($ngayDuLieu['exercises'] as $baiDuLieu) {
                $baiTap = BaiTap::query()->with('baiTapDungCus.dungCu')->findOrFail($baiDuLieu['exercise_id']);
                $ngay->baiTapTrongKeHoachs()->create([
                    'bai_tap_id' => $baiTap->getKey(),
                    'ma_bai_logic' => $baiDuLieu['logical_id'] ?? (string) Str::uuid(),
                    'so_thu_tu' => $baiDuLieu['order'],
                    'ten_bai_tap' => $baiTap->ten_bai_tap,
                    'huong_dan' => $baiTap->huong_dan,
                    'dung_cu_yeu_cau' => $baiTap->baiTapDungCus->map(fn ($muc): array => [
                        'code' => $muc->dungCu?->ma_dung_cu,
                        'name' => $muc->dungCu?->ten_dung_cu,
                    ])->values()->all(),
                    'so_hiep_muc_tieu' => $baiDuLieu['target_sets'],
                    'so_lan_lap_toi_thieu' => $baiDuLieu['min_reps'],
                    'so_lan_lap_toi_da' => $baiDuLieu['max_reps'],
                    'khoi_luong_muc_tieu_kg' => $baiDuLieu['target_weight_kg'] ?? null,
                    'thoi_gian_nghi_giay' => $baiDuLieu['rest_seconds'],
                    'ghi_chu' => $baiDuLieu['notes'] ?? null,
                    'ngay_tao' => $hienTai,
                ]);
            }
        }

        return $phienBan;
    }

    private function damBaoCauTruc(HoSoHoiVien $hoiVien, array $cauTruc): void
    {
        if (trim((string) ($cauTruc['name'] ?? '')) === '' || mb_strlen($cauTruc['name']) > 150
            || trim((string) ($cauTruc['goal'] ?? '')) === '' || ! isset($cauTruc['days']) || $cauTruc['days'] === []) {
            throw new WorkoutWorkflowException('Cấu trúc kế hoạch không hợp lệ.', 422, 'INVALID_PLAN_STRUCTURE');
        }

        $thuTuNgay = [];
        $thuTrongTuan = [];
        $baiTapIds = [];
        foreach ($cauTruc['days'] as $ngay) {
            if (! isset($ngay['order'], $ngay['weekday'], $ngay['name'], $ngay['estimated_minutes'], $ngay['exercises'])
                || $ngay['order'] < 1 || $ngay['weekday'] < 2 || $ngay['weekday'] > 8
                || $ngay['estimated_minutes'] < 1 || $ngay['exercises'] === []) {
                throw new WorkoutWorkflowException('Ngày trong kế hoạch không hợp lệ.', 422, 'INVALID_PLAN_DAY');
            }
            if (isset($thuTuNgay[$ngay['order']]) || isset($thuTrongTuan[$ngay['weekday']])) {
                throw new WorkoutWorkflowException('Thứ tự hoặc thứ trong tuần bị trùng.', 422, 'DUPLICATE_PLAN_DAY');
            }
            $thuTuNgay[$ngay['order']] = true;
            $thuTrongTuan[$ngay['weekday']] = true;
            $thuTuBai = [];
            foreach ($ngay['exercises'] as $bai) {
                if (! isset($bai['exercise_id'], $bai['order'], $bai['target_sets'], $bai['min_reps'], $bai['max_reps'], $bai['rest_seconds'])
                    || $bai['order'] < 1 || $bai['target_sets'] < 1 || $bai['min_reps'] < 1
                    || $bai['max_reps'] < $bai['min_reps'] || $bai['rest_seconds'] < 0 || isset($thuTuBai[$bai['order']])) {
                    throw new WorkoutWorkflowException('Bài tập trong kế hoạch không hợp lệ.', 422, 'INVALID_PLAN_EXERCISE');
                }
                $thuTuBai[$bai['order']] = true;
                $baiTapIds[] = (int) $bai['exercise_id'];
            }
        }

        $baiTaps = BaiTap::query()->with('baiTapDungCus')->whereIn('id', array_unique($baiTapIds))->where('trang_thai', 'HOAT_DONG')->get();
        if ($baiTaps->count() !== count(array_unique($baiTapIds))) {
            throw new WorkoutWorkflowException('Kế hoạch chứa bài tập không hoạt động.', 422, 'INVALID_PLAN_EXERCISE');
        }
        $dungCuHoiVien = $hoiVien->dungCuHoiViens()->pluck('dung_cu_id')->map(fn ($id): int => (int) $id)->all();
        foreach ($baiTaps as $baiTap) {
            $batBuoc = $baiTap->baiTapDungCus->pluck('dung_cu_id')->map(fn ($id): int => (int) $id)->all();
            if (array_diff($batBuoc, $dungCuHoiVien) !== []) {
                throw new WorkoutWorkflowException('Hội viên chưa có đủ dụng cụ bắt buộc cho bài tập.', 422, 'REQUIRED_EQUIPMENT_MISSING');
            }
        }
    }

    private function damBaoUuid(string $giaTri, string $maLoi): void
    {
        if (! Str::isUuid($giaTri)) {
            throw new WorkoutWorkflowException('Mã yêu cầu phải là UUID hợp lệ.', 422, $maLoi);
        }
    }
}
