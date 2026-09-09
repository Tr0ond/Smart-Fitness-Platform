<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanQuyenNguoiDung;
use App\Models\VaiTro;
use App\Models\YeuCauChongLap;
use App\Services\Auth\PasswordResetService;
use App\Support\EmailCanonicalizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class TrainerOnboardingService
{
    private const PHAM_VI_CHONG_LAP = 'ADMIN_TRAINER_ONBOARDING';

    private const PHAM_VI_CHONG_LAP_TAI_KHOAN_DA_CO = 'ADMIN_TRAINER_EXISTING_ACCOUNT';

    private const LOI_MOI_CHUA_XEP = 'NOT_QUEUED';

    public function __construct(
        private readonly AdminActorGuard $adminGuard,
        private readonly EmailCanonicalizer $emailCanonicalizer,
        private readonly PasswordResetService $passwordReset,
    ) {}

    /**
     * Tạo tài khoản PT trong phase 1 rồi khôi phục lời mời ở phase 2.
     *
     * Phase 1 khóa Admin, tạo account/profile/role/audit và ghi kết quả an toàn
     * vào yêu cầu chống lặp với trạng thái lời mời chưa xếp hàng. Phase 2 khóa
     * lại đúng hàng đó, xếp job đặt lại mật khẩu và chỉ lưu QUEUED sau khi
     * dispatcher chấp nhận. Retry cùng key chỉ chạy lại phase 2 khi cần.
     *
     * @param  array{name:string,email:string,phone?:string|null,introduction?:string|null,specialties?:string|null,status?:string,_idempotency_key:string}  $duLieu
     * @return array<string, mixed>
     */
    public function tao(NguoiDung $actor, array $duLieu): array
    {
        $email = $this->emailCanonicalizer->chuanHoa((string) $duLieu['email']);
        $maBamNoiDung = hash('sha256', json_encode([
            'name' => trim((string) $duLieu['name']),
            'email' => $email,
            'phone' => $this->chuoiHoacNull($duLieu['phone'] ?? null),
            'introduction' => $this->chuoiHoacNull($duLieu['introduction'] ?? null),
            'specialties' => $this->chuoiHoacNull($duLieu['specialties'] ?? null),
            'status' => (string) ($duLieu['status'] ?? 'HOAT_DONG'),
        ], JSON_THROW_ON_ERROR));

        try {
            $ketQua = DB::transaction(function () use ($actor, $duLieu, $email, $maBamNoiDung): array {
                $actorDaKhoa = $this->adminGuard->khoaVaDamBaoQuanTriVien($actor);
                $chongLap = $this->batDauHoacPhatLaiChongLap(
                    $actorDaKhoa,
                    self::PHAM_VI_CHONG_LAP,
                    (string) $duLieu['_idempotency_key'],
                    $maBamNoiDung,
                );
                if ($chongLap instanceof YeuCauChongLap) {
                    return $this->ketQuaNoiBoTuChongLap($chongLap, true);
                }

                if (NguoiDung::query()->where('thu_dien_tu', $email)->lockForUpdate()->exists()) {
                    throw new AuthWorkflowException('Email đã được sử dụng.', 409, 'ACCOUNT_EMAIL_ALREADY_EXISTS');
                }

                $hienTai = CarbonImmutable::now('UTC');
                $taiKhoan = NguoiDung::query()->create([
                    'chi_nhanh_id' => $actorDaKhoa->chi_nhanh_id,
                    'ho_ten' => trim((string) $duLieu['name']),
                    'thu_dien_tu' => $email,
                    'so_dien_thoai' => $this->chuoiHoacNull($duLieu['phone'] ?? null),
                    'mat_khau_bam' => Hash::make(bin2hex(random_bytes(32))),
                    'anh_dai_dien' => null,
                    'xac_minh_thu_luc' => null,
                    'trang_thai' => 'HOAT_DONG',
                    'dang_nhap_gan_nhat_luc' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                $ketQua = $this->onboardTrongGiaoDich($actorDaKhoa, $taiKhoan, $duLieu, true, $hienTai);
                $ketQua['invitation'] = self::LOI_MOI_CHUA_XEP;
                $ketQua['replayed'] = false;
                $this->hoanTatChongLap(
                    $actorDaKhoa,
                    self::PHAM_VI_CHONG_LAP,
                    (string) $duLieu['_idempotency_key'],
                    $ketQua,
                    $hienTai,
                    201,
                );

                return $ketQua;
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new AuthWorkflowException('Yêu cầu onboarding đang được xử lý hoặc email đã tồn tại.', 409, 'TRAINER_ONBOARDING_CONFLICT');
            }

            throw $exception;
        }

        return $this->xepLoiMoiNeuCan(
            $actor,
            (string) $duLieu['_idempotency_key'],
            $maBamNoiDung,
            (bool) $ketQua['replayed'],
        );
    }

    /**
     * Onboard hoặc cập nhật PT cho account hiện hữu bằng một logical-action key.
     *
     * Account target và actor bị khóa theo thứ tự cố định, branch/active state
     * được kiểm tra trước mọi profile/role mutation. Kết quả idempotency được
     * lưu cùng transaction; replay chỉ đọc lại DTO đã lọc và không gửi reset job.
     *
     * @param  array{introduction?:string|null,specialties?:string|null,status?:string,_idempotency_key:string}  $duLieu
     * @return array<string, mixed>
     */
    public function onboardTaiKhoanDaCo(NguoiDung $actor, int $taiKhoanId, array $duLieu): array
    {
        $maBamNoiDung = $this->maBamNoiDungTaiKhoanDaCo($taiKhoanId, $duLieu);

        return DB::transaction(function () use ($actor, $taiKhoanId, $duLieu, $maBamNoiDung): array {
            $cacTaiKhoan = $this->adminGuard->khoaActorVaDoiTuong($actor, $taiKhoanId);
            /** @var NguoiDung $taiKhoan */
            $taiKhoan = $cacTaiKhoan->get($taiKhoanId);
            /** @var NguoiDung $actorDaKhoa */
            $actorDaKhoa = $cacTaiKhoan->get((int) $actor->getKey());
            $this->damBaoCungChiNhanh($actorDaKhoa, $taiKhoan);

            $chongLap = $this->batDauHoacPhatLaiChongLap(
                $actorDaKhoa,
                self::PHAM_VI_CHONG_LAP_TAI_KHOAN_DA_CO,
                (string) $duLieu['_idempotency_key'],
                $maBamNoiDung,
            );
            if ($chongLap instanceof YeuCauChongLap) {
                return $this->ketQuaCongKhaiTuChongLap($chongLap, true);
            }

            if ($taiKhoan->trang_thai !== 'HOAT_DONG') {
                throw new AuthWorkflowException('Tài khoản phải đang hoạt động để onboarding PT.', 409, 'ACCOUNT_NOT_ACTIVE');
            }

            $ketQua = $this->onboardTrongGiaoDich(
                $actorDaKhoa,
                $taiKhoan,
                $duLieu,
                false,
                CarbonImmutable::now('UTC'),
            );
            $ketQua['invitation'] = 'NOT_REQUESTED';
            $ketQua['replayed'] = false;
            $this->hoanTatChongLap(
                $actorDaKhoa,
                self::PHAM_VI_CHONG_LAP_TAI_KHOAN_DA_CO,
                (string) $duLieu['_idempotency_key'],
                $ketQua,
                CarbonImmutable::now('UTC'),
                200,
            );

            return $this->ketQuaCongKhai($ketQua, false);
        }, 3);
    }

    /**
     * Đọc đầy đủ hồ sơ PT cho Admin trong cùng chi nhánh.
     *
     * Input là Admin đã xác thực và account ID số. Hàm revalidate quyền Admin,
     * lọc account theo chi nhánh của actor, đọc hồ sơ bằng một truy vấn chỉ
     * định rõ các cột được phép rồi trả DTO ổn định. Account thiếu, khác chi
     * nhánh hoặc chưa có hồ sơ dùng chung lỗi 404 và không ghi dữ liệu nào.
     *
     * @return array{account_id:int,trainer_profile_id:int,trainer_code:string,status:string,introduction:?string,specialties:?string,updated_at:?string}
     */
    public function hoSoHuanLuyenVien(NguoiDung $actor, int $taiKhoanId): array
    {
        $this->adminGuard->damBaoQuanTriVienHienTai($actor);

        $taiKhoan = NguoiDung::query()
            ->select(['id', 'chi_nhanh_id'])
            ->where('chi_nhanh_id', $actor->chi_nhanh_id)
            ->with('hoSoHuanLuyenVien:id,nguoi_dung_id,ma_huan_luyen_vien,trang_thai,gioi_thieu,chuyen_mon,ngay_cap_nhat')
            ->find($taiKhoanId);
        if (! $taiKhoan instanceof NguoiDung) {
            throw new AuthWorkflowException('Không tìm thấy hồ sơ huấn luyện viên.', 404, 'TRAINER_PROFILE_NOT_FOUND');
        }

        $hoSo = $taiKhoan->hoSoHuanLuyenVien;
        if (! $hoSo instanceof HoSoHuanLuyenVien) {
            throw new AuthWorkflowException('Không tìm thấy hồ sơ huấn luyện viên.', 404, 'TRAINER_PROFILE_NOT_FOUND');
        }

        return [
            'account_id' => (int) $taiKhoan->getKey(),
            'trainer_profile_id' => (int) $hoSo->getKey(),
            'trainer_code' => (string) $hoSo->ma_huan_luyen_vien,
            'status' => (string) $hoSo->trang_thai,
            'introduction' => $hoSo->gioi_thieu,
            'specialties' => $hoSo->chuyen_mon,
            'updated_at' => $hoSo->ngay_cap_nhat?->toISOString(),
        ];
    }

    /** @param array<string, mixed> $duLieu @return array<string, mixed> */
    private function onboardTrongGiaoDich(
        NguoiDung $actor,
        NguoiDung $taiKhoan,
        array $duLieu,
        bool $taiKhoanMoi,
        CarbonImmutable $hienTai,
    ): array {
        // Mot logical onboarding transaction co cung mot correlation UUID cho
        // moi audit; snapshot duoc chup truoc mutation va sau khi persistence.
        $khoaTuongQuan = (string) Str::uuid();
        $vaiTroPt = VaiTro::query()->where('ma_vai_tro', 'PT')->lockForUpdate()->first();
        if (! $vaiTroPt instanceof VaiTro) {
            throw new AuthWorkflowException('Danh mục vai trò chưa sẵn sàng.', 503, 'ROLE_CONFIGURATION_REQUIRED');
        }

        $hoSo = HoSoHuanLuyenVien::query()
            ->where('nguoi_dung_id', $taiKhoan->getKey())
            ->lockForUpdate()
            ->first();
        $taoHoSo = ! $hoSo instanceof HoSoHuanLuyenVien;
        $hoSoTruoc = $taoHoSo ? null : $this->snapshotHoSo($hoSo);
        $hoSoSau = null;
        if ($taoHoSo) {
            $hoSo = HoSoHuanLuyenVien::query()->create([
                'nguoi_dung_id' => $taiKhoan->getKey(),
                'ma_huan_luyen_vien' => 'PENDING_'.substr(str_replace('-', '', (string) Str::uuid()), 0, 16),
                'gioi_thieu' => $this->chuoiHoacNull($duLieu['introduction'] ?? null),
                'chuyen_mon' => $this->chuoiHoacNull($duLieu['specialties'] ?? null),
                'trang_thai' => (string) ($duLieu['status'] ?? 'HOAT_DONG'),
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            $hoSo->forceFill([
                'ma_huan_luyen_vien' => 'PT'.str_pad((string) $hoSo->getKey(), 6, '0', STR_PAD_LEFT),
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            $hoSoSau = $this->snapshotHoSo($hoSo->refresh());
        } else {
            $thayDoi = [];
            foreach (['introduction' => 'gioi_thieu', 'specialties' => 'chuyen_mon', 'status' => 'trang_thai'] as $truong => $cot) {
                if (array_key_exists($truong, $duLieu)) {
                    $giaTriMoi = $truong === 'status'
                        ? (string) $duLieu[$truong]
                        : $this->chuoiHoacNull($duLieu[$truong]);
                    $giaTriCu = $truong === 'status'
                        ? (string) $hoSo->{$cot}
                        : $this->chuoiHoacNull($hoSo->{$cot});
                    if ($giaTriMoi !== $giaTriCu) {
                        $thayDoi[$cot] = $giaTriMoi;
                    }
                }
            }
            if ($thayDoi !== []) {
                $hoSo->forceFill([...$thayDoi, 'ngay_cap_nhat' => $hienTai])->save();
                $hoSoSau = $this->snapshotHoSo($hoSo->refresh());
            }
        }

        $phanQuyen = PhanQuyenNguoiDung::query()
            ->where('nguoi_dung_id', $taiKhoan->getKey())
            ->where('vai_tro_id', $vaiTroPt->getKey())
            ->lockForUpdate()
            ->first();
        $phanQuyenTruoc = $phanQuyen === null ? null : $this->snapshotPhanQuyenPt($phanQuyen);
        $chuyenDoiVaiTro = 'UNCHANGED';
        if ($phanQuyen === null) {
            $phanQuyen = PhanQuyenNguoiDung::query()->create([
                'nguoi_dung_id' => $taiKhoan->getKey(),
                'vai_tro_id' => $vaiTroPt->getKey(),
                'nguoi_cap_id' => $actor->getKey(),
                'cap_luc' => $hienTai,
                'thu_hoi_luc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            $chuyenDoiVaiTro = 'GRANTED';
        } elseif ($phanQuyen->thu_hoi_luc !== null) {
            $phanQuyen->forceFill([
                'nguoi_cap_id' => $actor->getKey(),
                'cap_luc' => $hienTai,
                'thu_hoi_luc' => null,
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            $chuyenDoiVaiTro = 'REGRANTED';
        }

        $phanQuyenSau = $chuyenDoiVaiTro === 'UNCHANGED'
            ? null
            : $this->snapshotPhanQuyenPt($phanQuyen->refresh());

        if ($taiKhoanMoi) {
            $this->ghiAudit(
                $actor,
                'TAO_TAI_KHOAN_PT',
                'NGUOI_DUNG',
                (int) $taiKhoan->getKey(),
                $hienTai,
                null,
                $this->snapshotTaiKhoan($taiKhoan->refresh()),
                $khoaTuongQuan,
            );
        }
        if ($hoSoSau !== null) {
            $this->ghiAudit(
                $actor,
                $taoHoSo ? 'TAO_HO_SO_HUAN_LUYEN_VIEN' : 'CAP_NHAT_HO_SO_HUAN_LUYEN_VIEN',
                'HO_SO_HUAN_LUYEN_VIEN',
                (int) $hoSo->getKey(),
                $hienTai,
                $hoSoTruoc,
                $hoSoSau,
                $khoaTuongQuan,
            );
        }
        if ($chuyenDoiVaiTro !== 'UNCHANGED') {
            $this->ghiAudit(
                $actor,
                $chuyenDoiVaiTro === 'GRANTED' ? 'CAP_VAI_TRO_PT' : 'CAP_LAI_VAI_TRO_PT',
                'PHAN_QUYEN_NGUOI_DUNG',
                (int) $phanQuyen->getKey(),
                $hienTai,
                $phanQuyenTruoc,
                $phanQuyenSau,
                $khoaTuongQuan,
            );
        }

        return [
            'account' => [
                'id' => (int) $taiKhoan->getKey(),
                'email' => (string) $taiKhoan->thu_dien_tu,
                'status' => (string) $taiKhoan->trang_thai,
            ],
            'trainer_profile' => [
                'id' => (int) $hoSo->getKey(),
                'trainer_code' => (string) $hoSo->ma_huan_luyen_vien,
                'status' => (string) $hoSo->trang_thai,
            ],
            'role' => [
                'code' => 'PT',
                'active' => true,
                'transition' => $chuyenDoiVaiTro,
            ],
        ];
    }

    /**
     * Tạo hoặc khóa yêu cầu chống lặp của đúng logical-action scope.
     *
     * Existing completed rows are returned for replay; a different canonical
     * body is a conflict and every non-completed row remains in progress.
     */
    private function batDauHoacPhatLaiChongLap(
        NguoiDung $actor,
        string $phamVi,
        string $khoa,
        string $maBam,
    ): ?YeuCauChongLap {
        $hienTai = CarbonImmutable::now('UTC');
        $yeuCau = YeuCauChongLap::query()
            ->where('nguoi_dung_id', $actor->getKey())
            ->where('pham_vi', $phamVi)
            ->where('khoa_yeu_cau', $khoa)
            ->lockForUpdate()
            ->first();
        if ($yeuCau !== null) {
            if (! hash_equals((string) $yeuCau->ma_bam_noi_dung, $maBam)) {
                throw new AuthWorkflowException('Idempotency-Key đã dùng cho dữ liệu khác.', 409, 'IDEMPOTENCY_CONFLICT');
            }
            if ($yeuCau->trang_thai === 'DA_HOAN_TAT' && is_array($yeuCau->ket_qua_da_loc)) {
                return $yeuCau;
            }

            throw new AuthWorkflowException('Yêu cầu onboarding đang được xử lý.', 409, 'IDEMPOTENCY_IN_PROGRESS');
        }

        YeuCauChongLap::query()->create([
            'nguoi_dung_id' => $actor->getKey(),
            'pham_vi' => $phamVi,
            'khoa_yeu_cau' => $khoa,
            'ma_bam_noi_dung' => $maBam,
            'trang_thai' => 'DANG_XU_LY',
            'ma_phan_hoi' => null,
            'ket_qua_da_loc' => null,
            'het_han_luc' => $hienTai->addHours(24),
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);

        return null;
    }

    /**
     * Xếp job reset sau khi onboarding domain đã commit.
     *
     * The idempotency row is the recovery mutex. Dispatch happens while its
     * row lock is held, and QUEUED is persisted only after dispatch returns.
     * A queue exception leaves the committed domain rows and NOT_QUEUED result
     * intact for a later same-key retry.
     *
     * @return array<string, mixed>
     */
    private function xepLoiMoiNeuCan(
        NguoiDung $actor,
        string $khoa,
        string $maBam,
        bool $replayed,
    ): array {
        return DB::transaction(function () use ($actor, $khoa, $maBam, $replayed): array {
            $actorDaKhoa = $this->adminGuard->khoaVaDamBaoQuanTriVien($actor);
            $yeuCau = YeuCauChongLap::query()
                ->where('nguoi_dung_id', $actorDaKhoa->getKey())
                ->where('pham_vi', self::PHAM_VI_CHONG_LAP)
                ->where('khoa_yeu_cau', $khoa)
                ->lockForUpdate()
                ->first();

            if (! $yeuCau instanceof YeuCauChongLap) {
                throw new AuthWorkflowException(
                    'Yêu cầu onboarding đang được xử lý.',
                    409,
                    'IDEMPOTENCY_IN_PROGRESS',
                );
            }
            if (! hash_equals((string) $yeuCau->ma_bam_noi_dung, $maBam)) {
                throw new AuthWorkflowException(
                    'Idempotency-Key đã dùng cho dữ liệu khác.',
                    409,
                    'IDEMPOTENCY_CONFLICT',
                );
            }

            $ketQua = $this->ketQuaDaLuu($yeuCau);
            $trangThaiMoi = (string) ($ketQua['invitation'] ?? '');
            if ($trangThaiMoi === 'QUEUED') {
                return $this->ketQuaCongKhai($ketQua, $replayed);
            }
            if ($trangThaiMoi !== self::LOI_MOI_CHUA_XEP) {
                throw new AuthWorkflowException(
                    'Trạng thái lời mời onboarding không nhất quán.',
                    409,
                    'TRAINER_ONBOARDING_STATE_INVALID',
                );
            }

            $account = is_array($ketQua['account'] ?? null) ? $ketQua['account'] : [];
            $email = $account['email'] ?? null;
            if (! is_string($email) || $email === '') {
                throw new AuthWorkflowException(
                    'Trạng thái lời mời onboarding không nhất quán.',
                    409,
                    'TRAINER_ONBOARDING_STATE_INVALID',
                );
            }

            try {
                $this->passwordReset->yeuCau($email);
            } catch (AuthWorkflowException $exception) {
                throw $exception;
            } catch (Throwable) {
                throw new AuthWorkflowException(
                    'Hệ thống chưa thể tiếp nhận lời mời PT. Vui lòng thử lại sau.',
                    503,
                    'PASSWORD_RESET_QUEUE_UNAVAILABLE',
                );
            }

            $ketQua['invitation'] = 'QUEUED';
            $yeuCau->forceFill([
                'ket_qua_da_loc' => $this->ketQuaLuu($ketQua),
                'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
            ])->save();

            return $this->ketQuaCongKhai($ketQua, $replayed);
        }, 3);
    }

    /**
     * Lưu kết quả onboarding đã lọc, không bao gồm idempotency/recovery data.
     *
     * @param  array<string, mixed>  $ketQua
     * @return array<string, mixed>
     */
    private function ketQuaLuu(array $ketQua): array
    {
        return [
            'account' => $ketQua['account'],
            'trainer_profile' => $ketQua['trainer_profile'],
            'role' => $ketQua['role'],
            'invitation' => $ketQua['invitation'],
        ];
    }

    /** @return array<string, mixed> */
    private function ketQuaDaLuu(YeuCauChongLap $yeuCau): array
    {
        if ($yeuCau->trang_thai !== 'DA_HOAN_TAT' || ! is_array($yeuCau->ket_qua_da_loc)) {
            throw new AuthWorkflowException(
                'Yêu cầu onboarding đang được xử lý.',
                409,
                'IDEMPOTENCY_IN_PROGRESS',
            );
        }

        return $yeuCau->ket_qua_da_loc;
    }

    /** @return array<string, mixed> */
    private function ketQuaNoiBoTuChongLap(YeuCauChongLap $yeuCau, bool $replayed): array
    {
        $ketQua = $this->ketQuaDaLuu($yeuCau);
        $ketQua['replayed'] = $replayed;

        return $ketQua;
    }

    /** @return array<string, mixed> */
    private function ketQuaCongKhaiTuChongLap(YeuCauChongLap $yeuCau, bool $replayed): array
    {
        return $this->ketQuaCongKhai($this->ketQuaDaLuu($yeuCau), $replayed);
    }

    /**
     * Chỉ dựng DTO onboarding hiện hành từ các trường được phép công khai.
     *
     * @param  array<string, mixed>  $ketQua
     * @return array<string, mixed>
     */
    private function ketQuaCongKhai(array $ketQua, bool $replayed): array
    {
        $account = is_array($ketQua['account'] ?? null) ? $ketQua['account'] : [];
        $trainerProfile = is_array($ketQua['trainer_profile'] ?? null) ? $ketQua['trainer_profile'] : [];
        $role = is_array($ketQua['role'] ?? null) ? $ketQua['role'] : [];
        $invitation = $ketQua['invitation'] ?? null;

        if (
            ! isset($account['id'], $account['email'], $account['status'])
            || ! isset($trainerProfile['id'], $trainerProfile['trainer_code'], $trainerProfile['status'])
            || ! isset($role['code'], $role['active'], $role['transition'])
            || ! in_array($invitation, ['QUEUED', 'NOT_REQUESTED'], true)
        ) {
            throw new AuthWorkflowException(
                'Trạng thái onboarding không nhất quán.',
                409,
                'TRAINER_ONBOARDING_STATE_INVALID',
            );
        }

        return [
            'account' => [
                'id' => (int) $account['id'],
                'email' => (string) $account['email'],
                'status' => (string) $account['status'],
            ],
            'trainer_profile' => [
                'id' => (int) $trainerProfile['id'],
                'trainer_code' => (string) $trainerProfile['trainer_code'],
                'status' => (string) $trainerProfile['status'],
            ],
            'role' => [
                'code' => (string) $role['code'],
                'active' => (bool) $role['active'],
                'transition' => (string) $role['transition'],
            ],
            'invitation' => (string) $invitation,
            'replayed' => $replayed,
        ];
    }

    /**
     * Hoàn tất idempotency cùng domain transaction bằng DTO allow-list.
     *
     * @param  array<string, mixed>  $ketQua
     */
    private function hoanTatChongLap(
        NguoiDung $actor,
        string $phamVi,
        string $khoa,
        array $ketQua,
        CarbonImmutable $hienTai,
        int $maPhanHoi,
    ): void {
        YeuCauChongLap::query()
            ->where('nguoi_dung_id', $actor->getKey())
            ->where('pham_vi', $phamVi)
            ->where('khoa_yeu_cau', $khoa)
            ->lockForUpdate()
            ->firstOrFail()
            ->forceFill([
                'trang_thai' => 'DA_HOAN_TAT',
                'ma_phan_hoi' => $maPhanHoi,
                'ket_qua_da_loc' => $this->ketQuaLuu($ketQua),
                'ngay_cap_nhat' => $hienTai,
            ])
            ->save();
    }

    /**
     * Tạo hash profile ổn định cho account hiện hữu.
     *
     * Chỉ các field thực sự xuất hiện trong request được đưa vào payload;
     * vì vậy field bị bỏ qua khác với field nullable được gửi rõ là null.
     */
    private function maBamNoiDungTaiKhoanDaCo(int $taiKhoanId, array $duLieu): string
    {
        $profileInput = [];
        foreach (['introduction', 'specialties', 'status'] as $truong) {
            if (! array_key_exists($truong, $duLieu)) {
                continue;
            }

            $profileInput[$truong] = $truong === 'status'
                ? (string) $duLieu[$truong]
                : $this->chuoiHoacNull($duLieu[$truong]);
        }

        return hash('sha256', json_encode([
            'account_id' => $taiKhoanId,
            'profile' => $profileInput,
        ], JSON_THROW_ON_ERROR));
    }

    /** Cross-branch target phải bị che giấu trước khi profile/role bị thay đổi. */
    private function damBaoCungChiNhanh(NguoiDung $actor, NguoiDung $taiKhoan): void
    {
        if (
            $actor->chi_nhanh_id === null
            || $taiKhoan->chi_nhanh_id === null
            || (int) $actor->chi_nhanh_id !== (int) $taiKhoan->chi_nhanh_id
        ) {
            throw new AuthWorkflowException('Không tìm thấy tài khoản.', 404, 'ACCOUNT_NOT_FOUND');
        }
    }

    /** @return array<string, int|string|null> */
    private function snapshotTaiKhoan(NguoiDung $taiKhoan): array
    {
        return [
            'id' => (int) $taiKhoan->getKey(),
            'name' => (string) $taiKhoan->ho_ten,
            'email' => (string) $taiKhoan->thu_dien_tu,
            'branch_id' => $taiKhoan->chi_nhanh_id === null ? null : (int) $taiKhoan->chi_nhanh_id,
            'status' => (string) $taiKhoan->trang_thai,
        ];
    }

    /** @return array<string, int|string|null> */
    private function snapshotHoSo(HoSoHuanLuyenVien $hoSo): array
    {
        return [
            'id' => (int) $hoSo->getKey(),
            'account_id' => (int) $hoSo->nguoi_dung_id,
            'trainer_code' => (string) $hoSo->ma_huan_luyen_vien,
            'introduction' => $this->chuoiHoacNull($hoSo->gioi_thieu),
            'specialties' => $this->chuoiHoacNull($hoSo->chuyen_mon),
            'status' => (string) $hoSo->trang_thai,
        ];
    }

    /** @return array<string, int|string|bool|null> */
    private function snapshotPhanQuyenPt(PhanQuyenNguoiDung $phanQuyen): array
    {
        return [
            'assignment_id' => (int) $phanQuyen->getKey(),
            'account_id' => (int) $phanQuyen->nguoi_dung_id,
            'role' => 'PT',
            'granted_by_id' => $phanQuyen->nguoi_cap_id === null ? null : (int) $phanQuyen->nguoi_cap_id,
            'granted_at' => $phanQuyen->cap_luc?->format('Y-m-d H:i:s.u'),
            'revoked_at' => $phanQuyen->thu_hoi_luc?->format('Y-m-d H:i:s.u'),
            'active' => $phanQuyen->thu_hoi_luc === null,
        ];
    }

    /**
     * Ghi audit append-only trong cùng transaction voi account/profile/role.
     *
     * Snapshots la allow-list da chup truoc va sau persistence; correlation UUID
     * duoc truyen vao de gom cac transition cua cung logical onboarding action.
     *
     * @param  array<string, mixed>|null  $duLieuTruoc
     * @param  array<string, mixed>|null  $duLieuSau
     */
    private function ghiAudit(
        NguoiDung $actor,
        string $hanhDong,
        string $loaiDoiTuong,
        int $dinhDanh,
        CarbonImmutable $hienTai,
        ?array $duLieuTruoc,
        ?array $duLieuSau,
        string $khoaTuongQuan,
    ): void {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => $hanhDong,
            'loai_doi_tuong' => $loaiDoiTuong,
            'dinh_danh_doi_tuong' => $dinhDanh,
            'khoa_tuong_quan' => $khoaTuongQuan,
            'du_lieu_truoc' => $duLieuTruoc,
            'du_lieu_sau' => $duLieuSau,
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $hienTai,
            'ngay_tao' => $hienTai,
        ]);
    }

    private function chuoiHoacNull(mixed $giaTri): ?string
    {
        if ($giaTri === null) {
            return null;
        }

        $giaTri = trim((string) $giaTri);

        return $giaTri === '' ? null : $giaTri;
    }
}
