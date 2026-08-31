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
use App\Support\EmailCanonicalizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TrainerOnboardingService
{
    private const PHAM_VI_CHONG_LAP = 'ADMIN_TRAINER_ONBOARDING';

    public function __construct(
        private readonly AdminActorGuard $adminGuard,
        private readonly EmailCanonicalizer $emailCanonicalizer,
    ) {}

    /**
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
            return DB::transaction(function () use ($actor, $duLieu, $email, $maBamNoiDung): array {
                $actorDaKhoa = $this->adminGuard->khoaVaDamBaoQuanTriVien($actor);
                $chongLap = $this->batDauHoacPhatLaiChongLap(
                    $actorDaKhoa,
                    (string) $duLieu['_idempotency_key'],
                    $maBamNoiDung,
                );
                if ($chongLap instanceof YeuCauChongLap) {
                    return [...$chongLap->ket_qua_da_loc, 'replayed' => true];
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
                $ketQua['invitation'] = 'QUEUED';
                $ketQua['replayed'] = false;
                $this->hoanTatChongLap($actorDaKhoa, (string) $duLieu['_idempotency_key'], $ketQua, $hienTai);

                return $ketQua;
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new AuthWorkflowException('Yêu cầu onboarding đang được xử lý hoặc email đã tồn tại.', 409, 'TRAINER_ONBOARDING_CONFLICT');
            }

            throw $exception;
        }
    }

    /** @param array{introduction?:string|null,specialties?:string|null,status?:string} $duLieu */
    public function onboardTaiKhoanDaCo(NguoiDung $actor, int $taiKhoanId, array $duLieu): array
    {
        return DB::transaction(function () use ($actor, $taiKhoanId, $duLieu): array {
            $cacTaiKhoan = $this->adminGuard->khoaActorVaDoiTuong($actor, $taiKhoanId);
            /** @var NguoiDung $taiKhoan */
            $taiKhoan = $cacTaiKhoan->get($taiKhoanId);
            if ($taiKhoan->trang_thai !== 'HOAT_DONG') {
                throw new AuthWorkflowException('Tài khoản phải đang hoạt động để onboarding PT.', 409, 'ACCOUNT_NOT_ACTIVE');
            }

            $ketQua = $this->onboardTrongGiaoDich(
                $cacTaiKhoan->get((int) $actor->getKey()),
                $taiKhoan,
                $duLieu,
                false,
                CarbonImmutable::now('UTC'),
            );
            $ketQua['invitation'] = 'NOT_REQUESTED';
            $ketQua['replayed'] = false;

            return $ketQua;
        }, 3);
    }

    /** @param array<string, mixed> $duLieu @return array<string, mixed> */
    private function onboardTrongGiaoDich(
        NguoiDung $actor,
        NguoiDung $taiKhoan,
        array $duLieu,
        bool $taiKhoanMoi,
        CarbonImmutable $hienTai,
    ): array {
        $vaiTroPt = VaiTro::query()->where('ma_vai_tro', 'PT')->lockForUpdate()->first();
        if (! $vaiTroPt instanceof VaiTro) {
            throw new AuthWorkflowException('Danh mục vai trò chưa sẵn sàng.', 503, 'ROLE_CONFIGURATION_REQUIRED');
        }

        $hoSo = HoSoHuanLuyenVien::query()
            ->where('nguoi_dung_id', $taiKhoan->getKey())
            ->lockForUpdate()
            ->first();
        $taoHoSo = ! $hoSo instanceof HoSoHuanLuyenVien;
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
        } else {
            $thayDoi = [];
            foreach (['introduction' => 'gioi_thieu', 'specialties' => 'chuyen_mon', 'status' => 'trang_thai'] as $truong => $cot) {
                if (array_key_exists($truong, $duLieu)) {
                    $thayDoi[$cot] = $truong === 'status'
                        ? $duLieu[$truong]
                        : $this->chuoiHoacNull($duLieu[$truong]);
                }
            }
            if ($thayDoi !== []) {
                $hoSo->forceFill([...$thayDoi, 'ngay_cap_nhat' => $hienTai])->save();
            }
        }

        $phanQuyen = PhanQuyenNguoiDung::query()
            ->where('nguoi_dung_id', $taiKhoan->getKey())
            ->where('vai_tro_id', $vaiTroPt->getKey())
            ->lockForUpdate()
            ->first();
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

        if ($taiKhoanMoi) {
            $this->ghiAudit($actor, 'TAO_TAI_KHOAN_PT', 'NGUOI_DUNG', (int) $taiKhoan->getKey(), $hienTai);
        }
        if ($taoHoSo) {
            $this->ghiAudit($actor, 'TAO_HO_SO_HUAN_LUYEN_VIEN', 'HO_SO_HUAN_LUYEN_VIEN', (int) $hoSo->getKey(), $hienTai);
        }
        if ($chuyenDoiVaiTro !== 'UNCHANGED') {
            $this->ghiAudit(
                $actor,
                $chuyenDoiVaiTro === 'GRANTED' ? 'CAP_VAI_TRO_PT' : 'CAP_LAI_VAI_TRO_PT',
                'PHAN_QUYEN_NGUOI_DUNG',
                (int) $phanQuyen->getKey(),
                $hienTai,
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

    private function batDauHoacPhatLaiChongLap(NguoiDung $actor, string $khoa, string $maBam): ?YeuCauChongLap
    {
        $hienTai = CarbonImmutable::now('UTC');
        $yeuCau = YeuCauChongLap::query()
            ->where('nguoi_dung_id', $actor->getKey())
            ->where('pham_vi', self::PHAM_VI_CHONG_LAP)
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
            'pham_vi' => self::PHAM_VI_CHONG_LAP,
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

    /** @param array<string, mixed> $ketQua */
    private function hoanTatChongLap(NguoiDung $actor, string $khoa, array $ketQua, CarbonImmutable $hienTai): void
    {
        YeuCauChongLap::query()
            ->where('nguoi_dung_id', $actor->getKey())
            ->where('pham_vi', self::PHAM_VI_CHONG_LAP)
            ->where('khoa_yeu_cau', $khoa)
            ->lockForUpdate()
            ->firstOrFail()
            ->forceFill([
                'trang_thai' => 'DA_HOAN_TAT',
                'ma_phan_hoi' => 201,
                'ket_qua_da_loc' => $ketQua,
                'ngay_cap_nhat' => $hienTai,
            ])
            ->save();
    }

    private function ghiAudit(NguoiDung $actor, string $hanhDong, string $loaiDoiTuong, int $dinhDanh, CarbonImmutable $hienTai): void
    {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => $hanhDong,
            'loai_doi_tuong' => $loaiDoiTuong,
            'dinh_danh_doi_tuong' => $dinhDanh,
            'khoa_tuong_quan' => (string) Str::uuid(),
            'du_lieu_truoc' => null,
            'du_lieu_sau' => null,
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
