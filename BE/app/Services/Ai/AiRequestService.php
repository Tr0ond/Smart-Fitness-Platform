<?php

namespace App\Services\Ai;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Data\Ai\WorkoutAiResult;
use App\Exceptions\Ai\AiProviderException;
use App\Exceptions\Ai\AiWorkflowException;
use App\Models\BaiTap;
use App\Models\BaiTapUngVien;
use App\Models\DangKyGoiTap;
use App\Models\DeXuatKeHoachTap;
use App\Models\GiaoAnUngVien;
use App\Models\HoiThoaiTroLy;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use App\Models\LanGoiMoHinh;
use App\Models\NguoiDung;
use App\Models\SuDungQuyenLoi;
use App\Models\TinNhanTroLy;
use App\Models\YeuCauChongLap;
use App\Models\YeuCauTroLy;
use App\Services\MembershipActivationService;
use App\Services\MembershipEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AiRequestService
{
    private const PHAM_VI = 'TAO_YEU_CAU_TRO_LY';

    private const PHIEN_BAN_CAU_TRUC = 'workout-proposal-v1';

    private const PHIEN_BAN_MAU_LENH = 'workout-system-v1';

    public function __construct(
        private readonly WorkoutAiProvider $provider,
        private readonly AiRequestNormalizer $normalizer,
        private readonly AiCandidateRuleEngine $ruleEngine,
        private readonly AiStructuredOutputValidator $outputValidator,
        private readonly AiRequestQueryService $query,
        private readonly MembershipEntitlementService $entitlement,
        private readonly MembershipActivationService $activation,
    ) {}

    /**
     * Chấp nhận và xử lý đồng bộ một request workout AI trả phí của Member.
     *
     * Input gồm principal, payload đã qua HTTP validation và Idempotency-Key.
     * Backend normalize/scope-check, chứng minh có candidate, sau đó transaction
     * khóa Member → chuỗi/kỳ, giữ quota, ghi usage/request/candidate và kích hoạt
     * nếu cần. Provider được gọi sau commit, ngoài mọi khóa. Kết quả có cấu trúc
     * được validate và revalidate context trước khi tạo Proposal bất biến; mọi lỗi
     * kỹ thuật hoàn quota đúng một lần nhưng giữ usage/mốc kích hoạt đã commit.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function tao(NguoiDung $nguoiDung, array $input, string $khoaYeuCau): array
    {
        $yeuCauChuanHoa = $this->normalizer->chuanHoa($input);
        $maBam = hash('sha256', $this->jsonChuan($yeuCauChuanHoa));
        $hoiVien = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
        if ($hoiVien === null) {
            throw new AiWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
        }
        $this->damBaoNguoiDungMember((int) $nguoiDung->getKey());

        $yeuCauCu = YeuCauChongLap::query()
            ->where('nguoi_dung_id', $nguoiDung->getKey())
            ->where('pham_vi', self::PHAM_VI)
            ->where('khoa_yeu_cau', $khoaYeuCau)
            ->first();
        if ($yeuCauCu !== null) {
            return $this->xuLyYeuCauLap($nguoiDung, $yeuCauCu, $maBam);
        }

        // Preflight không ghi dữ liệu: request sai scope/context/candidate dừng trước quota và activation.
        $this->ruleEngine->taoNguCanh($hoiVien, $yeuCauChuanHoa);

        $chapNhan = DB::transaction(function () use (
            $nguoiDung,
            $hoiVien,
            $yeuCauChuanHoa,
            $khoaYeuCau,
            $maBam,
        ): array {
            $hoiVienDaKhoa = HoSoHoiVien::query()->lockForUpdate()->findOrFail($hoiVien->getKey());
            $this->damBaoNguoiDungMember((int) $nguoiDung->getKey());

            $yeuCauCu = YeuCauChongLap::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->where('pham_vi', self::PHAM_VI)
                ->where('khoa_yeu_cau', $khoaYeuCau)
                ->lockForUpdate()
                ->first();
            if ($yeuCauCu !== null) {
                return ['reused' => true, 'result' => $this->xuLyYeuCauLap($nguoiDung, $yeuCauCu, $maBam)];
            }

            $nguCanh = $this->ruleEngine->taoNguCanh($hoiVienDaKhoa, $yeuCauChuanHoa);
            $hienTai = CarbonImmutable::now('UTC');
            $quyen = $this->entitlement->kiemTra(
                (int) $hoiVienDaKhoa->getKey(),
                MembershipEntitlementService::YEU_CAU_TRO_LY,
                $hienTai,
                true,
            );
            if (! $quyen['allowed'] || $quyen['term_id'] === null) {
                $status = $quyen['reason'] === 'KHONG_CO_QUYEN_HOAC_HET_HAN_MUC' ? 429 : 403;
                $code = $status === 429 ? 'AI_QUOTA_EXHAUSTED' : 'AI_ENTITLEMENT_DENIED';
                throw new AiWorkflowException('Membership hiện tại không cấp lượt AI.', $status, $code);
            }

            $ky = KyHanHoiVien::query()->lockForUpdate()->findOrFail($quyen['term_id']);
            $chuoi = $ky->dang_ky_goi_tap_id === null
                ? null
                : DangKyGoiTap::query()->lockForUpdate()->find($ky->dang_ky_goi_tap_id);
            if ($chuoi === null || (int) $ky->hoi_vien_id !== (int) $hoiVienDaKhoa->getKey()) {
                throw new AiWorkflowException('Trạng thái Membership không nhất quán.', 409, 'MEMBERSHIP_STATE_CONFLICT');
            }
            if (! $ky->cho_phep_tro_ly_tap_luyen
                || ($ky->gioi_han_luot_tro_ly !== null
                    && (int) $ky->so_luot_tro_ly_da_dung + (int) $ky->so_luot_tro_ly_giu_cho >= (int) $ky->gioi_han_luot_tro_ly)) {
                throw new AiWorkflowException('Đã hết hạn mức AI của kỳ hiện tại.', 429, 'AI_QUOTA_EXHAUSTED');
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
            $hoiThoai = HoiThoaiTroLy::query()->create([
                'hoi_vien_id' => $hoiVienDaKhoa->getKey(),
                'tieu_de' => Str::limit($yeuCauChuanHoa['prompt'], 190, ''),
                'trang_thai' => 'DANG_MO',
                'so_thu_tu_cuoi' => 1,
            ]);
            $tinNhan = TinNhanTroLy::query()->create([
                'hoi_thoai_tro_ly_id' => $hoiThoai->getKey(),
                'so_thu_tu' => 1,
                'nguon_tin' => 'HOI_VIEN',
                'ma_tin_nhan_phia_gui' => $khoaYeuCau,
                'yeu_cau_tro_ly_id' => null,
                'noi_dung' => $yeuCauChuanHoa['prompt'],
                'gui_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);
            $suDung = SuDungQuyenLoi::query()->create([
                'hoi_vien_id' => $hoiVienDaKhoa->getKey(),
                'ky_han_hoi_vien_id' => $ky->getKey(),
                'nguoi_thuc_hien_id' => $nguoiDung->getKey(),
                'loai_su_dung' => MembershipEntitlementService::YEU_CAU_TRO_LY,
                'ma_hanh_dong' => $khoaYeuCau,
                'chap_nhan_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);
            $yeuCau = YeuCauTroLy::query()->create([
                'hoi_vien_id' => $hoiVienDaKhoa->getKey(),
                'hoi_thoai_tro_ly_id' => $hoiThoai->getKey(),
                'tin_nhan_dau_vao_id' => $tinNhan->getKey(),
                'ky_han_hoi_vien_id' => $ky->getKey(),
                'su_dung_quyen_loi_id' => $suDung->getKey(),
                'ma_yeu_cau' => $khoaYeuCau,
                'loai_yeu_cau' => $yeuCauChuanHoa['loai_yeu_cau'],
                'yeu_cau_chuan_hoa' => $yeuCauChuanHoa,
                'ngu_canh_da_chot' => $nguCanh,
                'phien_ban_quy_tac' => AiCandidateRuleEngine::VERSION,
                'trang_thai' => 'DANG_XU_LY',
                'trang_thai_han_muc' => 'GIU_CHO',
                'bat_dau_xu_ly_luc' => $hienTai,
                'hoan_tat_luc' => null,
                'ma_loi' => null,
            ]);

            foreach ($nguCanh['bai_tap_ung_vien'] as $baiTap) {
                BaiTapUngVien::query()->create([
                    'yeu_cau_tro_ly_id' => $yeuCau->getKey(),
                    'bai_tap_id' => $baiTap['id'],
                    'phien_ban_noi_dung' => $baiTap['phien_ban_noi_dung'],
                    'du_lieu_da_chot' => $baiTap,
                    'ly_do_phu_hop' => 'Đang hoạt động và thỏa toàn bộ dụng cụ bắt buộc.',
                    'ngay_tao' => $hienTai,
                ]);
            }
            foreach ($nguCanh['giao_an_ung_vien'] as $giaoAn) {
                GiaoAnUngVien::query()->create([
                    'yeu_cau_tro_ly_id' => $yeuCau->getKey(),
                    'giao_an_mau_id' => $giaoAn['id'],
                    'phien_ban_noi_dung' => $giaoAn['phien_ban_noi_dung'],
                    'du_lieu_da_chot' => $giaoAn,
                    'ly_do_phu_hop' => 'Số buổi mỗi tuần phù hợp hồ sơ.',
                    'ngay_tao' => $hienTai,
                ]);
            }

            // Activation phải nhìn counter trước reservation; cả hai vẫn cùng outer transaction.
            $this->activation->kichHoatNeuCan((int) $suDung->getKey());
            $ky = KyHanHoiVien::query()->lockForUpdate()->findOrFail($ky->getKey());
            if ($ky->gioi_han_luot_tro_ly !== null
                && (int) $ky->so_luot_tro_ly_da_dung + (int) $ky->so_luot_tro_ly_giu_cho >= (int) $ky->gioi_han_luot_tro_ly) {
                throw new AiWorkflowException('Đã hết hạn mức AI của kỳ hiện tại.', 429, 'AI_QUOTA_EXHAUSTED');
            }
            $ky->forceFill(['so_luot_tro_ly_giu_cho' => (int) $ky->so_luot_tro_ly_giu_cho + 1])->save();

            return [
                'reused' => false,
                'request_id' => (int) $yeuCau->getKey(),
                'idempotency_id' => (int) $chongLap->getKey(),
                'context' => $nguCanh,
            ];
        }, 3);

        if ($chapNhan['reused']) {
            return $chapNhan['result'];
        }

        $lanGoiId = null;
        try {
            $lanGoiId = $this->batDauLanGoi((int) $chapNhan['request_id']);
            $ketQua = $this->provider->generateStructuredProposal($this->nguCanhChoProvider(
                (int) $chapNhan['request_id'],
                $chapNhan['context'],
            ));
            $noiDung = $this->outputValidator->kiemTra($ketQua->structuredOutput, $chapNhan['context']);

            return $this->hoanTatThanhCong(
                (int) $chapNhan['request_id'],
                $lanGoiId,
                $ketQua,
                $noiDung,
            );
        } catch (AiWorkflowException $exception) {
            $this->hoanTraSauLoi((int) $chapNhan['request_id'], $lanGoiId, $exception);
            throw $exception;
        } catch (Throwable $exception) {
            $loi = new AiProviderException(
                'Không thể hoàn tất yêu cầu AI.',
                503,
                'AI_PROCESSING_FAILED',
                'THAT_BAI',
            );
            $this->hoanTraSauLoi((int) $chapNhan['request_id'], $lanGoiId, $loi);
            throw $loi;
        }
    }

    /**
     * Finalize kết quả provider theo trạng thái, an toàn khi gọi lặp.
     *
     * Hàm khóa lại owner/context/quota, từ chối output stale, tạo tối đa một
     * Proposal và chuyển GIU_CHO → DA_TINH đúng một lần. Nếu request đã hoàn tất,
     * hàm trả chính kết quả cũ, không overwrite Proposal hoặc tăng used lần nữa.
     *
     * @param  array<string, mixed>  $noiDung
     * @return array<string, mixed>
     */
    public function hoanTatThanhCong(
        int $yeuCauId,
        int $lanGoiId,
        WorkoutAiResult $ketQua,
        array $noiDung,
    ): array {
        return DB::transaction(function () use ($yeuCauId, $lanGoiId, $ketQua, $noiDung): array {
            $banDau = YeuCauTroLy::query()->findOrFail($yeuCauId);
            $hoiVien = HoSoHoiVien::query()->lockForUpdate()->findOrFail($banDau->hoi_vien_id);
            $this->damBaoNguoiDungMember((int) $hoiVien->nguoi_dung_id);
            $kyBanDau = KyHanHoiVien::query()->findOrFail($banDau->ky_han_hoi_vien_id);
            if ($kyBanDau->dang_ky_goi_tap_id !== null) {
                DangKyGoiTap::query()->lockForUpdate()->findOrFail($kyBanDau->dang_ky_goi_tap_id);
            }
            $ky = KyHanHoiVien::query()->lockForUpdate()->findOrFail($kyBanDau->getKey());
            $yeuCau = YeuCauTroLy::query()->lockForUpdate()->findOrFail($yeuCauId);
            $lanGoi = LanGoiMoHinh::query()->lockForUpdate()->findOrFail($lanGoiId);

            if ($yeuCau->trang_thai === 'THANH_CONG' && $yeuCau->trang_thai_han_muc === 'DA_TINH') {
                $yeuCau->load('deXuatKeHoachTap');

                return $this->query->duLieuYeuCau($yeuCau);
            }
            if ($yeuCau->trang_thai !== 'DANG_XU_LY' || $yeuCau->trang_thai_han_muc !== 'GIU_CHO') {
                throw new AiWorkflowException('Request không còn ở trạng thái chờ finalize.', 409, 'AI_REQUEST_FINALIZED');
            }

            $nguCanh = $yeuCau->ngu_canh_da_chot;
            $this->damBaoNguCanhConHieuLuc($hoiVien, $nguCanh, $noiDung);
            $hienTai = CarbonImmutable::now('UTC');
            if ((int) $ky->so_luot_tro_ly_giu_cho < 1) {
                throw new AiWorkflowException('Counter giữ chỗ không nhất quán.', 409, 'AI_QUOTA_STATE_CONFLICT');
            }

            $keHoach = $nguCanh['ke_hoach_hien_tai'];
            $noiDungChuan = $this->sapXepMang($noiDung);
            $deXuat = DeXuatKeHoachTap::query()->create([
                'hoi_vien_id' => $hoiVien->getKey(),
                'nguon_de_xuat' => 'TRO_LY',
                'nguoi_tao_id' => $hoiVien->nguoi_dung_id,
                'phan_cong_huan_luyen_vien_id' => null,
                'yeu_cau_tro_ly_id' => $yeuCau->getKey(),
                'ke_hoach_tap_id' => $keHoach['id'] ?? null,
                'phien_ban_co_so_id' => $keHoach['phien_ban_hien_tai_id'] ?? null,
                'moc_thay_doi_ke_hoach_co_so' => $nguCanh['ho_so']['moc_thay_doi_ke_hoach'],
                'phien_ban_ho_so_co_so' => $nguCanh['ho_so']['phien_ban_ho_so'],
                'loai_thay_doi' => $noiDungChuan['loai_thay_doi'],
                'tieu_de' => $noiDungChuan['tieu_de'],
                'giai_thich' => $noiDungChuan['giai_thich'],
                'noi_dung_de_xuat' => $noiDungChuan,
                'phien_ban_cau_truc' => self::PHIEN_BAN_CAU_TRUC,
                'ma_bam_noi_dung' => hash('sha256', $this->jsonChuan($noiDungChuan)),
                'ap_dung_tu_ngay' => $noiDungChuan['ap_dung_tu_ngay'],
                'trang_thai' => 'CHO_XAC_NHAN',
                'het_han_luc' => $hienTai->addHours(max(1, (int) config('ai.proposal_ttl_hours', 24))),
                'nguoi_quyet_dinh_id' => null,
                'quyet_dinh_luc' => null,
                'ap_dung_luc' => null,
                'ly_do_ket_thuc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);

            $hoiThoai = HoiThoaiTroLy::query()->lockForUpdate()->findOrFail($yeuCau->hoi_thoai_tro_ly_id);
            $soThuTu = (int) $hoiThoai->so_thu_tu_cuoi + 1;
            TinNhanTroLy::query()->create([
                'hoi_thoai_tro_ly_id' => $hoiThoai->getKey(),
                'so_thu_tu' => $soThuTu,
                'nguon_tin' => 'TRO_LY',
                'ma_tin_nhan_phia_gui' => (string) Str::uuid(),
                'yeu_cau_tro_ly_id' => $yeuCau->getKey(),
                'noi_dung' => $noiDungChuan['tieu_de']."\n\n".$noiDungChuan['giai_thich'],
                'gui_luc' => $hienTai,
                'ngay_tao' => $hienTai,
            ]);
            $hoiThoai->forceFill(['so_thu_tu_cuoi' => $soThuTu])->save();

            $ky->forceFill([
                'so_luot_tro_ly_giu_cho' => (int) $ky->so_luot_tro_ly_giu_cho - 1,
                'so_luot_tro_ly_da_dung' => (int) $ky->so_luot_tro_ly_da_dung + 1,
            ])->save();
            $yeuCau->forceFill([
                'trang_thai' => 'THANH_CONG',
                'trang_thai_han_muc' => 'DA_TINH',
                'hoan_tat_luc' => $hienTai,
                'ma_loi' => null,
            ])->save();
            $lanGoi->forceFill([
                'ma_yeu_cau_nha_cung_cap' => $ketQua->providerRequestId,
                'ma_bam_phan_hoi' => hash('sha256', $this->jsonChuan($ketQua->structuredOutput)),
                'ket_qua_cau_truc' => $ketQua->structuredOutput,
                'ket_qua_kiem_tra' => ['hop_le' => true, 'proposal_id' => (int) $deXuat->getKey()],
                'so_don_vi_dau_vao' => $ketQua->inputUnits,
                'so_don_vi_dau_ra' => $ketQua->outputUnits,
                'trang_thai' => 'THANH_CONG',
                'ket_thuc_luc' => $hienTai,
                'ma_loi' => null,
            ])->save();
            $this->hoanTatChongLap($yeuCau, 201, null, (int) $deXuat->getKey());

            return $this->query->duLieuYeuCau($yeuCau->fresh('deXuatKeHoachTap'));
        }, 3);
    }

    /**
     * Hoàn reservation sau lỗi kỹ thuật theo trạng thái, an toàn khi gọi lặp.
     *
     * GIU_CHO giảm reserved một lần; DA_TINH (nếu có lỗi kỹ thuật muộn) giảm used
     * một lần. Request chuyển DA_TRA/THAT_BAI nhưng usage và activation không bị
     * xóa hoặc lùi. Lần gọi tiếp theo thấy DA_TRA và không đổi counter.
     */
    public function hoanTraSauLoi(
        int $yeuCauId,
        ?int $lanGoiId,
        AiWorkflowException $exception,
    ): void {
        DB::transaction(function () use ($yeuCauId, $lanGoiId, $exception): void {
            $banDau = YeuCauTroLy::query()->findOrFail($yeuCauId);
            HoSoHoiVien::query()->lockForUpdate()->findOrFail($banDau->hoi_vien_id);
            $kyBanDau = KyHanHoiVien::query()->findOrFail($banDau->ky_han_hoi_vien_id);
            if ($kyBanDau->dang_ky_goi_tap_id !== null) {
                DangKyGoiTap::query()->lockForUpdate()->findOrFail($kyBanDau->dang_ky_goi_tap_id);
            }
            $ky = KyHanHoiVien::query()->lockForUpdate()->findOrFail($kyBanDau->getKey());
            $yeuCau = YeuCauTroLy::query()->lockForUpdate()->findOrFail($yeuCauId);
            if ($yeuCau->trang_thai_han_muc === 'DA_TRA') {
                return;
            }

            if ($yeuCau->trang_thai_han_muc === 'GIU_CHO') {
                if ((int) $ky->so_luot_tro_ly_giu_cho < 1) {
                    throw new AiWorkflowException('Không thể hoàn reservation bị thiếu.', 409, 'AI_QUOTA_STATE_CONFLICT');
                }
                $ky->forceFill(['so_luot_tro_ly_giu_cho' => (int) $ky->so_luot_tro_ly_giu_cho - 1])->save();
            } elseif ($yeuCau->trang_thai_han_muc === 'DA_TINH') {
                if ((int) $ky->so_luot_tro_ly_da_dung < 1) {
                    throw new AiWorkflowException('Không thể hoàn lượt đã dùng bị thiếu.', 409, 'AI_QUOTA_STATE_CONFLICT');
                }
                $ky->forceFill(['so_luot_tro_ly_da_dung' => (int) $ky->so_luot_tro_ly_da_dung - 1])->save();
            } else {
                throw new AiWorkflowException('Trạng thái quota không thể hoàn.', 409, 'AI_QUOTA_STATE_CONFLICT');
            }

            $hienTai = CarbonImmutable::now('UTC');
            $yeuCau->forceFill([
                'trang_thai' => 'THAT_BAI',
                'trang_thai_han_muc' => 'DA_TRA',
                'hoan_tat_luc' => $hienTai,
                'ma_loi' => $exception->safeCode,
            ])->save();
            if ($lanGoiId !== null) {
                $lanGoi = LanGoiMoHinh::query()->lockForUpdate()->find($lanGoiId);
                if ($lanGoi !== null && $lanGoi->trang_thai === 'DANG_GOI') {
                    $lanGoi->forceFill([
                        'ket_qua_cau_truc' => $exception->safeStructuredOutput,
                        'ket_qua_kiem_tra' => ['hop_le' => false, 'code' => $exception->safeCode],
                        'trang_thai' => $exception->modelCallStatus,
                        'ket_thuc_luc' => $hienTai,
                        'ma_loi' => $exception->safeCode,
                    ])->save();
                }
            }
            $this->hoanTatChongLap($yeuCau, $exception->responseStatus, $exception->safeCode, null);
        }, 3);
    }

    private function batDauLanGoi(int $yeuCauId): int
    {
        return DB::transaction(function () use ($yeuCauId): int {
            $yeuCau = YeuCauTroLy::query()->lockForUpdate()->findOrFail($yeuCauId);
            if ($yeuCau->trang_thai !== 'DANG_XU_LY' || $yeuCau->trang_thai_han_muc !== 'GIU_CHO') {
                throw new AiWorkflowException('Request không còn sẵn sàng gọi provider.', 409, 'AI_REQUEST_FINALIZED');
            }
            $soLan = (int) LanGoiMoHinh::query()->where('yeu_cau_tro_ly_id', $yeuCauId)->max('so_lan') + 1;
            $hienTai = CarbonImmutable::now('UTC');
            $lan = LanGoiMoHinh::query()->create([
                'yeu_cau_tro_ly_id' => $yeuCauId,
                'so_lan' => $soLan,
                'nha_cung_cap' => $this->provider->providerName(),
                'ten_mo_hinh' => $this->provider->modelName(),
                'ma_yeu_cau_nha_cung_cap' => null,
                'phien_ban_mau_lenh' => self::PHIEN_BAN_MAU_LENH,
                'phien_ban_cau_truc' => self::PHIEN_BAN_CAU_TRUC,
                'ma_bam_phan_hoi' => null,
                'ket_qua_cau_truc' => null,
                'ket_qua_kiem_tra' => null,
                'so_don_vi_dau_vao' => null,
                'so_don_vi_dau_ra' => null,
                'trang_thai' => 'DANG_GOI',
                'bat_dau_luc' => $hienTai,
                'ket_thuc_luc' => null,
                'ma_loi' => null,
            ]);

            return (int) $lan->getKey();
        }, 3);
    }

    /** @param array<string, mixed> $nguCanh @return array<string, mixed> */
    private function nguCanhChoProvider(int $yeuCauId, array $nguCanh): array
    {
        return [
            'request_id' => $yeuCauId,
            'schema_version' => self::PHIEN_BAN_CAU_TRUC,
            'request' => $nguCanh['yeu_cau'],
            'profile' => $nguCanh['ho_so'],
            'available_days' => $nguCanh['ngay_ranh'],
            'equipment' => $nguCanh['dung_cu'],
            'exercise_candidates' => $nguCanh['bai_tap_ung_vien'],
            'template_candidates' => $nguCanh['giao_an_ung_vien'],
            'current_plan' => $nguCanh['ke_hoach_hien_tai'],
            'policy' => [
                'user_text_is_untrusted' => true,
                'candidate_ids_only' => true,
                'structured_output_only' => true,
            ],
        ];
    }

    /** @param array<string, mixed> $nguCanh @param array<string, mixed> $noiDung */
    private function damBaoNguCanhConHieuLuc(HoSoHoiVien $hoiVien, array $nguCanh, array $noiDung): void
    {
        if ((int) $hoiVien->phien_ban_ho_so !== (int) $nguCanh['ho_so']['phien_ban_ho_so']
            || (int) $hoiVien->moc_thay_doi_ke_hoach !== (int) $nguCanh['ho_so']['moc_thay_doi_ke_hoach']) {
            throw new AiWorkflowException('Hồ sơ hoặc kế hoạch đã thay đổi trong lúc xử lý.', 409, 'AI_CONTEXT_STALE');
        }

        $dungCuHienTai = DB::table('dung_cu_hoi_vien')
            ->join('dung_cu', 'dung_cu.id', '=', 'dung_cu_hoi_vien.dung_cu_id')
            ->where('dung_cu_hoi_vien.hoi_vien_id', $hoiVien->getKey())
            ->where('dung_cu.trang_thai', 'HOAT_DONG')
            ->orderBy('dung_cu.id')
            ->pluck('dung_cu.id')->map(fn ($id): int => (int) $id)->all();
        $dungCuDaChot = array_map(fn (array $muc): int => (int) $muc['id'], $nguCanh['dung_cu']);
        if ($dungCuHienTai !== $dungCuDaChot) {
            throw new AiWorkflowException('Dụng cụ của hội viên đã thay đổi trong lúc xử lý.', 409, 'AI_EQUIPMENT_STALE');
        }

        $ngayHienTai = DB::table('ngay_ranh_hoi_vien')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->orderBy('thu_trong_tuan')
            ->pluck('thu_trong_tuan')->map(fn ($ngay): int => (int) $ngay)->all();
        if ($ngayHienTai !== array_map('intval', $nguCanh['ngay_ranh'])) {
            throw new AiWorkflowException('Ngày rảnh đã thay đổi trong lúc xử lý.', 409, 'AI_AVAILABILITY_STALE');
        }

        $keHoachHienTai = DB::table('ke_hoach_tap')
            ->where('hoi_vien_id', $hoiVien->getKey())
            ->where('trang_thai', 'DANG_SU_DUNG')
            ->orderBy('id')
            ->first(['id', 'phien_ban_hien_tai_id']);
        $keHoachDaChot = $nguCanh['ke_hoach_hien_tai'];
        if (($keHoachHienTai === null) !== ($keHoachDaChot === null)
            || ($keHoachHienTai !== null && (
                (int) $keHoachHienTai->id !== (int) $keHoachDaChot['id']
                || ($keHoachHienTai->phien_ban_hien_tai_id === null ? null : (int) $keHoachHienTai->phien_ban_hien_tai_id)
                    !== ($keHoachDaChot['phien_ban_hien_tai_id'] ?? null)
            ))) {
            throw new AiWorkflowException('Workout Plan đã thay đổi trong lúc xử lý.', 409, 'AI_PLAN_STALE');
        }

        $snapshot = collect($nguCanh['bai_tap_ung_vien'])->keyBy('id');
        $idDuocChon = collect($noiDung['ngay_trong_ke_hoach'])
            ->flatMap(fn (array $ngay) => collect($ngay['bai_tap_trong_ke_hoach'])->pluck('bai_tap_id'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $cacBaiTap = BaiTap::query()
            ->whereIn('id', $idDuocChon)
            ->with('baiTapDungCus')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        foreach ($idDuocChon as $id) {
            $baiTap = $cacBaiTap->get($id);
            $daChot = $snapshot->get($id);
            if (! $baiTap instanceof BaiTap
                || $daChot === null
                || $baiTap->trang_thai !== 'HOAT_DONG'
                || (int) $baiTap->phien_ban_noi_dung !== (int) $daChot['phien_ban_noi_dung']
                || array_diff(
                    $baiTap->baiTapDungCus->pluck('dung_cu_id')->map(fn ($muc): int => (int) $muc)->all(),
                    $dungCuHienTai,
                ) !== []) {
                throw new AiWorkflowException('Candidate không còn hợp lệ ở thời điểm công bố.', 409, 'AI_CANDIDATE_STALE');
            }
        }
    }

    /** @return array<string, mixed> */
    private function xuLyYeuCauLap(NguoiDung $nguoiDung, YeuCauChongLap $yeuCau, string $maBam): array
    {
        if (! hash_equals((string) $yeuCau->ma_bam_noi_dung, $maBam)) {
            throw new AiWorkflowException('Idempotency-Key đã được dùng cho yêu cầu khác.', 409, 'IDEMPOTENCY_CONFLICT');
        }
        if ($yeuCau->trang_thai === 'DANG_XU_LY') {
            throw new AiWorkflowException('Yêu cầu AI đang được xử lý.', 409, 'IDEMPOTENCY_IN_PROGRESS');
        }
        $requestId = (int) ($yeuCau->ket_qua_da_loc['request_id'] ?? 0);
        if ($yeuCau->trang_thai === 'DA_HOAN_TAT' && $requestId > 0) {
            return $this->query->chiTiet($nguoiDung, $requestId);
        }

        $status = (int) $yeuCau->ma_phan_hoi;
        if ($status < 400 || $status > 599) {
            $status = 503;
        }
        throw new AiWorkflowException(
            'Yêu cầu AI trước đó đã kết thúc với lỗi.',
            $status,
            (string) ($yeuCau->ket_qua_da_loc['code'] ?? 'AI_PROCESSING_FAILED'),
        );
    }

    private function hoanTatChongLap(
        YeuCauTroLy $yeuCau,
        int $httpStatus,
        ?string $maLoi,
        ?int $deXuatId,
    ): void {
        $chongLap = YeuCauChongLap::query()
            ->where('nguoi_dung_id', $yeuCau->hoiVien->nguoi_dung_id)
            ->where('pham_vi', self::PHAM_VI)
            ->where('khoa_yeu_cau', $yeuCau->ma_yeu_cau)
            ->lockForUpdate()
            ->firstOrFail();
        $chongLap->forceFill([
            'trang_thai' => $maLoi === null ? 'DA_HOAN_TAT' : 'THAT_BAI',
            'ma_phan_hoi' => $httpStatus,
            'ket_qua_da_loc' => array_filter([
                'request_id' => (int) $yeuCau->getKey(),
                'proposal_id' => $deXuatId,
                'code' => $maLoi,
            ], fn ($giaTri): bool => $giaTri !== null),
        ])->save();
    }

    private function damBaoNguoiDungMember(int $nguoiDungId): void
    {
        $hopLe = DB::table('nguoi_dung')
            ->join('phan_quyen_nguoi_dung', 'phan_quyen_nguoi_dung.nguoi_dung_id', '=', 'nguoi_dung.id')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('nguoi_dung.id', $nguoiDungId)
            ->where('nguoi_dung.trang_thai', 'HOAT_DONG')
            ->where('vai_tro.ma_vai_tro', 'MEMBER')
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc')
            ->exists();
        if (! $hopLe) {
            throw new AiWorkflowException('Tài khoản không có quyền MEMBER đang hiệu lực.', 403, 'MEMBER_ROLE_REQUIRED');
        }
    }

    /** @param array<string, mixed> $duLieu */
    private function jsonChuan(array $duLieu): string
    {
        return json_encode($this->sapXepMang($duLieu), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $duLieu @return array<string, mixed> */
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
}
