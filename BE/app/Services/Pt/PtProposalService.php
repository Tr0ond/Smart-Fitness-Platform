<?php

namespace App\Services\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Models\DeXuatKeHoachTap;
use App\Models\GiaoAnMau;
use App\Models\HoSoHoiVien;
use App\Models\KeHoachTap;
use App\Models\NguoiDung;
use App\Models\PhienBanKeHoachTap;
use App\Models\YeuCauChongLap;
use App\Services\Workout\WorkoutMemberService;
use App\Services\Workout\WorkoutPlanQueryService;
use App\Services\Workout\WorkoutPlanService;
use App\Services\Workout\WorkoutScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PtProposalService
{
    private const PHAM_VI_TAO = 'TAO_DE_XUAT_HUAN_LUYEN_VIEN';

    private const PHAM_VI_XAC_NHAN = 'XAC_NHAN_DE_XUAT_HUAN_LUYEN_VIEN';

    private const PHAM_VI_TU_CHOI = 'TU_CHOI_DE_XUAT_HUAN_LUYEN_VIEN';

    private const PHIEN_BAN_CAU_TRUC = 'pt-workout-proposal-v1';

    private const SO_NGAY_LICH = 92;

    public function __construct(
        private readonly PtAssignmentScopeService $scope,
        private readonly WorkoutMemberService $members,
        private readonly WorkoutPlanQueryService $planQuery,
        private readonly WorkoutPlanService $plans,
        private readonly WorkoutScheduleService $schedules,
        private readonly PtProposalAuditService $audit,
    ) {}

    /**
     * PT tạo Proposal bất biến cho đúng assignment hiện tại.
     *
     * Backend tự chụp assignment, base Plan/version, marker và TTL; toàn bộ thao tác
     * Proposal/idempotency/audit là atomic, không đọc hoặc ghi Membership/quota.
     *
     * @param  array<string, mixed>  $duLieu
     * @return array<string, mixed>
     */
    public function tao(NguoiDung $pt, int $hoiVienId, array $duLieu, string $khoaYeuCau): array
    {
        $this->damBaoUuid($khoaYeuCau);

        try {
            return DB::transaction(function () use ($pt, $hoiVienId, $duLieu, $khoaYeuCau): array {
                $hienTai = CarbonImmutable::now('UTC');
                $hoiVien = $this->scope->khoaHoiVien($hoiVienId);
                $cacPhanCong = $this->scope->khoaCacPhanCong($hoiVienId);
                $hoSoPt = $this->scope->khoaHuanLuyenVien($pt);
                $phanCong = $this->scope->phanCongHienTai($cacPhanCong, (int) $hoSoPt->getKey(), $hienTai);

                $duLieuYeuCau = $this->duLieuYeuCauTao($hoiVienId, $duLieu);
                $maBamYeuCau = hash('sha256', $this->jsonChuan($duLieuYeuCau));
                $chongLap = $this->timChongLap($pt, self::PHAM_VI_TAO, $khoaYeuCau);
                if ($chongLap instanceof YeuCauChongLap) {
                    return $this->xuLyLap($chongLap, $maBamYeuCau);
                }

                $cacKeHoach = KeHoachTap::query()
                    ->where('hoi_vien_id', $hoiVienId)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $keHoachHienTai = $cacKeHoach->firstWhere('trang_thai', 'DANG_SU_DUNG');
                [$keHoachId, $phienBanId] = $this->nenKhiTao((string) $duLieu['change_type'], $keHoachHienTai);

                $cauTruc = $this->chuanHoaCauTruc($duLieu, true);
                $this->damBaoNgayApDung($cauTruc['effective_from']);
                $cauTruc = $this->ganSnapshotGiaoAn($cauTruc);
                $nguoiDungHoiVien = NguoiDung::query()->findOrFail($hoiVien->nguoi_dung_id);
                $this->plans->kiemTraCauTruc($nguoiDungHoiVien, $cauTruc);

                $chongLap = $this->taoChongLap($pt, self::PHAM_VI_TAO, $khoaYeuCau, $maBamYeuCau, $hienTai);
                $deXuat = DeXuatKeHoachTap::query()->create([
                    'hoi_vien_id' => $hoiVienId,
                    'nguon_de_xuat' => 'HUAN_LUYEN_VIEN',
                    'nguoi_tao_id' => $pt->getKey(),
                    'phan_cong_huan_luyen_vien_id' => $phanCong->getKey(),
                    'yeu_cau_tro_ly_id' => null,
                    'ke_hoach_tap_id' => $keHoachId,
                    'phien_ban_co_so_id' => $phienBanId,
                    'moc_thay_doi_ke_hoach_co_so' => (int) $hoiVien->moc_thay_doi_ke_hoach,
                    'phien_ban_ho_so_co_so' => (int) $hoiVien->phien_ban_ho_so,
                    'loai_thay_doi' => (string) $duLieu['change_type'],
                    'tieu_de' => trim((string) $duLieu['title']),
                    'giai_thich' => trim((string) $duLieu['explanation']),
                    'noi_dung_de_xuat' => $cauTruc,
                    'phien_ban_cau_truc' => self::PHIEN_BAN_CAU_TRUC,
                    'ma_bam_noi_dung' => hash('sha256', $this->jsonChuan($cauTruc)),
                    'ap_dung_tu_ngay' => $cauTruc['effective_from'],
                    'trang_thai' => 'CHO_XAC_NHAN',
                    'het_han_luc' => $hienTai->addHours(max(1, (int) config('ai.proposal_ttl_hours', 24))),
                    'nguoi_quyet_dinh_id' => null,
                    'quyet_dinh_luc' => null,
                    'ap_dung_luc' => null,
                    'ly_do_ket_thuc' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                $this->audit->ghiProposal(
                    $pt,
                    $deXuat,
                    'TAO_DE_XUAT_HUAN_LUYEN_VIEN',
                    null,
                    'CHO_XAC_NHAN',
                    $khoaYeuCau,
                    $hienTai,
                );

                $ketQua = $this->duLieuDeXuat($deXuat, false);
                $this->hoanTatChongLap($chongLap, 201, $ketQua);

                return $ketQua;
            }, 3);
        } catch (WorkoutWorkflowException $exception) {
            throw $this->loiWorkout($exception);
        }
    }

    /** Member đọc danh sách Proposal PT của chính mình, không làm thay đổi TTL/trạng thái. */
    public function danhSachCuaHoiVien(NguoiDung $nguoiDung): array
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);

        return DeXuatKeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('nguon_de_xuat', 'HUAN_LUYEN_VIEN')
            ->orderByDesc('ngay_tao')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (DeXuatKeHoachTap $deXuat): array => $this->duLieuDeXuat($deXuat, false))
            ->values()
            ->all();
    }

    /** PT chỉ đọc Proposal của Member trong chính assignment còn hiệu lực. */
    public function danhSachCuaHuanLuyenVien(NguoiDung $pt, int $hoiVienId): array
    {
        return DB::transaction(function () use ($pt, $hoiVienId): array {
            $hienTai = CarbonImmutable::now('UTC');
            $this->scope->khoaHoiVien($hoiVienId);
            $cacPhanCong = $this->scope->khoaCacPhanCong($hoiVienId);
            $hoSoPt = $this->scope->khoaHuanLuyenVien($pt);
            $phanCong = $this->scope->phanCongHienTai($cacPhanCong, (int) $hoSoPt->getKey(), $hienTai);

            return DeXuatKeHoachTap::query()
                ->where('hoi_vien_id', $hoiVienId)
                ->where('nguon_de_xuat', 'HUAN_LUYEN_VIEN')
                ->where('phan_cong_huan_luyen_vien_id', $phanCong->getKey())
                ->orderByDesc('ngay_tao')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn (DeXuatKeHoachTap $deXuat): array => $this->duLieuDeXuat($deXuat, false))
                ->values()
                ->all();
        }, 3);
    }

    /** Member preview một Proposal PT self-owned; preview không gia hạn TTL. */
    public function chiTiet(NguoiDung $nguoiDung, int $deXuatId): array
    {
        $hoiVien = $this->members->hoiVienCuaNguoiDung($nguoiDung);
        $deXuat = DeXuatKeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('nguon_de_xuat', 'HUAN_LUYEN_VIEN')
            ->find($deXuatId);
        if (! $deXuat instanceof DeXuatKeHoachTap) {
            throw new PtWorkflowException('Không tìm thấy Proposal.', 404, 'PT_PROPOSAL_NOT_FOUND');
        }

        return array_merge($this->duLieuDeXuat($deXuat, false), [
            'current_plan' => $this->planQuery->hienTai($nguoiDung),
        ]);
    }

    /**
     * Member xác nhận Proposal PT đúng một lần.
     *
     * Transaction revalidate ownership, assignment nguồn, TTL, marker/base, hash,
     * bài/dụng cụ rồi tái sử dụng Workout services để tạo version và lịch tương lai.
     * Membership, usage, quota và LLM không tham gia workflow này.
     */
    public function xacNhan(NguoiDung $nguoiDung, int $deXuatId, string $khoaYeuCau): array
    {
        $this->damBaoUuid($khoaYeuCau);

        try {
            $ketQua = DB::transaction(function () use ($nguoiDung, $deXuatId, $khoaYeuCau): array {
                $hienTai = CarbonImmutable::now('UTC');
                $hoiVien = $this->scope->khoaHoiVienCuaNguoiDung($nguoiDung);
                $cacPhanCong = $this->scope->khoaCacPhanCong((int) $hoiVien->getKey());
                $deXuat = DeXuatKeHoachTap::query()
                    ->where('hoi_vien_id', $hoiVien->getKey())
                    ->where('nguon_de_xuat', 'HUAN_LUYEN_VIEN')
                    ->lockForUpdate()
                    ->find($deXuatId);
                if (! $deXuat instanceof DeXuatKeHoachTap) {
                    throw new PtWorkflowException('Không tìm thấy Proposal.', 404, 'PT_PROPOSAL_NOT_FOUND');
                }

                $maBamYeuCau = hash('sha256', $this->jsonChuan(['proposal_id' => $deXuatId]));
                $chongLap = $this->timChongLap($nguoiDung, self::PHAM_VI_XAC_NHAN, $khoaYeuCau);
                if ($chongLap instanceof YeuCauChongLap) {
                    return $this->xuLyLap($chongLap, $maBamYeuCau);
                }
                if ($deXuat->trang_thai === 'DA_AP_DUNG') {
                    return $this->ketQuaDaApDung($deXuat, true);
                }
                if ($deXuat->trang_thai !== 'CHO_XAC_NHAN') {
                    throw new PtWorkflowException('Proposal không còn chờ xác nhận.', 409, 'PT_PROPOSAL_NOT_PENDING');
                }
                if ($hienTai->greaterThanOrEqualTo($deXuat->het_han_luc)) {
                    $this->ketThuc(
                        $nguoiDung,
                        $deXuat,
                        'HET_HAN',
                        'HET_HAN_DE_XUAT_HUAN_LUYEN_VIEN',
                        'Proposal đã hết hạn.',
                        $khoaYeuCau,
                        $hienTai,
                    );

                    return $this->loiSauCommit('Proposal đã hết hạn.', 409, 'PT_PROPOSAL_EXPIRED');
                }

                try {
                    $this->scope->damBaoPhanCongNguon(
                        $cacPhanCong,
                        (int) $deXuat->phan_cong_huan_luyen_vien_id,
                        (int) $hoiVien->getKey(),
                        $hienTai,
                    );
                    $cauTruc = $this->revalidate($nguoiDung, $hoiVien, $deXuat, $hienTai);
                } catch (PtWorkflowException|WorkoutWorkflowException $exception) {
                    $maLoi = $exception instanceof PtWorkflowException ? $exception->safeCode : $exception->safeCode;
                    $this->ketThuc(
                        $nguoiDung,
                        $deXuat,
                        'XUNG_DOT',
                        'XUNG_DOT_DE_XUAT_HUAN_LUYEN_VIEN',
                        $exception->getMessage(),
                        $khoaYeuCau,
                        $hienTai,
                    );

                    return $this->loiSauCommit($exception->getMessage(), 409, $maLoi);
                }

                $chongLap = $this->taoChongLap(
                    $nguoiDung,
                    self::PHAM_VI_XAC_NHAN,
                    $khoaYeuCau,
                    $maBamYeuCau,
                    $hienTai,
                );
                $nguon = [
                    'proposal_id' => (int) $deXuat->getKey(),
                    'source' => 'HUAN_LUYEN_VIEN',
                    'creator_user_id' => (int) $deXuat->nguoi_tao_id,
                    'reason' => Str::limit((string) $deXuat->giai_thich, 1000, ''),
                ];
                if ($deXuat->loai_thay_doi === 'TAO_MOI') {
                    $keHoach = $this->plans->taoMoi($nguoiDung, $cauTruc, $khoaYeuCau, true, $nguon);
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
                    throw new PtWorkflowException('Không tạo được Plan Version.', 409, 'PT_PLAN_VERSION_CONFLICT');
                }

                $tuNgay = (string) $cauTruc['effective_from'];
                $denNgay = CarbonImmutable::parse($tuNgay, 'Asia/Ho_Chi_Minh')
                    ->addDays(self::SO_NGAY_LICH - 1)
                    ->toDateString();
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
                $this->audit->ghiProposal(
                    $nguoiDung,
                    $deXuat,
                    'AP_DUNG_DE_XUAT_HUAN_LUYEN_VIEN',
                    'CHO_XAC_NHAN',
                    'DA_AP_DUNG',
                    $khoaYeuCau,
                    $hienTai,
                    [
                        'plan_id' => (int) $keHoach->getKey(),
                        'version_id' => (int) $phienBan->getKey(),
                    ],
                );

                $duLieuKetQua = $this->duLieuApDung(
                    $deXuat,
                    $keHoach,
                    $phienBan,
                    $tuNgay,
                    $denNgay,
                    count($lich),
                    false,
                );
                $this->hoanTatChongLap($chongLap, 200, $duLieuKetQua);

                return $duLieuKetQua;
            }, 3);
        } catch (WorkoutWorkflowException $exception) {
            throw $this->loiWorkout($exception);
        }

        if (isset($ketQua['_error'])) {
            throw new PtWorkflowException($ketQua['message'], $ketQua['status'], $ketQua['code']);
        }

        return $ketQua;
    }

    /** Member từ chối Proposal pending; Plan/lịch và mọi quyền lợi không bị chạm. */
    public function tuChoi(
        NguoiDung $nguoiDung,
        int $deXuatId,
        string $khoaYeuCau,
        ?string $lyDo = null,
    ): array {
        $this->damBaoUuid($khoaYeuCau);
        $ketQua = DB::transaction(function () use ($nguoiDung, $deXuatId, $khoaYeuCau, $lyDo): array {
            $hienTai = CarbonImmutable::now('UTC');
            $hoiVien = $this->scope->khoaHoiVienCuaNguoiDung($nguoiDung);
            $deXuat = DeXuatKeHoachTap::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->where('nguon_de_xuat', 'HUAN_LUYEN_VIEN')
                ->lockForUpdate()
                ->find($deXuatId);
            if (! $deXuat instanceof DeXuatKeHoachTap) {
                throw new PtWorkflowException('Không tìm thấy Proposal.', 404, 'PT_PROPOSAL_NOT_FOUND');
            }

            $lyDoDaLoc = trim((string) ($lyDo ?? 'Hội viên từ chối Proposal.'));
            $maBamYeuCau = hash('sha256', $this->jsonChuan([
                'proposal_id' => $deXuatId,
                'reason' => $lyDoDaLoc,
            ]));
            $chongLap = $this->timChongLap($nguoiDung, self::PHAM_VI_TU_CHOI, $khoaYeuCau);
            if ($chongLap instanceof YeuCauChongLap) {
                return $this->xuLyLap($chongLap, $maBamYeuCau);
            }
            if ($deXuat->trang_thai === 'DA_TU_CHOI') {
                return $this->duLieuDeXuat($deXuat, true);
            }
            if ($deXuat->trang_thai !== 'CHO_XAC_NHAN') {
                throw new PtWorkflowException('Proposal không còn chờ quyết định.', 409, 'PT_PROPOSAL_NOT_PENDING');
            }
            if ($hienTai->greaterThanOrEqualTo($deXuat->het_han_luc)) {
                $this->ketThuc(
                    $nguoiDung,
                    $deXuat,
                    'HET_HAN',
                    'HET_HAN_DE_XUAT_HUAN_LUYEN_VIEN',
                    'Proposal đã hết hạn.',
                    $khoaYeuCau,
                    $hienTai,
                );

                return $this->loiSauCommit('Proposal đã hết hạn.', 409, 'PT_PROPOSAL_EXPIRED');
            }

            $chongLap = $this->taoChongLap(
                $nguoiDung,
                self::PHAM_VI_TU_CHOI,
                $khoaYeuCau,
                $maBamYeuCau,
                $hienTai,
            );
            $deXuat->forceFill([
                'trang_thai' => 'DA_TU_CHOI',
                'nguoi_quyet_dinh_id' => $nguoiDung->getKey(),
                'quyet_dinh_luc' => $hienTai,
                'ap_dung_luc' => null,
                'ly_do_ket_thuc' => Str::limit($lyDoDaLoc, 1000, ''),
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            $this->audit->ghiProposal(
                $nguoiDung,
                $deXuat,
                'TU_CHOI_DE_XUAT_HUAN_LUYEN_VIEN',
                'CHO_XAC_NHAN',
                'DA_TU_CHOI',
                $khoaYeuCau,
                $hienTai,
            );

            $duLieuKetQua = $this->duLieuDeXuat($deXuat, false);
            $this->hoanTatChongLap($chongLap, 200, $duLieuKetQua);

            return $duLieuKetQua;
        }, 3);

        if (isset($ketQua['_error'])) {
            throw new PtWorkflowException($ketQua['message'], $ketQua['status'], $ketQua['code']);
        }

        return $ketQua;
    }

    /** @return array{0:int|null,1:int|null} */
    private function nenKhiTao(string $loaiThayDoi, ?KeHoachTap $keHoachHienTai): array
    {
        if ($loaiThayDoi === 'TAO_MOI') {
            if ($keHoachHienTai instanceof KeHoachTap) {
                throw new PtWorkflowException('Hội viên đã có Plan đang sử dụng.', 409, 'PT_NEW_PLAN_CONFLICT');
            }

            return [null, null];
        }
        if (! in_array($loaiThayDoi, ['DIEU_CHINH', 'THAY_BAI'], true)
            || ! $keHoachHienTai instanceof KeHoachTap
            || $keHoachHienTai->phien_ban_hien_tai_id === null) {
            throw new PtWorkflowException('Không có base Plan phù hợp để đề xuất.', 409, 'PT_BASE_PLAN_REQUIRED');
        }

        return [(int) $keHoachHienTai->getKey(), (int) $keHoachHienTai->phien_ban_hien_tai_id];
    }

    /** @return array<string, mixed> */
    private function revalidate(
        NguoiDung $nguoiDung,
        HoSoHoiVien $hoiVien,
        DeXuatKeHoachTap $deXuat,
        CarbonImmutable $hienTai,
    ): array {
        if ($deXuat->phien_ban_cau_truc !== self::PHIEN_BAN_CAU_TRUC
            || ! is_array($deXuat->noi_dung_de_xuat)
            || ! hash_equals(
                (string) $deXuat->ma_bam_noi_dung,
                hash('sha256', $this->jsonChuan($deXuat->noi_dung_de_xuat)),
            )) {
            throw new PtWorkflowException('Nội dung Proposal không còn toàn vẹn.', 409, 'PT_PROPOSAL_INTEGRITY_CONFLICT');
        }
        if ((int) $deXuat->moc_thay_doi_ke_hoach_co_so !== (int) $hoiVien->moc_thay_doi_ke_hoach
            || (int) $deXuat->phien_ban_ho_so_co_so !== (int) $hoiVien->phien_ban_ho_so) {
            throw new PtWorkflowException('Hồ sơ hoặc kế hoạch đã thay đổi sau lúc tạo Proposal.', 409, 'PT_PROPOSAL_CONTEXT_STALE');
        }

        $cacKeHoach = KeHoachTap::query()
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $keHoachHienTai = $cacKeHoach->firstWhere('trang_thai', 'DANG_SU_DUNG');
        if ($deXuat->loai_thay_doi === 'TAO_MOI') {
            if ($deXuat->ke_hoach_tap_id !== null || $deXuat->phien_ban_co_so_id !== null || $keHoachHienTai !== null) {
                throw new PtWorkflowException('Đã có Plan mới sau lúc tạo Proposal.', 409, 'PT_PLAN_STALE');
            }
        } elseif (! $keHoachHienTai instanceof KeHoachTap
            || (int) $deXuat->ke_hoach_tap_id !== (int) $keHoachHienTai->getKey()
            || (int) $deXuat->phien_ban_co_so_id !== (int) $keHoachHienTai->phien_ban_hien_tai_id) {
            throw new PtWorkflowException('Base Plan Version đã thay đổi.', 409, 'PT_PLAN_STALE');
        }

        $cauTruc = $deXuat->noi_dung_de_xuat;
        $this->damBaoNgayApDung((string) ($cauTruc['effective_from'] ?? ''), $hienTai);
        $this->damBaoSnapshotGiaoAn($cauTruc);
        $this->plans->kiemTraCauTruc($nguoiDung, $cauTruc);

        return $cauTruc;
    }

    /** @param array<string, mixed> $duLieu */
    private function chuanHoaCauTruc(array $duLieu, bool $taoMaLogic): array
    {
        $plan = $duLieu['plan'];

        return [
            'name' => trim((string) $plan['name']),
            'goal' => trim((string) $plan['goal']),
            'effective_from' => (string) $duLieu['effective_from'],
            'template_id' => isset($plan['template_id']) ? (int) $plan['template_id'] : null,
            'template_name' => null,
            'days' => array_map(function (array $ngay) use ($taoMaLogic): array {
                return [
                    'logical_id' => $taoMaLogic ? (string) Str::uuid() : ($ngay['logical_id'] ?? null),
                    'order' => (int) $ngay['order'],
                    'weekday' => (int) $ngay['weekday'],
                    'name' => trim((string) $ngay['name']),
                    'estimated_minutes' => (int) $ngay['estimated_minutes'],
                    'exercises' => array_map(fn (array $bai): array => [
                        'exercise_id' => (int) $bai['exercise_id'],
                        'logical_id' => $taoMaLogic ? (string) Str::uuid() : ($bai['logical_id'] ?? null),
                        'order' => (int) $bai['order'],
                        'target_sets' => (int) $bai['target_sets'],
                        'min_reps' => (int) $bai['min_reps'],
                        'max_reps' => (int) $bai['max_reps'],
                        'target_weight_kg' => isset($bai['target_weight_kg'])
                            ? number_format((float) $bai['target_weight_kg'], 2, '.', '')
                            : null,
                        'rest_seconds' => (int) $bai['rest_seconds'],
                        'notes' => isset($bai['notes']) ? trim((string) $bai['notes']) : null,
                    ], $ngay['exercises']),
                ];
            }, $plan['days']),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuYeuCauTao(int $hoiVienId, array $duLieu): array
    {
        return [
            'member_id' => $hoiVienId,
            'change_type' => (string) $duLieu['change_type'],
            'title' => trim((string) $duLieu['title']),
            'explanation' => trim((string) $duLieu['explanation']),
            'plan' => $this->chuanHoaCauTruc($duLieu, false),
        ];
    }

    /** @param array<string, mixed> $cauTruc */
    private function ganSnapshotGiaoAn(array $cauTruc): array
    {
        if ($cauTruc['template_id'] === null) {
            return $cauTruc;
        }
        $giaoAn = GiaoAnMau::query()->lockForUpdate()->find($cauTruc['template_id']);
        if (! $giaoAn instanceof GiaoAnMau || $giaoAn->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Giáo án mẫu không còn hoạt động.', 422, 'PT_TEMPLATE_INVALID');
        }
        $cauTruc['template_name'] = $giaoAn->ten_giao_an;

        return $cauTruc;
    }

    /** @param array<string, mixed> $cauTruc */
    private function damBaoSnapshotGiaoAn(array $cauTruc): void
    {
        $giaoAnId = $cauTruc['template_id'] ?? null;
        if ($giaoAnId === null) {
            return;
        }
        $giaoAn = GiaoAnMau::query()->lockForUpdate()->find((int) $giaoAnId);
        if (! $giaoAn instanceof GiaoAnMau || $giaoAn->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Giáo án mẫu của Proposal không còn hoạt động.', 409, 'PT_TEMPLATE_STALE');
        }
    }

    private function damBaoNgayApDung(string $ngay, ?CarbonImmutable $hienTai = null): void
    {
        try {
            $ngayApDung = CarbonImmutable::createFromFormat('!Y-m-d', $ngay, 'Asia/Ho_Chi_Minh');
        } catch (\Throwable) {
            $ngayApDung = false;
        }
        $homNay = ($hienTai ?? CarbonImmutable::now('UTC'))->setTimezone('Asia/Ho_Chi_Minh')->startOfDay();
        if ($ngayApDung === false || $ngayApDung->format('Y-m-d') !== $ngay || $ngayApDung->lessThan($homNay)) {
            throw new PtWorkflowException('Ngày áp dụng phải là hôm nay hoặc tương lai.', 422, 'PT_EFFECTIVE_DATE_INVALID');
        }
    }

    private function ketThuc(
        NguoiDung $actor,
        DeXuatKeHoachTap $deXuat,
        string $trangThai,
        string $hanhDong,
        string $lyDo,
        string $khoaTuongQuan,
        CarbonImmutable $thoiDiem,
    ): void {
        $deXuat->forceFill([
            'trang_thai' => $trangThai,
            'nguoi_quyet_dinh_id' => $actor->getKey(),
            'quyet_dinh_luc' => $thoiDiem,
            'ap_dung_luc' => null,
            'ly_do_ket_thuc' => Str::limit($lyDo, 1000, ''),
            'ngay_cap_nhat' => $thoiDiem,
        ])->save();
        $this->audit->ghiProposal(
            $actor,
            $deXuat,
            $hanhDong,
            'CHO_XAC_NHAN',
            $trangThai,
            $khoaTuongQuan,
            $thoiDiem,
        );
    }

    private function timChongLap(NguoiDung $actor, string $phamVi, string $khoaYeuCau): ?YeuCauChongLap
    {
        return YeuCauChongLap::query()
            ->where('nguoi_dung_id', $actor->getKey())
            ->where('pham_vi', $phamVi)
            ->where('khoa_yeu_cau', $khoaYeuCau)
            ->lockForUpdate()
            ->first();
    }

    private function taoChongLap(
        NguoiDung $actor,
        string $phamVi,
        string $khoaYeuCau,
        string $maBam,
        CarbonImmutable $hienTai,
    ): YeuCauChongLap {
        return YeuCauChongLap::query()->create([
            'nguoi_dung_id' => $actor->getKey(),
            'pham_vi' => $phamVi,
            'khoa_yeu_cau' => $khoaYeuCau,
            'ma_bam_noi_dung' => $maBam,
            'trang_thai' => 'DANG_XU_LY',
            'ma_phan_hoi' => null,
            'ket_qua_da_loc' => null,
            'het_han_luc' => $hienTai->addHours(max(1, (int) config('ai.idempotency_ttl_hours', 24))),
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
    }

    /** @param array<string, mixed> $ketQua */
    private function hoanTatChongLap(YeuCauChongLap $chongLap, int $maPhanHoi, array $ketQua): void
    {
        $chongLap->forceFill([
            'trang_thai' => 'DA_HOAN_TAT',
            'ma_phan_hoi' => $maPhanHoi,
            'ket_qua_da_loc' => $ketQua,
            'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
        ])->save();
    }

    /** @return array<string, mixed> */
    private function xuLyLap(YeuCauChongLap $chongLap, string $maBam): array
    {
        if (! hash_equals((string) $chongLap->ma_bam_noi_dung, $maBam)) {
            throw new PtWorkflowException('Idempotency-Key đã dùng cho nội dung khác.', 409, 'IDEMPOTENCY_CONFLICT');
        }
        if ($chongLap->trang_thai !== 'DA_HOAN_TAT' || ! is_array($chongLap->ket_qua_da_loc)) {
            throw new PtWorkflowException('Yêu cầu đang được xử lý.', 409, 'IDEMPOTENCY_IN_PROGRESS');
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
            throw new PtWorkflowException('Proposal đã Apply nhưng thiếu Plan Version.', 409, 'PT_APPLY_STATE_CONFLICT');
        }
        $keHoach = KeHoachTap::query()->lockForUpdate()->findOrFail($phienBan->ke_hoach_tap_id);
        $tuNgay = $phienBan->ap_dung_tu_ngay->toDateString();
        $denNgay = CarbonImmutable::parse($tuNgay, 'Asia/Ho_Chi_Minh')
            ->addDays(self::SO_NGAY_LICH - 1)
            ->toDateString();
        $soLich = DB::table('buoi_tap_du_kien')
            ->where('phien_ban_ke_hoach_tap_id', $phienBan->getKey())
            ->count();

        return $this->duLieuApDung($deXuat, $keHoach, $phienBan, $tuNgay, $denNgay, $soLich, $replayed);
    }

    /** @return array<string, mixed> */
    private function duLieuApDung(
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
                    'effective_from' => $tuNgay,
                ],
            ],
            'schedule' => ['from' => $tuNgay, 'to' => $denNgay, 'items' => $soLich],
            'replayed' => $replayed,
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuDeXuat(DeXuatKeHoachTap $deXuat, bool $replayed): array
    {
        return [
            'id' => (int) $deXuat->getKey(),
            'member_id' => (int) $deXuat->hoi_vien_id,
            'source' => (string) $deXuat->nguon_de_xuat,
            'creator_user_id' => (int) $deXuat->nguoi_tao_id,
            'assignment_id' => (int) $deXuat->phan_cong_huan_luyen_vien_id,
            'base_plan_id' => $deXuat->ke_hoach_tap_id === null ? null : (int) $deXuat->ke_hoach_tap_id,
            'base_version_id' => $deXuat->phien_ban_co_so_id === null ? null : (int) $deXuat->phien_ban_co_so_id,
            'change_type' => (string) $deXuat->loai_thay_doi,
            'title' => (string) $deXuat->tieu_de,
            'explanation' => (string) $deXuat->giai_thich,
            'content' => $deXuat->noi_dung_de_xuat,
            'structure_version' => (string) $deXuat->phien_ban_cau_truc,
            'effective_from' => $deXuat->ap_dung_tu_ngay?->toDateString(),
            'status' => (string) $deXuat->trang_thai,
            'expires_at' => $deXuat->het_han_luc?->toISOString(),
            'decided_at' => $deXuat->quyet_dinh_luc?->toISOString(),
            'applied_at' => $deXuat->ap_dung_luc?->toISOString(),
            'terminal_reason' => $deXuat->ly_do_ket_thuc,
            'created_at' => $deXuat->ngay_tao?->toISOString(),
            'replayed' => $replayed,
        ];
    }

    private function damBaoUuid(string $giaTri): void
    {
        if (! Str::isUuid($giaTri)) {
            throw new PtWorkflowException('Idempotency-Key phải là UUID hợp lệ.', 422, 'INVALID_IDEMPOTENCY_KEY');
        }
    }

    /** @param array<string, mixed> $duLieu */
    private function jsonChuan(array $duLieu): string
    {
        $duLieu = $this->sapXepMang($duLieu);

        return json_encode($duLieu, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /** @param array<string, mixed> $duLieu */
    private function sapXepMang(array $duLieu): array
    {
        if (! array_is_list($duLieu)) {
            ksort($duLieu);
        }
        foreach ($duLieu as $khoa => $giaTri) {
            if (is_array($giaTri)) {
                $duLieu[$khoa] = $this->sapXepMang($giaTri);
            }
        }

        return $duLieu;
    }

    /** @return array<string, mixed> */
    private function loiSauCommit(string $message, int $status, string $code): array
    {
        return ['_error' => true, 'message' => $message, 'status' => $status, 'code' => $code];
    }

    private function loiWorkout(WorkoutWorkflowException $exception): PtWorkflowException
    {
        return new PtWorkflowException($exception->getMessage(), $exception->responseStatus, $exception->safeCode);
    }
}
