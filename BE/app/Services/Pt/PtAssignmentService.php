<?php

namespace App\Services\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PtAssignmentService
{
    /** Admin là actor duy nhất được PROJECT_RULES cấp quyền quản lý phân công. */
    private const VAI_TRO_QUAN_LY = 'ADMIN';

    /** Tạo một khoảng phân công, khóa Member trước toàn bộ assignment của Member. */
    public function tao(NguoiDung $nguoiDung, array $duLieu): array
    {
        $this->damBaoQuanLy($nguoiDung);
        $hoiVienId = (int) $duLieu['member_id'];
        $huanLuyenVienId = (int) $duLieu['trainer_id'];
        $ngayBatDau = $this->thoiDiem($duLieu['start_at'] ?? null) ?? CarbonImmutable::now('UTC');
        $ngayKetThuc = $this->thoiDiem($duLieu['end_at'] ?? null);
        $this->damBaoKhoangHopLe($ngayBatDau, $ngayKetThuc);

        try {
            return DB::transaction(function () use (
                $nguoiDung,
                $hoiVienId,
                $huanLuyenVienId,
                $ngayBatDau,
                $ngayKetThuc,
            ): array {
                $hoiVien = $this->khoaHoiVienHopLe($hoiVienId);
                $cacPhanCong = $this->khoaCacPhanCong($hoiVienId);
                $this->khoaVaXacThucHuanLuyenVien($huanLuyenVienId);
                $this->damBaoKhongChongKhoang($cacPhanCong, $ngayBatDau, $ngayKetThuc);

                $phanCong = PhanCongHuanLuyenVien::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'huan_luyen_vien_id' => $huanLuyenVienId,
                    'nguoi_phan_cong_id' => $nguoiDung->getKey(),
                    'ngay_bat_dau' => $ngayBatDau,
                    'ngay_ket_thuc' => $ngayKetThuc,
                    'ly_do_ket_thuc' => null,
                    'ngay_tao' => CarbonImmutable::now('UTC'),
                    'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
                ]);

                return $this->duLieuPhanCong($phanCong->refresh());
            }, 3);
        } catch (QueryException $exception) {
            throw new PtWorkflowException(
                'Khoảng phân công xung đột hoặc không còn hợp lệ.',
                409,
                'ASSIGNMENT_CONFLICT',
            );
        }
    }

    /** Kết thúc assignment tại server time; không xóa hàng lịch sử. */
    public function ketThuc(NguoiDung $nguoiDung, int $phanCongId, ?string $lyDo = null): array
    {
        $this->damBaoQuanLy($nguoiDung);

        try {
            return DB::transaction(function () use ($phanCongId, $lyDo): array {
                $phanCongBanDau = PhanCongHuanLuyenVien::query()->find($phanCongId);
                if ($phanCongBanDau === null) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $hoiVien = $this->khoaHoiVienHopLe((int) $phanCongBanDau->hoi_vien_id);
                $cacPhanCong = $this->khoaCacPhanCong((int) $hoiVien->getKey());
                $phanCong = $cacPhanCong->firstWhere('id', $phanCongId);
                if (! $phanCong instanceof PhanCongHuanLuyenVien) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                if ($phanCong->ngay_ket_thuc !== null) {
                    return $this->duLieuPhanCong($phanCong);
                }

                $hienTai = CarbonImmutable::now('UTC');
                if ($hienTai->lessThanOrEqualTo($phanCong->ngay_bat_dau)) {
                    throw new PtWorkflowException('Phân công chưa bắt đầu tại thời điểm kết thúc.', 409, 'ASSIGNMENT_NOT_STARTED');
                }
                $phanCong->forceFill([
                    'ngay_ket_thuc' => $hienTai,
                    'ly_do_ket_thuc' => $lyDo,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();

                return $this->duLieuPhanCong($phanCong->refresh());
            }, 3);
        } catch (QueryException $exception) {
            throw new PtWorkflowException('Không thể kết thúc phân công.', 409, 'ASSIGNMENT_CONFLICT');
        }
    }

    /** Đóng assignment cũ đúng ranh giới rồi mở assignment mới trong một transaction. */
    public function phanCongLai(NguoiDung $nguoiDung, int $phanCongId, array $duLieu): array
    {
        $this->damBaoQuanLy($nguoiDung);
        $huanLuyenVienMoiId = (int) $duLieu['trainer_id'];
        $ngayBatDauMoi = $this->thoiDiem($duLieu['start_at'] ?? null) ?? CarbonImmutable::now('UTC');
        $lyDo = isset($duLieu['reason']) ? trim((string) $duLieu['reason']) : null;

        try {
            return DB::transaction(function () use (
                $phanCongId,
                $huanLuyenVienMoiId,
                $ngayBatDauMoi,
                $lyDo,
                $nguoiDung,
            ): array {
                $phanCongBanDau = PhanCongHuanLuyenVien::query()->find($phanCongId);
                if ($phanCongBanDau === null) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $hoiVien = $this->khoaHoiVienHopLe((int) $phanCongBanDau->hoi_vien_id);
                $cacPhanCong = $this->khoaCacPhanCong((int) $hoiVien->getKey());
                $phanCongCu = $cacPhanCong->firstWhere('id', $phanCongId);
                if (! $phanCongCu instanceof PhanCongHuanLuyenVien) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $hienTai = CarbonImmutable::now('UTC');
                if ($phanCongCu->ngay_ket_thuc !== null || $hienTai->lessThan($phanCongCu->ngay_bat_dau)) {
                    throw new PtWorkflowException('Chỉ có thể chuyển một phân công đang hiệu lực.', 409, 'ASSIGNMENT_NOT_ACTIVE');
                }
                if ($ngayBatDauMoi->lessThanOrEqualTo($phanCongCu->ngay_bat_dau)) {
                    throw new PtWorkflowException('Mốc chuyển phải sau mốc bắt đầu phân công cũ.', 422, 'INVALID_ASSIGNMENT_INTERVAL');
                }

                $this->khoaVaXacThucHuanLuyenVien($huanLuyenVienMoiId);
                $this->damBaoKhongChongKhoang(
                    $cacPhanCong->reject(fn (PhanCongHuanLuyenVien $muc): bool => (int) $muc->getKey() === $phanCongId),
                    $ngayBatDauMoi,
                    null,
                );

                $phanCongCu->forceFill([
                    'ngay_ket_thuc' => $ngayBatDauMoi,
                    'ly_do_ket_thuc' => $lyDo,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();
                $phanCongMoi = PhanCongHuanLuyenVien::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'huan_luyen_vien_id' => $huanLuyenVienMoiId,
                    'nguoi_phan_cong_id' => $nguoiDung->getKey(),
                    'ngay_bat_dau' => $ngayBatDauMoi,
                    'ngay_ket_thuc' => null,
                    'ly_do_ket_thuc' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                return $this->duLieuPhanCong($phanCongMoi->refresh());
            }, 3);
        } catch (QueryException $exception) {
            throw new PtWorkflowException('Không thể chuyển phân công vì khoảng thời gian xung đột.', 409, 'ASSIGNMENT_CONFLICT');
        }
    }

    /** Member chỉ đọc assignment của chính mình, gồm current và history an toàn. */
    public function layCuaHoiVien(NguoiDung $nguoiDung): array
    {
        $hoiVien = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
        if ($hoiVien === null) {
            throw new PtWorkflowException('Tài khoản chưa có hồ sơ hội viên.', 404, 'MEMBER_PROFILE_REQUIRED');
        }

        $hienTai = CarbonImmutable::now('UTC');
        $cacPhanCong = $this->cacPhanCongCuaHoiVien((int) $hoiVien->getKey());
        $phanCongHienTai = $cacPhanCong->first(fn (PhanCongHuanLuyenVien $muc): bool => $this->dangHieuLuc($muc, $hienTai));

        return [
            'current' => $phanCongHienTai instanceof PhanCongHuanLuyenVien
                ? $this->duLieuPhanCong($phanCongHienTai)
                : null,
            'history' => $cacPhanCong->map(fn (PhanCongHuanLuyenVien $muc): array => $this->duLieuPhanCong($muc))->values()->all(),
        ];
    }

    /** PT chỉ nhận danh sách Member đang thuộc assignment của chính PT đó. */
    public function layThanhVienCuaHuanLuyenVien(NguoiDung $nguoiDung): array
    {
        $huanLuyenVien = $this->hoSoHuanLuyenVienCuaNguoiDung($nguoiDung);
        $hienTai = CarbonImmutable::now('UTC');
        $cacPhanCong = PhanCongHuanLuyenVien::query()
            ->with(['hoiVien.nguoiDung'])
            ->where('huan_luyen_vien_id', $huanLuyenVien->getKey())
            ->where('ngay_bat_dau', '<=', $hienTai)
            ->where(function ($truyVan) use ($hienTai): void {
                $truyVan->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>', $hienTai);
            })
            ->orderBy('id')
            ->get();

        return $cacPhanCong->map(fn (PhanCongHuanLuyenVien $muc): array => $this->duLieuPhanCong($muc))->values()->all();
    }

    /** @return Collection<int, PhanCongHuanLuyenVien> */
    private function khoaCacPhanCong(int $hoiVienId): Collection
    {
        return PhanCongHuanLuyenVien::query()->where('hoi_vien_id', $hoiVienId)->orderBy('id')->lockForUpdate()->get();
    }

    /** @return Collection<int, PhanCongHuanLuyenVien> */
    private function cacPhanCongCuaHoiVien(int $hoiVienId): Collection
    {
        return PhanCongHuanLuyenVien::query()
            ->with(['huanLuyenVien.nguoiDung'])
            ->where('hoi_vien_id', $hoiVienId)
            ->orderByDesc('ngay_bat_dau')
            ->orderByDesc('id')
            ->get();
    }

    private function khoaHoiVienHopLe(int $hoiVienId): HoSoHoiVien
    {
        $hoiVien = HoSoHoiVien::query()->with('nguoiDung')->lockForUpdate()->find($hoiVienId);
        if (! $hoiVien instanceof HoSoHoiVien) {
            throw new PtWorkflowException('Không tìm thấy hồ sơ hội viên.', 404, 'MEMBER_NOT_FOUND');
        }
        if ($hoiVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Tài khoản hội viên không hoạt động.', 409, 'MEMBER_NOT_ACTIVE');
        }

        return $hoiVien;
    }

    private function khoaVaXacThucHuanLuyenVien(int $huanLuyenVienId): HoSoHuanLuyenVien
    {
        $huanLuyenVien = HoSoHuanLuyenVien::query()->with('nguoiDung')->lockForUpdate()->find($huanLuyenVienId);
        if (! $huanLuyenVien instanceof HoSoHuanLuyenVien) {
            throw new PtWorkflowException('Không tìm thấy hồ sơ huấn luyện viên.', 404, 'TRAINER_NOT_FOUND');
        }
        if ($huanLuyenVien->trang_thai !== 'HOAT_DONG' || $huanLuyenVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Huấn luyện viên không sẵn sàng nhận phân công.', 409, 'TRAINER_NOT_AVAILABLE');
        }
        if (! $this->nguoiDungCoVaiTro($huanLuyenVien->nguoiDung, 'PT')) {
            throw new PtWorkflowException('Tài khoản chưa có role PT hiệu lực.', 403, 'TRAINER_ROLE_REQUIRED');
        }

        return $huanLuyenVien;
    }

    private function hoSoHuanLuyenVienCuaNguoiDung(NguoiDung $nguoiDung): HoSoHuanLuyenVien
    {
        if ($nguoiDung->trang_thai !== 'HOAT_DONG' || ! $this->nguoiDungCoVaiTro($nguoiDung, 'PT')) {
            throw new PtWorkflowException('Chỉ PT đang hoạt động được truy cập phạm vi Member.', 403, 'TRAINER_ROLE_REQUIRED');
        }
        $hoSo = HoSoHuanLuyenVien::query()->with('nguoiDung')->where('nguoi_dung_id', $nguoiDung->getKey())->first();
        if (! $hoSo instanceof HoSoHuanLuyenVien || $hoSo->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Không tìm thấy hồ sơ huấn luyện viên hoạt động.', 403, 'TRAINER_NOT_AVAILABLE');
        }

        return $hoSo;
    }

    private function damBaoQuanLy(NguoiDung $nguoiDung): void
    {
        if ($nguoiDung->trang_thai !== 'HOAT_DONG' || ! $this->nguoiDungCoVaiTro($nguoiDung, self::VAI_TRO_QUAN_LY)) {
            throw new PtWorkflowException('Chỉ Admin được quản lý phân công PT.', 403, 'ASSIGNMENT_MANAGER_REQUIRED');
        }
    }

    private function nguoiDungCoVaiTro(?NguoiDung $nguoiDung, string $maVaiTro): bool
    {
        return $nguoiDung instanceof NguoiDung
            && $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
                ->whereNull('thu_hoi_luc')
                ->whereHas('vaiTro', fn ($truyVan) => $truyVan->where('ma_vai_tro', $maVaiTro))
                ->exists();
    }

    private function damBaoKhoangHopLe(CarbonImmutable $batDau, ?CarbonImmutable $ketThuc): void
    {
        if ($ketThuc !== null && $ketThuc->lessThanOrEqualTo($batDau)) {
            throw new PtWorkflowException('Khoảng phân công phải có end sau start.', 422, 'INVALID_ASSIGNMENT_INTERVAL');
        }
    }

    /** @param Collection<int, PhanCongHuanLuyenVien> $cacPhanCong */
    private function damBaoKhongChongKhoang(Collection $cacPhanCong, CarbonImmutable $batDauMoi, ?CarbonImmutable $ketThucMoi): void
    {
        foreach ($cacPhanCong as $phanCong) {
            $batDauCu = CarbonImmutable::instance($phanCong->ngay_bat_dau);
            $ketThucCu = $phanCong->ngay_ket_thuc === null ? null : CarbonImmutable::instance($phanCong->ngay_ket_thuc);
            $cuBatDauTruocKetThucMoi = $ketThucMoi === null || $batDauCu->lessThan($ketThucMoi);
            $moiBatDauTruocKetThucCu = $ketThucCu === null || $batDauMoi->lessThan($ketThucCu);
            if ($cuBatDauTruocKetThucMoi && $moiBatDauTruocKetThucCu) {
                throw new PtWorkflowException('Khoảng phân công bị chồng lấn.', 409, 'ASSIGNMENT_OVERLAP');
            }
        }
    }

    private function dangHieuLuc(PhanCongHuanLuyenVien $phanCong, CarbonImmutable $thoiDiem): bool
    {
        return CarbonImmutable::instance($phanCong->ngay_bat_dau)->lessThanOrEqualTo($thoiDiem)
            && ($phanCong->ngay_ket_thuc === null || $thoiDiem->lessThan(CarbonImmutable::instance($phanCong->ngay_ket_thuc)));
    }

    private function thoiDiem(mixed $giaTri): ?CarbonImmutable
    {
        if ($giaTri === null || $giaTri === '') {
            return null;
        }
        try {
            return $giaTri instanceof CarbonImmutable
                ? $giaTri->utc()
                : CarbonImmutable::parse((string) $giaTri, 'UTC')->utc();
        } catch (\Throwable) {
            throw new PtWorkflowException('Mốc thời gian không hợp lệ.', 422, 'INVALID_ASSIGNMENT_INTERVAL');
        }
    }

    /** @return array<string, mixed> */
    public function duLieuPhanCong(PhanCongHuanLuyenVien $phanCong): array
    {
        $hoiVien = $phanCong->relationLoaded('hoiVien') ? $phanCong->hoiVien : $phanCong->hoiVien()->with('nguoiDung')->first();
        $huanLuyenVien = $phanCong->relationLoaded('huanLuyenVien') ? $phanCong->huanLuyenVien : $phanCong->huanLuyenVien()->with('nguoiDung')->first();

        return [
            'id' => (int) $phanCong->getKey(),
            'member' => $hoiVien === null ? null : [
                'id' => (int) $hoiVien->getKey(),
                'code' => $hoiVien->ma_hoi_vien,
                'name' => $hoiVien->nguoiDung?->ho_ten,
            ],
            'trainer' => $huanLuyenVien === null ? null : [
                'id' => (int) $huanLuyenVien->getKey(),
                'code' => $huanLuyenVien->ma_huan_luyen_vien,
                'name' => $huanLuyenVien->nguoiDung?->ho_ten,
                'introduction' => $huanLuyenVien->gioi_thieu,
                'specialties' => $huanLuyenVien->chuyen_mon,
            ],
            'start_at' => CarbonImmutable::instance($phanCong->ngay_bat_dau)->toISOString(),
            'end_at' => $phanCong->ngay_ket_thuc === null ? null : CarbonImmutable::instance($phanCong->ngay_ket_thuc)->toISOString(),
            'reason' => $phanCong->ly_do_ket_thuc,
        ];
    }
}
