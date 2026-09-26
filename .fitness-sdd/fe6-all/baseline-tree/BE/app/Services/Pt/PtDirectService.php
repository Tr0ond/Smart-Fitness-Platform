<?php

namespace App\Services\Pt;

use App\Exceptions\MembershipLifecycleException;
use App\Exceptions\Pt\PtWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\KyHanHoiVien;
use App\Models\LichSuSuDungHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\SuDungQuyenLoi;
use App\Models\YeuCauChongLap;
use App\Services\MembershipActivationService;
use App\Services\MembershipEntitlementService;
use App\Services\MembershipLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PtDirectService
{
    private const PHAM_VI = 'XAC_NHAN_BUOI_HUAN_LUYEN';

    public function __construct(
        private readonly MembershipLifecycleService $vongDoi,
        private readonly MembershipEntitlementService $quyenLoi,
        private readonly MembershipActivationService $kichHoat,
    ) {}

    /**
     * Xác nhận một buổi PT đã hoàn thành bằng transaction duy nhất.
     *
     * Assignment, Membership và PT role đều được kiểm tra tại server time.
     * Member được khóa trước assignment, sau đó kỳ và usage; idempotency row,
     * usage, ledger, activation và counter cùng commit hoặc cùng rollback.
     * Idempotency-Key đồng thời là mã buổi ổn định trong schema hiện hành.
     *
     * @param  array{assignment_id:int,notes?:string|null}  $duLieu
     * @return array<string, mixed>
     */
    public function hoanTat(NguoiDung $pt, array $duLieu, string $khoaYeuCau): array
    {
        $hoSoHuanLuyenVien = $this->xacThucPt($pt);
        $assignmentId = (int) $duLieu['assignment_id'];
        $notes = array_key_exists('notes', $duLieu) && $duLieu['notes'] !== null
            ? trim((string) $duLieu['notes'])
            : null;
        $maBam = hash('sha256', json_encode([
            'assignment_id' => $assignmentId,
            'notes' => $notes,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use (
            $pt,
            $hoSoHuanLuyenVien,
            $assignmentId,
            $notes,
            $khoaYeuCau,
            $maBam,
        ): array {
            $assignmentBanDau = PhanCongHuanLuyenVien::query()->find($assignmentId);
            if (! $assignmentBanDau instanceof PhanCongHuanLuyenVien) {
                throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
            }

            // Lock order bắt buộc: Member → assignment rows → Membership/usage.
            $hoiVien = HoSoHoiVien::query()->with('nguoiDung')->lockForUpdate()->find($assignmentBanDau->hoi_vien_id);
            if (! $hoiVien instanceof HoSoHoiVien) {
                throw new PtWorkflowException('Không tìm thấy hội viên.', 404, 'MEMBER_NOT_FOUND');
            }
            if ($hoiVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
                throw new PtWorkflowException('Tài khoản hội viên không hoạt động.', 409, 'MEMBER_NOT_ACTIVE');
            }

            $cacPhanCong = PhanCongHuanLuyenVien::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $hoSoHuanLuyenVien = HoSoHuanLuyenVien::query()
                ->with('nguoiDung')
                ->lockForUpdate()
                ->find($hoSoHuanLuyenVien->getKey());
            if (! $hoSoHuanLuyenVien instanceof HoSoHuanLuyenVien
                || $hoSoHuanLuyenVien->trang_thai !== 'HOAT_DONG'
                || $hoSoHuanLuyenVien->nguoiDung?->trang_thai !== 'HOAT_DONG'
                || ! $this->nguoiDungCoVaiTro($pt, 'PT')) {
                throw new PtWorkflowException('PT không còn đủ điều kiện xác nhận buổi.', 403, 'TRAINER_ROLE_REQUIRED');
            }

            $yeuCauCu = YeuCauChongLap::query()
                ->where('nguoi_dung_id', $pt->getKey())
                ->where('pham_vi', self::PHAM_VI)
                ->where('khoa_yeu_cau', $khoaYeuCau)
                ->lockForUpdate()
                ->first();
            if ($yeuCauCu !== null) {
                if (! hash_equals((string) $yeuCauCu->ma_bam_noi_dung, $maBam)) {
                    throw new PtWorkflowException('Idempotency-Key đã được dùng cho dữ liệu khác.', 409, 'IDEMPOTENCY_CONFLICT');
                }
                if ($yeuCauCu->trang_thai === 'DA_HOAN_TAT' && is_array($yeuCauCu->ket_qua_da_loc)) {
                    return array_merge($yeuCauCu->ket_qua_da_loc, ['replayed' => true]);
                }
            } else {
                $hienTai = CarbonImmutable::now('UTC');
                $yeuCauCu = YeuCauChongLap::query()->create([
                    'nguoi_dung_id' => $pt->getKey(),
                    'pham_vi' => self::PHAM_VI,
                    'khoa_yeu_cau' => $khoaYeuCau,
                    'ma_bam_noi_dung' => $maBam,
                    'trang_thai' => 'DANG_XU_LY',
                    'ma_phan_hoi' => null,
                    'ket_qua_da_loc' => null,
                    'het_han_luc' => $hienTai->addHours(24),
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
            }

            /** @var PhanCongHuanLuyenVien|null $assignment */
            $assignment = $cacPhanCong->firstWhere('id', $assignmentId);
            if (! $assignment instanceof PhanCongHuanLuyenVien
                || (int) $assignment->huan_luyen_vien_id !== (int) $hoSoHuanLuyenVien->getKey()) {
                throw new PtWorkflowException('Không có quyền trên phân công này.', 404, 'ASSIGNMENT_NOT_FOUND');
            }

            $hienTai = CarbonImmutable::now('UTC');
            if (! $this->dangHieuLuc($assignment, $hienTai)) {
                throw new PtWorkflowException('Phân công không còn hiệu lực tại thời điểm xác nhận.', 409, 'ASSIGNMENT_NOT_ACTIVE');
            }

            try {
                $this->vongDoi->doiChieuHoiVien((int) $hoiVien->getKey(), $hienTai);
                $quyen = $this->quyenLoi->kiemTra(
                    (int) $hoiVien->getKey(),
                    MembershipEntitlementService::BUOI_HUAN_LUYEN,
                    $hienTai,
                    true,
                );
            } catch (MembershipLifecycleException $exception) {
                throw new PtWorkflowException('Membership không ở trạng thái có thể sử dụng.', 409, 'PT_ENTITLEMENT_DENIED');
            }
            if (! $quyen['allowed'] || $quyen['term_id'] === null) {
                $maLoi = $quyen['reason'] === 'KHONG_CO_QUYEN_HOAC_HET_HAN_MUC'
                    ? 'PT_QUOTA_EXHAUSTED'
                    : 'PT_ENTITLEMENT_DENIED';
                throw new PtWorkflowException('Kỳ Membership hiện tại không còn lượt PT trực tiếp.', 409, $maLoi);
            }

            $ky = KyHanHoiVien::query()->lockForUpdate()->find($quyen['term_id']);
            if (! $ky instanceof KyHanHoiVien
                || (int) $ky->hoi_vien_id !== (int) $hoiVien->getKey()
                || (int) $ky->so_buoi_huan_luyen_vien_da_dung >= (int) $ky->so_buoi_huan_luyen_vien) {
                throw new PtWorkflowException('Kỳ Membership không còn quota PT trực tiếp.', 409, 'PT_QUOTA_EXHAUSTED');
            }

            $suDung = SuDungQuyenLoi::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'ky_han_hoi_vien_id' => $ky->getKey(),
                'nguoi_thuc_hien_id' => $pt->getKey(),
                'loai_su_dung' => MembershipEntitlementService::BUOI_HUAN_LUYEN,
                'ma_hanh_dong' => $khoaYeuCau,
                'chap_nhan_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);

            try {
                $this->kichHoat->kichHoatNeuCan((int) $suDung->getKey());
            } catch (MembershipLifecycleException $exception) {
                throw new PtWorkflowException('Không thể kích hoạt Membership cho buổi PT.', 409, 'MEMBERSHIP_STATE_CONFLICT');
            }

            $ky = KyHanHoiVien::query()->lockForUpdate()->findOrFail($ky->getKey());
            if ((int) $ky->so_buoi_huan_luyen_vien_da_dung >= (int) $ky->so_buoi_huan_luyen_vien) {
                throw new PtWorkflowException('Đã hết quota PT trực tiếp.', 409, 'PT_QUOTA_EXHAUSTED');
            }

            $lichSu = LichSuSuDungHuanLuyenVien::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'huan_luyen_vien_id' => $hoSoHuanLuyenVien->getKey(),
                'phan_cong_huan_luyen_vien_id' => $assignment->getKey(),
                'ky_han_hoi_vien_id' => $ky->getKey(),
                'su_dung_quyen_loi_id' => $suDung->getKey(),
                'ma_buoi_huan_luyen' => $khoaYeuCau,
                'trang_thai' => 'HOAN_THANH',
                'so_luot_su_dung' => 1,
                'hoan_thanh_luc' => $hienTai,
                'xac_nhan_luc' => $hienTai,
                'nguon_thao_tac' => 'WEB_HUAN_LUYEN_VIEN',
                'ghi_chu' => $notes,
                'ngay_tao' => $hienTai,
            ]);
            $ky->forceFill([
                'so_buoi_huan_luyen_vien_da_dung' => (int) $ky->so_buoi_huan_luyen_vien_da_dung + 1,
                'ngay_cap_nhat' => $hienTai,
            ])->save();

            $ketQua = [
                'history_id' => (int) $lichSu->getKey(),
                'usage_id' => (int) $suDung->getKey(),
                'assignment_id' => (int) $assignment->getKey(),
                'member_id' => (int) $hoiVien->getKey(),
                'trainer_id' => (int) $hoSoHuanLuyenVien->getKey(),
                'term_id' => (int) $ky->getKey(),
                'status' => 'HOAN_THANH',
                'completed_at' => $hienTai->toISOString(),
                'confirmed_at' => $hienTai->toISOString(),
                'replayed' => false,
            ];
            $yeuCauCu->forceFill([
                'trang_thai' => 'DA_HOAN_TAT',
                'ma_phan_hoi' => 201,
                'ket_qua_da_loc' => $ketQua,
                'ngay_cap_nhat' => $hienTai,
            ])->save();

            return $ketQua;
        }, 3);
    }

    /** Member đọc lịch sử của chính mình; PT chỉ đọc dòng do chính PT xác nhận. */
    public function layLichSu(NguoiDung $nguoiDung): array
    {
        $truyVan = LichSuSuDungHuanLuyenVien::query()
            ->with(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung'])
            ->orderByDesc('xac_nhan_luc')
            ->orderByDesc('id');

        if ($this->nguoiDungCoVaiTro($nguoiDung, 'MEMBER')) {
            $hoiVienId = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->value('id');
            if ($hoiVienId === null) {
                throw new PtWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
            }
            $truyVan->where('hoi_vien_id', $hoiVienId);
        } elseif ($this->nguoiDungCoVaiTro($nguoiDung, 'PT')) {
            $huanLuyenVienId = HoSoHuanLuyenVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->value('id');
            if ($huanLuyenVienId === null) {
                throw new PtWorkflowException('Tài khoản chưa có hồ sơ huấn luyện viên.', 404, 'TRAINER_PROFILE_REQUIRED');
            }
            $truyVan->where('huan_luyen_vien_id', $huanLuyenVienId);
        } else {
            throw new PtWorkflowException('Không có quyền đọc lịch sử buổi PT.', 403, 'PT_HISTORY_ACCESS_DENIED');
        }

        return $truyVan->limit(100)->get()->map(fn (LichSuSuDungHuanLuyenVien $lichSu): array => [
            'history_id' => (int) $lichSu->getKey(),
            'assignment_id' => (int) $lichSu->phan_cong_huan_luyen_vien_id,
            'member_id' => (int) $lichSu->hoi_vien_id,
            'trainer_id' => (int) $lichSu->huan_luyen_vien_id,
            'term_id' => (int) $lichSu->ky_han_hoi_vien_id,
            'usage_id' => (int) $lichSu->su_dung_quyen_loi_id,
            'status' => $lichSu->trang_thai,
            'completed_at' => $lichSu->hoan_thanh_luc?->toISOString(),
            'confirmed_at' => $lichSu->xac_nhan_luc?->toISOString(),
            'notes' => $lichSu->ghi_chu,
        ])->values()->all();
    }

    private function xacThucPt(NguoiDung $pt): HoSoHuanLuyenVien
    {
        if ($pt->trang_thai !== 'HOAT_DONG' || ! $this->nguoiDungCoVaiTro($pt, 'PT')) {
            throw new PtWorkflowException('Chỉ PT đang hoạt động được xác nhận buổi.', 403, 'TRAINER_ROLE_REQUIRED');
        }
        $hoSo = HoSoHuanLuyenVien::query()->where('nguoi_dung_id', $pt->getKey())->first();
        if (! $hoSo instanceof HoSoHuanLuyenVien || $hoSo->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Hồ sơ PT không hoạt động.', 403, 'TRAINER_NOT_AVAILABLE');
        }

        return $hoSo;
    }

    private function nguoiDungCoVaiTro(NguoiDung $nguoiDung, string $maVaiTro): bool
    {
        return $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
            ->whereNull('thu_hoi_luc')
            ->whereHas('vaiTro', fn ($truyVan) => $truyVan->where('ma_vai_tro', $maVaiTro))
            ->exists();
    }

    private function dangHieuLuc(PhanCongHuanLuyenVien $assignment, CarbonImmutable $thoiDiem): bool
    {
        return CarbonImmutable::instance($assignment->ngay_bat_dau)->lessThanOrEqualTo($thoiDiem)
            && ($assignment->ngay_ket_thuc === null
                || $thoiDiem->lessThan(CarbonImmutable::instance($assignment->ngay_ket_thuc)));
    }
}
