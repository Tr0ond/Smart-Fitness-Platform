<?php

namespace App\Services\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Models\GhiChuHuanLuyen;
use App\Models\KeHoachTap;
use App\Models\NguoiDung;
use App\Models\PhienTap;
use App\Services\Workout\WorkoutMemberService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PtNoteService
{
    public function __construct(
        private readonly PtAssignmentScopeService $scope,
        private readonly WorkoutMemberService $members,
        private readonly PtProposalAuditService $audit,
    ) {}

    /**
     * Append ghi chú tư vấn cho đúng assignment hiện tại.
     *
     * Plan/Session tùy chọn chỉ được liên kết khi thuộc cùng Member; hàm không
     * update dữ liệu kê tập, kết quả thực tế, Membership hoặc quota.
     *
     * @param  array<string, mixed>  $duLieu
     * @return array<string, mixed>
     */
    public function tao(NguoiDung $pt, int $hoiVienId, array $duLieu): array
    {
        return DB::transaction(function () use ($pt, $hoiVienId, $duLieu): array {
            $hienTai = CarbonImmutable::now('UTC');
            $this->scope->khoaHoiVien($hoiVienId);
            $cacPhanCong = $this->scope->khoaCacPhanCong($hoiVienId);
            $hoSoPt = $this->scope->khoaHuanLuyenVien($pt);
            $phanCong = $this->scope->phanCongHienTai($cacPhanCong, (int) $hoSoPt->getKey(), $hienTai);

            $noiDung = trim((string) $duLieu['content']);
            if ($noiDung === '') {
                throw new PtWorkflowException('Nội dung ghi chú không được để trống.', 422, 'PT_NOTE_CONTENT_REQUIRED');
            }
            $keHoachId = isset($duLieu['plan_id']) ? (int) $duLieu['plan_id'] : null;
            if ($keHoachId !== null && ! KeHoachTap::query()
                ->where('hoi_vien_id', $hoiVienId)
                ->lockForUpdate()
                ->whereKey($keHoachId)
                ->exists()) {
                throw new PtWorkflowException('Không tìm thấy Plan của hội viên.', 404, 'WORKOUT_PLAN_NOT_FOUND');
            }
            $phienTapId = isset($duLieu['session_id']) ? (int) $duLieu['session_id'] : null;
            if ($phienTapId !== null && ! PhienTap::query()
                ->where('hoi_vien_id', $hoiVienId)
                ->lockForUpdate()
                ->whereKey($phienTapId)
                ->exists()) {
                throw new PtWorkflowException('Không tìm thấy Session của hội viên.', 404, 'WORKOUT_SESSION_NOT_FOUND');
            }

            $ghiChu = GhiChuHuanLuyen::query()->create([
                'hoi_vien_id' => $hoiVienId,
                'phan_cong_huan_luyen_vien_id' => $phanCong->getKey(),
                'nguoi_tao_id' => $pt->getKey(),
                'ke_hoach_tap_id' => $keHoachId,
                'phien_tap_id' => $phienTapId,
                'noi_dung' => $noiDung,
                'ngay_tao' => $hienTai,
            ]);
            $this->audit->ghiGhiChu($pt, $ghiChu, $hienTai);

            return $this->duLieuGhiChu($ghiChu);
        }, 3);
    }

    /** Member đọc toàn bộ lịch sử ghi chú self-owned dù assignment đã kết thúc. */
    public function danhSachCuaHoiVien(NguoiDung $nguoiDung): array
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);

        return GhiChuHuanLuyen::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderByDesc('ngay_tao')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (GhiChuHuanLuyen $ghiChu): array => $this->duLieuGhiChu($ghiChu))
            ->values()
            ->all();
    }

    /** PT chỉ đọc note của chính assignment hiện tại, không đọc lịch sử PT khác. */
    public function danhSachCuaHuanLuyenVien(NguoiDung $pt, int $hoiVienId): array
    {
        return DB::transaction(function () use ($pt, $hoiVienId): array {
            $hienTai = CarbonImmutable::now('UTC');
            $this->scope->khoaHoiVien($hoiVienId);
            $cacPhanCong = $this->scope->khoaCacPhanCong($hoiVienId);
            $hoSoPt = $this->scope->khoaHuanLuyenVien($pt);
            $phanCong = $this->scope->phanCongHienTai($cacPhanCong, (int) $hoSoPt->getKey(), $hienTai);

            return GhiChuHuanLuyen::query()
                ->where('hoi_vien_id', $hoiVienId)
                ->where('phan_cong_huan_luyen_vien_id', $phanCong->getKey())
                ->orderByDesc('ngay_tao')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn (GhiChuHuanLuyen $ghiChu): array => $this->duLieuGhiChu($ghiChu))
                ->values()
                ->all();
        }, 3);
    }

    /** @return array<string, mixed> */
    private function duLieuGhiChu(GhiChuHuanLuyen $ghiChu): array
    {
        return [
            'id' => (int) $ghiChu->getKey(),
            'member_id' => (int) $ghiChu->hoi_vien_id,
            'assignment_id' => (int) $ghiChu->phan_cong_huan_luyen_vien_id,
            'creator_user_id' => (int) $ghiChu->nguoi_tao_id,
            'plan_id' => $ghiChu->ke_hoach_tap_id === null ? null : (int) $ghiChu->ke_hoach_tap_id,
            'session_id' => $ghiChu->phien_tap_id === null ? null : (int) $ghiChu->phien_tap_id,
            'content' => (string) $ghiChu->noi_dung,
            'created_at' => $ghiChu->ngay_tao?->toISOString(),
        ];
    }
}
