<?php

namespace App\Services\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanQuyenNguoiDung;
use App\Models\VaiTro;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleManagementService
{
    private const CAC_VAI_TRO = ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'];

    public function __construct(private readonly AdminActorGuard $guard) {}

    /**
     * Grant mới hoặc regrant cùng hàng đã thu hồi. Grant đang active là no-op
     * nên retry không sinh thêm hàng hoặc audit thành công trùng.
     *
     * @return array<string, mixed>
     */
    public function gan(NguoiDung $actor, int $taiKhoanId, string $maVaiTro): array
    {
        $maVaiTro = strtoupper($maVaiTro);
        $vaiTro = $this->vaiTro($maVaiTro);

        try {
            return DB::transaction(function () use ($actor, $taiKhoanId, $vaiTro, $maVaiTro): array {
                $cacTaiKhoan = $this->guard->khoaActorVaDoiTuong($actor, $taiKhoanId);
                if ($maVaiTro === 'PT' && ! $cacTaiKhoan->get($taiKhoanId)
                    ->hoSoHuanLuyenVien()
                    ->lockForUpdate()
                    ->exists()) {
                    throw new AuthWorkflowException(
                        'Cần hoàn tất onboarding hồ sơ huấn luyện viên.',
                        409,
                        'TRAINER_PROFILE_REQUIRED',
                    );
                }
                $phanQuyen = PhanQuyenNguoiDung::query()
                    ->where('nguoi_dung_id', $taiKhoanId)
                    ->where('vai_tro_id', $vaiTro->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($phanQuyen !== null && $phanQuyen->thu_hoi_luc === null) {
                    return $this->ketQua($phanQuyen, $maVaiTro, false, 'UNCHANGED');
                }

                $hienTai = CarbonImmutable::now('UTC');
                $truoc = $phanQuyen === null ? null : $this->snapshot($phanQuyen, $maVaiTro);
                if ($phanQuyen === null) {
                    $phanQuyen = PhanQuyenNguoiDung::query()->create([
                        'nguoi_dung_id' => $taiKhoanId,
                        'vai_tro_id' => $vaiTro->getKey(),
                        'nguoi_cap_id' => $actor->getKey(),
                        'cap_luc' => $hienTai,
                        'thu_hoi_luc' => null,
                        'ngay_tao' => $hienTai,
                        'ngay_cap_nhat' => $hienTai,
                    ]);
                    $hanhDong = 'CAP_VAI_TRO';
                    $transition = 'GRANTED';
                } else {
                    $phanQuyen->forceFill([
                        'nguoi_cap_id' => $actor->getKey(),
                        'cap_luc' => $hienTai,
                        'thu_hoi_luc' => null,
                        'ngay_cap_nhat' => $hienTai,
                    ])->save();
                    $hanhDong = 'CAP_LAI_VAI_TRO';
                    $transition = 'REGRANTED';
                }

                $this->ghiAudit($actor, $phanQuyen, $hanhDong, $truoc, $this->snapshot($phanQuyen, $maVaiTro), $hienTai);

                return $this->ketQua($phanQuyen->refresh(), $maVaiTro, true, $transition);
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new AuthWorkflowException('Vai trò đã được xử lý bởi yêu cầu khác.', 409, 'ROLE_CONFLICT');
            }

            throw $exception;
        }
    }

    /**
     * Thu hồi bằng UPDATE thu_hoi_luc. Retry hàng đã revoked là no-op.
     *
     * @return array<string, mixed>
     */
    public function thuHoi(NguoiDung $actor, int $taiKhoanId, string $maVaiTro): array
    {
        $maVaiTro = strtoupper($maVaiTro);
        $vaiTro = $this->vaiTro($maVaiTro);

        return DB::transaction(function () use ($actor, $taiKhoanId, $vaiTro, $maVaiTro): array {
            $cacTaiKhoan = $this->guard->khoaActorVaDoiTuong($actor, $taiKhoanId);
            $phanQuyen = PhanQuyenNguoiDung::query()
                ->where('nguoi_dung_id', $taiKhoanId)
                ->where('vai_tro_id', $vaiTro->getKey())
                ->lockForUpdate()
                ->first();

            if ($phanQuyen === null) {
                throw new AuthWorkflowException('Tài khoản chưa từng được cấp vai trò này.', 404, 'ROLE_ASSIGNMENT_NOT_FOUND');
            }
            if ($phanQuyen->thu_hoi_luc !== null) {
                return $this->ketQua($phanQuyen, $maVaiTro, false, 'UNCHANGED');
            }

            if ($maVaiTro === 'ADMIN') {
                $this->guard->damBaoKhongVoHieuHoaAdminHoatDongCuoiCung(
                    $cacTaiKhoan->get($taiKhoanId),
                );
            }

            $hienTai = CarbonImmutable::now('UTC');
            $truoc = $this->snapshot($phanQuyen, $maVaiTro);
            $phanQuyen->forceFill([
                'thu_hoi_luc' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            $this->ghiAudit(
                $actor,
                $phanQuyen,
                'THU_HOI_VAI_TRO',
                $truoc,
                $this->snapshot($phanQuyen, $maVaiTro),
                $hienTai,
            );

            return $this->ketQua($phanQuyen->refresh(), $maVaiTro, true, 'REVOKED');
        }, 3);
    }

    private function vaiTro(string $maVaiTro): VaiTro
    {
        if (! in_array($maVaiTro, self::CAC_VAI_TRO, true)) {
            throw new AuthWorkflowException('Vai trò không hợp lệ.', 422, 'INVALID_ROLE');
        }

        $vaiTro = VaiTro::query()->where('ma_vai_tro', $maVaiTro)->first();
        if ($vaiTro === null) {
            throw new AuthWorkflowException('Danh mục vai trò chưa sẵn sàng.', 503, 'ROLE_CONFIGURATION_REQUIRED');
        }

        return $vaiTro;
    }

    /** @param array<string, mixed>|null $truoc @param array<string, mixed> $sau */
    private function ghiAudit(
        NguoiDung $actor,
        PhanQuyenNguoiDung $phanQuyen,
        string $hanhDong,
        ?array $truoc,
        array $sau,
        CarbonImmutable $hienTai,
    ): void {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => $hanhDong,
            'loai_doi_tuong' => 'PHAN_QUYEN_NGUOI_DUNG',
            'dinh_danh_doi_tuong' => $phanQuyen->getKey(),
            'khoa_tuong_quan' => (string) Str::uuid(),
            'du_lieu_truoc' => $truoc,
            'du_lieu_sau' => $sau,
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $hienTai,
            'ngay_tao' => $hienTai,
        ]);
    }

    /** @return array<string, int|string|null> */
    private function snapshot(PhanQuyenNguoiDung $phanQuyen, string $maVaiTro): array
    {
        return [
            'id' => (int) $phanQuyen->getKey(),
            'nguoi_dung_id' => (int) $phanQuyen->nguoi_dung_id,
            'vai_tro_id' => (int) $phanQuyen->vai_tro_id,
            'ma_vai_tro' => $maVaiTro,
            'nguoi_cap_id' => $phanQuyen->nguoi_cap_id === null ? null : (int) $phanQuyen->nguoi_cap_id,
            'cap_luc' => $phanQuyen->cap_luc?->format('Y-m-d H:i:s.u'),
            'thu_hoi_luc' => $phanQuyen->thu_hoi_luc?->format('Y-m-d H:i:s.u'),
        ];
    }

    /** @return array<string, mixed> */
    private function ketQua(
        PhanQuyenNguoiDung $phanQuyen,
        string $maVaiTro,
        bool $daThayDoi,
        string $transition,
    ): array {
        return [
            'assignment_id' => (int) $phanQuyen->getKey(),
            'account_id' => (int) $phanQuyen->nguoi_dung_id,
            'role' => $maVaiTro,
            'active' => $phanQuyen->thu_hoi_luc === null,
            'granted_by_id' => $phanQuyen->nguoi_cap_id === null ? null : (int) $phanQuyen->nguoi_cap_id,
            'granted_at' => $phanQuyen->cap_luc?->toISOString(),
            'revoked_at' => $phanQuyen->thu_hoi_luc?->toISOString(),
            'created_at' => $phanQuyen->ngay_tao?->toISOString(),
            'updated_at' => $phanQuyen->ngay_cap_nhat?->toISOString(),
            'changed' => $daThayDoi,
            'transition' => $transition,
        ];
    }
}
