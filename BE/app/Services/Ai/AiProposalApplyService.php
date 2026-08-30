<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiWorkflowException;
use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\DeXuatKeHoachTap;
use App\Models\KeHoachTap;
use App\Models\NguoiDung;
use App\Models\PhienBanKeHoachTap;
use App\Models\YeuCauChongLap;
use App\Models\YeuCauTroLy;
use App\Services\Workout\WorkoutMemberService;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiProposalApplyService
{
    private const PHAM_VI = 'AP_DUNG_DE_XUAT_TRO_LY';

    private const SO_NGAY_LICH = 92;

    public function __construct(
        private readonly WorkoutMemberService $members,
        private readonly AiProposalApplyValidator $validator,
        private readonly WorkoutPlanService $plans,
        private readonly WorkoutScheduleService $schedules,
        private readonly AiProposalAuditService $audit,
    ) {}

    /**
     * Apply đúng một lần Proposal AI đã lưu, không gọi provider và không dùng Membership/quota.
     *
     * @return array<string, mixed>
     */
    public function apDung(NguoiDung $nguoiDung, int $deXuatId, string $khoaYeuCau): array
    {
        try {
            $ketQua = DB::transaction(function () use ($nguoiDung, $deXuatId, $khoaYeuCau): array {
                $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung, true);
                $this->damBaoTaiKhoanVaVaiTro($nguoiDung);
                $deXuat = DeXuatKeHoachTap::query()
                    ->where('hoi_vien_id', $hoiVien->getKey())
                    ->where('nguon_de_xuat', 'TRO_LY')
                    ->lockForUpdate()
                    ->find($deXuatId);
                if (! $deXuat instanceof DeXuatKeHoachTap) {
                    throw new AiWorkflowException('Không tìm thấy Proposal.', 404, 'AI_PROPOSAL_NOT_FOUND');
                }

                if ($deXuat->trang_thai === 'DA_AP_DUNG') {
                    return $this->ketQuaDaApDung($deXuat, true);
                }
                if ($deXuat->trang_thai !== 'CHO_XAC_NHAN') {
                    throw new AiWorkflowException('Proposal không còn có thể Apply.', 409, 'AI_PROPOSAL_NOT_APPLICABLE');
                }

                $hienTai = CarbonImmutable::now('UTC');
                if ($hienTai->greaterThanOrEqualTo($deXuat->het_han_luc)) {
                    $this->ketThuc($deXuat, 'HET_HAN', $nguoiDung, $hienTai, 'Proposal đã hết hạn.');

                    return $this->loiSauCommit('Proposal đã hết hạn.', 409, 'AI_PROPOSAL_EXPIRED');
                }

                $yeuCau = YeuCauTroLy::query()->lockForUpdate()->find($deXuat->yeu_cau_tro_ly_id);
                if (! $yeuCau instanceof YeuCauTroLy) {
                    $this->ketThuc($deXuat, 'XUNG_DOT', $nguoiDung, $hienTai, 'Không còn AI Request nguồn.');

                    return $this->loiSauCommit('Proposal không còn nguồn hợp lệ.', 409, 'AI_PROPOSAL_SOURCE_CONFLICT');
                }

                $cacKeHoach = KeHoachTap::query()
                    ->where('hoi_vien_id', $hoiVien->getKey())
                    ->orderBy('id')->lockForUpdate()->get();
                $keHoachHienTai = $cacKeHoach->firstWhere('trang_thai', 'DANG_SU_DUNG');
                $loiNen = $this->kiemTraNen($deXuat, $keHoachHienTai);
                if ($loiNen !== null) {
                    $this->ketThuc($deXuat, 'XUNG_DOT', $nguoiDung, $hienTai, $loiNen['message']);

                    return $this->loiSauCommit($loiNen['message'], 409, $loiNen['code']);
                }

                try {
                    $cauTruc = $this->validator->kiemTraVaAnhXa($deXuat, $yeuCau, $hoiVien);
                } catch (AiWorkflowException $exception) {
                    $this->ketThuc($deXuat, 'XUNG_DOT', $nguoiDung, $hienTai, $exception->getMessage());

                    return $this->loiSauCommit($exception->getMessage(), $exception->responseStatus, $exception->safeCode);
                }

                $maBam = hash('sha256', json_encode(['proposal_id' => $deXuatId], JSON_THROW_ON_ERROR));
                $chongLap = YeuCauChongLap::query()
                    ->where('nguoi_dung_id', $nguoiDung->getKey())
                    ->where('pham_vi', self::PHAM_VI)
                    ->where('khoa_yeu_cau', $khoaYeuCau)
                    ->lockForUpdate()->first();
                if ($chongLap instanceof YeuCauChongLap) {
                    return $this->xuLyLap($chongLap, $maBam);
                }
                $chongLap = YeuCauChongLap::query()->create([
                    'nguoi_dung_id' => $nguoiDung->getKey(),
                    'pham_vi' => self::PHAM_VI,
                    'khoa_yeu_cau' => $khoaYeuCau,
                    'ma_bam_noi_dung' => $maBam,
                    'trang_thai' => 'DANG_XU_LY',
                    'ma_phan_hoi' => null,
                    'ket_qua_da_loc' => null,
                    'het_han_luc' => $hienTai->addHours(max(1, (int) config('ai.idempotency_ttl_hours', 24))),
                ]);

                $nguon = [
                    'proposal_id' => (int) $deXuat->getKey(),
                    'source' => 'TRO_LY',
                    'reason' => Str::limit((string) $deXuat->giai_thich, 1000, ''),
                ];
                if ($deXuat->loai_thay_doi === 'TAO_MOI') {
                    $keHoach = $this->plans->taoMoi($nguoiDung, $cauTruc, (string) Str::uuid(), true, $nguon);
                    $phienBan = $keHoach->phienBanHienTai;
                } else {
                    $phienBan = $this->plans->taoPhienBanTiepTheo(
                        $nguoiDung,
                        (int) $deXuat->ke_hoach_tap_id,
                        $cauTruc,
                        $nguon,
                    );
                    $keHoach = KeHoachTap::query()->findOrFail($deXuat->ke_hoach_tap_id);
                }
                if (! $phienBan instanceof PhienBanKeHoachTap) {
                    throw new AiWorkflowException('Không tạo được Plan Version.', 409, 'AI_PLAN_VERSION_CONFLICT');
                }

                $tuNgay = (string) $cauTruc['effective_from'];
                $denNgay = CarbonImmutable::parse($tuNgay, 'Asia/Ho_Chi_Minh')
                    ->addDays(self::SO_NGAY_LICH - 1)->toDateString();
                $lich = $this->schedules->lapLich(
                    (int) $hoiVien->getKey(),
                    (int) $keHoach->getKey(),
                    (int) $phienBan->getKey(),
                    $tuNgay,
                    $denNgay,
                );

                $hoiVien->forceFill([
                    'moc_thay_doi_ke_hoach' => (int) $hoiVien->moc_thay_doi_ke_hoach + 1,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();
                $deXuat->forceFill([
                    'trang_thai' => 'DA_AP_DUNG',
                    'nguoi_quyet_dinh_id' => $nguoiDung->getKey(),
                    'quyet_dinh_luc' => $hienTai,
                    'ap_dung_luc' => $hienTai,
                    'ly_do_ket_thuc' => null,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();
                $this->audit->ghiDaApDung(
                    $nguoiDung,
                    $deXuat,
                    (int) $keHoach->getKey(),
                    (int) $phienBan->getKey(),
                    $khoaYeuCau,
                    $hienTai,
                );

                $duLieu = $this->duLieuKetQua($deXuat, $keHoach, $phienBan, $tuNgay, $denNgay, count($lich), false);
                $chongLap->forceFill([
                    'trang_thai' => 'DA_HOAN_TAT',
                    'ma_phan_hoi' => 200,
                    'ket_qua_da_loc' => $duLieu,
                ])->save();

                return $duLieu;
            }, 3);
        } catch (WorkoutWorkflowException $exception) {
            throw new AiWorkflowException(
                $exception->getMessage(),
                $exception->responseStatus,
                $exception->safeCode,
            );
        }

        if (isset($ketQua['_error'])) {
            throw new AiWorkflowException(
                $ketQua['message'],
                $ketQua['status'],
                $ketQua['code'],
            );
        }

        return $ketQua;
    }

    /** @return array{message:string,code:string}|null */
    private function kiemTraNen(DeXuatKeHoachTap $deXuat, ?KeHoachTap $hienTai): ?array
    {
        if ($deXuat->loai_thay_doi === 'TAO_MOI') {
            if ($deXuat->ke_hoach_tap_id !== null || $deXuat->phien_ban_co_so_id !== null || $hienTai !== null) {
                return ['message' => 'Đã có Plan mới sau khi tạo Proposal.', 'code' => 'AI_PLAN_STALE'];
            }

            return null;
        }
        if (! in_array($deXuat->loai_thay_doi, ['DIEU_CHINH', 'THAY_BAI'], true)
            || ! $hienTai instanceof KeHoachTap
            || (int) $deXuat->ke_hoach_tap_id !== (int) $hienTai->getKey()
            || (int) $deXuat->phien_ban_co_so_id !== (int) $hienTai->phien_ban_hien_tai_id) {
            return ['message' => 'Base Plan Version đã thay đổi.', 'code' => 'AI_PLAN_STALE'];
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function xuLyLap(YeuCauChongLap $chongLap, string $maBam): array
    {
        if (! hash_equals((string) $chongLap->ma_bam_noi_dung, $maBam)) {
            throw new AiWorkflowException('Idempotency-Key đã dùng cho Proposal khác.', 409, 'IDEMPOTENCY_CONFLICT');
        }
        if ($chongLap->trang_thai !== 'DA_HOAN_TAT' || ! is_array($chongLap->ket_qua_da_loc)) {
            throw new AiWorkflowException('Yêu cầu Apply đang được xử lý.', 409, 'IDEMPOTENCY_IN_PROGRESS');
        }

        return array_merge($chongLap->ket_qua_da_loc, ['replayed' => true]);
    }

    /** @return array<string, mixed> */
    private function ketQuaDaApDung(DeXuatKeHoachTap $deXuat, bool $replayed): array
    {
        $phienBan = PhienBanKeHoachTap::query()
            ->where('de_xuat_ke_hoach_tap_id', $deXuat->getKey())
            ->lockForUpdate()
            ->first();
        if (! $phienBan instanceof PhienBanKeHoachTap) {
            throw new AiWorkflowException('Proposal đã Apply nhưng thiếu Plan Version.', 409, 'AI_APPLY_STATE_CONFLICT');
        }
        $keHoach = KeHoachTap::query()->lockForUpdate()->findOrFail($phienBan->ke_hoach_tap_id);
        $tuNgay = $phienBan->ap_dung_tu_ngay->toDateString();
        $denNgay = CarbonImmutable::parse($tuNgay, 'Asia/Ho_Chi_Minh')->addDays(self::SO_NGAY_LICH - 1)->toDateString();
        $soLich = DB::table('buoi_tap_du_kien')->where('phien_ban_ke_hoach_tap_id', $phienBan->getKey())->count();

        return $this->duLieuKetQua($deXuat, $keHoach, $phienBan, $tuNgay, $denNgay, $soLich, $replayed);
    }

    /** @return array<string, mixed> */
    private function duLieuKetQua(
        DeXuatKeHoachTap $deXuat,
        KeHoachTap $keHoach,
        PhienBanKeHoachTap $phienBan,
        string $tuNgay,
        string $denNgay,
        int $soLich,
        bool $replayed,
    ): array {
        return [
            'proposal' => ['id' => (int) $deXuat->getKey(), 'status' => 'DA_AP_DUNG'],
            'plan' => [
                'id' => (int) $keHoach->getKey(),
                'status' => (string) $keHoach->trang_thai,
                'current_version' => [
                    'id' => (int) $phienBan->getKey(),
                    'number' => (int) $phienBan->so_phien_ban,
                    'source' => (string) $phienBan->nguon_tao,
                    'effective_from' => $phienBan->ap_dung_tu_ngay->toDateString(),
                ],
            ],
            'schedule' => ['from' => $tuNgay, 'to' => $denNgay, 'items' => $soLich],
            'replayed' => $replayed,
        ];
    }

    private function ketThuc(
        DeXuatKeHoachTap $deXuat,
        string $trangThai,
        NguoiDung $nguoiDung,
        CarbonImmutable $thoiDiem,
        string $lyDo,
    ): void {
        $deXuat->forceFill([
            'trang_thai' => $trangThai,
            'nguoi_quyet_dinh_id' => $nguoiDung->getKey(),
            'quyet_dinh_luc' => $thoiDiem,
            'ap_dung_luc' => null,
            'ly_do_ket_thuc' => Str::limit($lyDo, 1000, ''),
            'ngay_cap_nhat' => $thoiDiem,
        ])->save();
    }

    private function damBaoTaiKhoanVaVaiTro(NguoiDung $nguoiDung): void
    {
        $taiKhoan = NguoiDung::query()->lockForUpdate()->find($nguoiDung->getKey());
        $phanQuyen = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $nguoiDung->getKey())
            ->where('vai_tro.ma_vai_tro', 'MEMBER')
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc')
            ->lockForUpdate()
            ->first(['phan_quyen_nguoi_dung.id']);
        if (! $taiKhoan instanceof NguoiDung || $taiKhoan->trang_thai !== 'HOAT_DONG' || $phanQuyen === null) {
            throw new AiWorkflowException('Tài khoản không có quyền MEMBER đang hiệu lực.', 403, 'MEMBER_ROLE_REQUIRED');
        }
    }

    /** @return array<string, mixed> */
    private function loiSauCommit(string $message, int $status, string $code): array
    {
        return ['_error' => true, 'message' => $message, 'status' => $status, 'code' => $code];
    }
}
