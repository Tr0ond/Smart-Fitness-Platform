<?php

namespace App\Services\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\PhanCongHuanLuyenVien;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PtAssignmentService
{
    /** Admin là actor duy nhất được PROJECT_RULES cấp quyền quản lý phân công. */
    private const VAI_TRO_QUAN_LY = 'ADMIN';

    /** Admin list chỉ trả assignment có đủ hai resource cùng branch với actor. */
    public function danhSach(NguoiDung $nguoiDung, array $boLoc): array
    {
        $this->damBaoQuanLy($nguoiDung);
        $chiNhanhId = $this->chiNhanhQuanLy($nguoiDung);
        $hienTai = CarbonImmutable::now('UTC');
        $truyVan = PhanCongHuanLuyenVien::query()
            ->with([
                'hoiVien.nguoiDung',
                'huanLuyenVien.nguoiDung',
            ])
            ->whereHas('hoiVien.nguoiDung', fn ($query) => $query->where('chi_nhanh_id', $chiNhanhId))
            ->whereHas('huanLuyenVien.nguoiDung', fn ($query) => $query->where('chi_nhanh_id', $chiNhanhId));

        if (isset($boLoc['member_id'])) {
            $truyVan->where('hoi_vien_id', (int) $boLoc['member_id']);
        }
        if (isset($boLoc['trainer_id'])) {
            $truyVan->where('huan_luyen_vien_id', (int) $boLoc['trainer_id']);
        }
        if (array_key_exists('current', $boLoc)) {
            $truyVan = $this->locTheoHieuLuc($truyVan, (bool) $boLoc['current'], $hienTai);
        }

        $phanTrang = $truyVan
            ->orderByDesc('ngay_bat_dau')
            ->orderByDesc('id')
            ->paginate((int) ($boLoc['per_page'] ?? 20));

        return [
            'items' => collect($phanTrang->items())
                ->map(fn (PhanCongHuanLuyenVien $phanCong): array => $this->duLieuPhanCong($phanCong, $hienTai))
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $phanTrang->currentPage(),
                'per_page' => $phanTrang->perPage(),
                'total' => $phanTrang->total(),
                'last_page' => $phanTrang->lastPage(),
            ],
        ];
    }

    /** Detail dùng cùng scope branch và cùng allow-list DTO với Admin list. */
    public function chiTiet(NguoiDung $nguoiDung, int $phanCongId): array
    {
        $this->damBaoQuanLy($nguoiDung);
        $chiNhanhId = $this->chiNhanhQuanLy($nguoiDung);
        $hienTai = CarbonImmutable::now('UTC');
        $phanCong = PhanCongHuanLuyenVien::query()
            ->with(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung'])
            ->whereHas('hoiVien.nguoiDung', fn ($query) => $query->where('chi_nhanh_id', $chiNhanhId))
            ->whereHas('huanLuyenVien.nguoiDung', fn ($query) => $query->where('chi_nhanh_id', $chiNhanhId))
            ->find($phanCongId);
        if (! $phanCong instanceof PhanCongHuanLuyenVien) {
            throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
        }

        return $this->duLieuPhanCong($phanCong, $hienTai);
    }

    /** Tạo một khoảng phân công, khóa Member trước toàn bộ assignment của Member. */
    public function tao(NguoiDung $nguoiDung, array $duLieu): array
    {
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
                $actorDaKhoa = $this->khoaVaDamBaoQuanLy($nguoiDung);
                $hoiVien = $this->khoaHoiVienHopLe($hoiVienId, (int) $actorDaKhoa->chi_nhanh_id);
                $cacPhanCong = $this->khoaCacPhanCong($hoiVienId);
                $this->khoaVaXacThucHuanLuyenVien($huanLuyenVienId, (int) $actorDaKhoa->chi_nhanh_id);
                $this->damBaoKhongChongKhoang($cacPhanCong, $ngayBatDau, $ngayKetThuc);

                $phanCong = PhanCongHuanLuyenVien::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'huan_luyen_vien_id' => $huanLuyenVienId,
                    'nguoi_phan_cong_id' => $actorDaKhoa->getKey(),
                    'ngay_bat_dau' => $ngayBatDau,
                    'ngay_ket_thuc' => $ngayKetThuc,
                    'ly_do_ket_thuc' => null,
                    'ngay_tao' => CarbonImmutable::now('UTC'),
                    'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
                ]);

                $phanCong->refresh()->load(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung']);
                $duLieuSau = $this->snapshotAudit($phanCong);
                $this->ghiAuditAssignment(
                    $actorDaKhoa,
                    'TAO_PHAN_CONG_HUAN_LUYEN_VIEN',
                    $phanCong,
                    null,
                    $duLieuSau,
                    CarbonImmutable::now('UTC'),
                    (string) Str::uuid(),
                );

                return $this->duLieuPhanCong($phanCong);
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
        try {
            return DB::transaction(function () use ($nguoiDung, $phanCongId, $lyDo): array {
                $actorDaKhoa = $this->khoaVaDamBaoQuanLy($nguoiDung);
                $phanCongBanDau = PhanCongHuanLuyenVien::query()->find($phanCongId);
                if ($phanCongBanDau === null) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $hoiVien = $this->khoaHoiVienHopLe((int) $phanCongBanDau->hoi_vien_id, (int) $actorDaKhoa->chi_nhanh_id);
                $cacPhanCong = $this->khoaCacPhanCong((int) $hoiVien->getKey());
                $phanCong = $cacPhanCong->firstWhere('id', $phanCongId);
                if (! $phanCong instanceof PhanCongHuanLuyenVien) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $this->khoaVaXacThucAssignmentTrainer($phanCong, (int) $actorDaKhoa->chi_nhanh_id);
                if ($phanCong->ngay_ket_thuc !== null) {
                    return $this->duLieuPhanCong($phanCong, CarbonImmutable::now('UTC'));
                }

                $hienTai = CarbonImmutable::now('UTC');
                if ($hienTai->lessThanOrEqualTo($phanCong->ngay_bat_dau)) {
                    throw new PtWorkflowException('Phân công chưa bắt đầu tại thời điểm kết thúc.', 409, 'ASSIGNMENT_NOT_STARTED');
                }
                $duLieuTruoc = $this->snapshotAudit($phanCong, $hienTai);
                $phanCong->forceFill([
                    'ngay_ket_thuc' => $hienTai,
                    'ly_do_ket_thuc' => $lyDo,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();

                $phanCong->refresh()->load(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung']);
                $this->ghiAuditAssignment(
                    $actorDaKhoa,
                    'KET_THUC_PHAN_CONG_HUAN_LUYEN_VIEN',
                    $phanCong,
                    $duLieuTruoc,
                    $this->snapshotAudit($phanCong, $hienTai),
                    $hienTai,
                    (string) Str::uuid(),
                );

                return $this->duLieuPhanCong($phanCong->refresh());
            }, 3);
        } catch (QueryException $exception) {
            throw new PtWorkflowException('Không thể kết thúc phân công.', 409, 'ASSIGNMENT_CONFLICT');
        }
    }

    /** Đóng assignment cũ đúng ranh giới rồi mở assignment mới trong một transaction. */
    public function phanCongLai(NguoiDung $nguoiDung, int $phanCongId, array $duLieu): array
    {
        $huanLuyenVienMoiId = (int) $duLieu['trainer_id'];
        $ngayBatDauMoi = $this->thoiDiem($duLieu['start_at'] ?? null) ?? CarbonImmutable::now('UTC');
        $lyDo = isset($duLieu['reason']) ? trim((string) $duLieu['reason']) : null;

        try {
            return DB::transaction(function () use (
                $nguoiDung,
                $phanCongId,
                $huanLuyenVienMoiId,
                $ngayBatDauMoi,
                $lyDo,
            ): array {
                $actorDaKhoa = $this->khoaVaDamBaoQuanLy($nguoiDung);
                $phanCongBanDau = PhanCongHuanLuyenVien::query()->find($phanCongId);
                if ($phanCongBanDau === null) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $hoiVien = $this->khoaHoiVienHopLe((int) $phanCongBanDau->hoi_vien_id, (int) $actorDaKhoa->chi_nhanh_id);
                $cacPhanCong = $this->khoaCacPhanCong((int) $hoiVien->getKey());
                $phanCongCu = $cacPhanCong->firstWhere('id', $phanCongId);
                if (! $phanCongCu instanceof PhanCongHuanLuyenVien) {
                    throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
                }
                $this->khoaVaXacThucAssignmentTrainer($phanCongCu, (int) $actorDaKhoa->chi_nhanh_id);
                $hienTai = CarbonImmutable::now('UTC');
                if ($phanCongCu->ngay_ket_thuc !== null || $hienTai->lessThan($phanCongCu->ngay_bat_dau)) {
                    throw new PtWorkflowException('Chỉ có thể chuyển một phân công đang hiệu lực.', 409, 'ASSIGNMENT_NOT_ACTIVE');
                }
                if ($ngayBatDauMoi->lessThanOrEqualTo($phanCongCu->ngay_bat_dau)) {
                    throw new PtWorkflowException('Mốc chuyển phải sau mốc bắt đầu phân công cũ.', 422, 'INVALID_ASSIGNMENT_INTERVAL');
                }

                $cacHuanLuyenVien = $this->khoaVaXacThucHuanLuyenViens(
                    [(int) $phanCongCu->huan_luyen_vien_id, $huanLuyenVienMoiId],
                    (int) $actorDaKhoa->chi_nhanh_id,
                );
                $huanLuyenVienMoi = $cacHuanLuyenVien->get($huanLuyenVienMoiId);
                if (
                    ! $huanLuyenVienMoi instanceof HoSoHuanLuyenVien
                    || $huanLuyenVienMoi->trang_thai !== 'HOAT_DONG'
                    || $huanLuyenVienMoi->nguoiDung?->trang_thai !== 'HOAT_DONG'
                ) {
                    throw new PtWorkflowException('Huấn luyện viên không sẵn sàng nhận phân công.', 409, 'TRAINER_NOT_AVAILABLE');
                }
                if (! $this->nguoiDungCoVaiTro($huanLuyenVienMoi->nguoiDung, 'PT')) {
                    throw new PtWorkflowException('Tài khoản chưa có role PT hiệu lực.', 403, 'TRAINER_ROLE_REQUIRED');
                }
                $this->damBaoKhongChongKhoang(
                    $cacPhanCong->reject(fn (PhanCongHuanLuyenVien $muc): bool => (int) $muc->getKey() === $phanCongId),
                    $ngayBatDauMoi,
                    null,
                );

                $duLieuTruocCu = $this->snapshotAudit($phanCongCu, $hienTai);
                $phanCongCu->forceFill([
                    'ngay_ket_thuc' => $ngayBatDauMoi,
                    'ly_do_ket_thuc' => $lyDo,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();
                $phanCongMoi = PhanCongHuanLuyenVien::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'huan_luyen_vien_id' => $huanLuyenVienMoiId,
                    'nguoi_phan_cong_id' => $actorDaKhoa->getKey(),
                    'ngay_bat_dau' => $ngayBatDauMoi,
                    'ngay_ket_thuc' => null,
                    'ly_do_ket_thuc' => null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                $phanCongCu->refresh()->load(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung']);
                $phanCongMoi->refresh()->load(['hoiVien.nguoiDung', 'huanLuyenVien.nguoiDung']);
                $khoaTuongQuan = (string) Str::uuid();
                $this->ghiAuditAssignment(
                    $actorDaKhoa,
                    'KET_THUC_PHAN_CONG_HUAN_LUYEN_VIEN',
                    $phanCongCu,
                    $duLieuTruocCu,
                    $this->snapshotAudit($phanCongCu, $hienTai),
                    $hienTai,
                    $khoaTuongQuan,
                );
                $this->ghiAuditAssignment(
                    $actorDaKhoa,
                    'TAO_PHAN_CONG_HUAN_LUYEN_VIEN',
                    $phanCongMoi,
                    null,
                    $this->snapshotAudit($phanCongMoi, $hienTai),
                    $hienTai,
                    $khoaTuongQuan,
                );

                return $this->duLieuPhanCong($phanCongMoi);
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

    private function khoaHoiVienHopLe(int $hoiVienId, ?int $chiNhanhId = null): HoSoHoiVien
    {
        $hoiVien = HoSoHoiVien::query()->with('nguoiDung')->lockForUpdate()->find($hoiVienId);
        if (! $hoiVien instanceof HoSoHoiVien) {
            throw new PtWorkflowException('Không tìm thấy hồ sơ hội viên.', 404, 'MEMBER_NOT_FOUND');
        }
        if (
            $chiNhanhId === null
            || $hoiVien->nguoiDung?->chi_nhanh_id === null
            || (int) $hoiVien->nguoiDung->chi_nhanh_id !== $chiNhanhId
        ) {
            throw new PtWorkflowException('Không tìm thấy hồ sơ hội viên.', 404, 'MEMBER_NOT_FOUND');
        }
        if ($hoiVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
            throw new PtWorkflowException('Tài khoản hội viên không hoạt động.', 409, 'MEMBER_NOT_ACTIVE');
        }

        return $hoiVien;
    }

    private function khoaVaXacThucHuanLuyenVien(int $huanLuyenVienId, ?int $chiNhanhId = null): HoSoHuanLuyenVien
    {
        $huanLuyenVien = HoSoHuanLuyenVien::query()->with('nguoiDung')->lockForUpdate()->find($huanLuyenVienId);
        if (! $huanLuyenVien instanceof HoSoHuanLuyenVien) {
            throw new PtWorkflowException('Không tìm thấy hồ sơ huấn luyện viên.', 404, 'TRAINER_NOT_FOUND');
        }
        if (
            $chiNhanhId === null
            || $huanLuyenVien->nguoiDung?->chi_nhanh_id === null
            || (int) $huanLuyenVien->nguoiDung->chi_nhanh_id !== $chiNhanhId
        ) {
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

    /** Khóa trainer resources theo ID tăng dần để tránh đổi thứ tự giữa các mutation. */
    private function khoaVaXacThucHuanLuyenViens(array $huanLuyenVienIds, int $chiNhanhId): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $huanLuyenVienIds)));
        sort($ids, SORT_NUMERIC);
        $cacHoSo = HoSoHuanLuyenVien::query()
            ->with('nguoiDung')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (HoSoHuanLuyenVien $hoSo): int => (int) $hoSo->getKey());

        foreach ($ids as $id) {
            $hoSo = $cacHoSo->get($id);
            if (! $hoSo instanceof HoSoHuanLuyenVien) {
                throw new PtWorkflowException('Không tìm thấy hồ sơ huấn luyện viên.', 404, 'TRAINER_NOT_FOUND');
            }
            if (
                $hoSo->nguoiDung?->chi_nhanh_id === null
                || (int) $hoSo->nguoiDung->chi_nhanh_id !== $chiNhanhId
            ) {
                throw new PtWorkflowException('Không tìm thấy hồ sơ huấn luyện viên.', 404, 'TRAINER_NOT_FOUND');
            }
        }

        return $cacHoSo;
    }

    /** Assignment history may point to an inactive trainer; only branch scope is required. */
    private function khoaVaXacThucAssignmentTrainer(PhanCongHuanLuyenVien $phanCong, int $chiNhanhId): void
    {
        $hoSo = HoSoHuanLuyenVien::query()
            ->with('nguoiDung')
            ->lockForUpdate()
            ->find((int) $phanCong->huan_luyen_vien_id);
        if (
            ! $hoSo instanceof HoSoHuanLuyenVien
            || $hoSo->nguoiDung?->chi_nhanh_id === null
            || (int) $hoSo->nguoiDung->chi_nhanh_id !== $chiNhanhId
        ) {
            throw new PtWorkflowException('Không tìm thấy phân công.', 404, 'ASSIGNMENT_NOT_FOUND');
        }
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
        $actor = NguoiDung::query()->find($nguoiDung->getKey());
        if (
            ! $actor instanceof NguoiDung
            || $actor->chi_nhanh_id === null
            || $actor->trang_thai !== 'HOAT_DONG'
            || ! $this->nguoiDungCoVaiTro($actor, self::VAI_TRO_QUAN_LY)
        ) {
            throw new PtWorkflowException('Chỉ Admin được quản lý phân công PT.', 403, 'ASSIGNMENT_MANAGER_REQUIRED');
        }
    }

    /** Khóa actor đầu tiên và revalidate role/branch bên trong mutation transaction. */
    private function khoaVaDamBaoQuanLy(NguoiDung $nguoiDung): NguoiDung
    {
        $actor = NguoiDung::query()->lockForUpdate()->find($nguoiDung->getKey());
        if (
            ! $actor instanceof NguoiDung
            || $actor->chi_nhanh_id === null
            || $actor->trang_thai !== 'HOAT_DONG'
            || ! $this->nguoiDungCoVaiTro($actor, self::VAI_TRO_QUAN_LY)
        ) {
            throw new PtWorkflowException('Chỉ Admin được quản lý phân công PT.', 403, 'ASSIGNMENT_MANAGER_REQUIRED');
        }

        return $actor;
    }

    private function chiNhanhQuanLy(NguoiDung $nguoiDung): int
    {
        $actor = NguoiDung::query()->find($nguoiDung->getKey());
        if (
            ! $actor instanceof NguoiDung
            || $actor->chi_nhanh_id === null
            || $actor->trang_thai !== 'HOAT_DONG'
            || ! $this->nguoiDungCoVaiTro($actor, self::VAI_TRO_QUAN_LY)
        ) {
            throw new PtWorkflowException('Chỉ Admin được quản lý phân công PT.', 403, 'ASSIGNMENT_MANAGER_REQUIRED');
        }

        return (int) $actor->chi_nhanh_id;
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

    /** @param Builder<PhanCongHuanLuyenVien> $truyVan */
    private function locTheoHieuLuc($truyVan, bool $current, CarbonImmutable $hienTai)
    {
        if ($current) {
            return $truyVan
                ->where('ngay_bat_dau', '<=', $hienTai)
                ->where(function ($query) use ($hienTai): void {
                    $query->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>', $hienTai);
                });
        }

        return $truyVan->where(function ($query) use ($hienTai): void {
            $query
                ->where('ngay_bat_dau', '>', $hienTai)
                ->orWhere('ngay_ket_thuc', '<=', $hienTai);
        });
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
    public function duLieuPhanCong(PhanCongHuanLuyenVien $phanCong, ?CarbonImmutable $hienTai = null): array
    {
        $hienTai ??= CarbonImmutable::now('UTC');
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
                'status' => (string) $huanLuyenVien->trang_thai,
            ],
            'start_at' => CarbonImmutable::instance($phanCong->ngay_bat_dau)->toISOString(),
            'end_at' => $phanCong->ngay_ket_thuc === null ? null : CarbonImmutable::instance($phanCong->ngay_ket_thuc)->toISOString(),
            'reason' => $phanCong->ly_do_ket_thuc,
            'is_current' => $this->dangHieuLuc($phanCong, $hienTai),
            'created_at' => CarbonImmutable::instance($phanCong->ngay_tao)->toISOString(),
            'updated_at' => CarbonImmutable::instance($phanCong->ngay_cap_nhat)->toISOString(),
        ];
    }

    /** @return array<string, int|string|bool|null> */
    private function snapshotAudit(PhanCongHuanLuyenVien $phanCong, ?CarbonImmutable $hienTai = null): array
    {
        $hienTai ??= CarbonImmutable::now('UTC');

        return [
            'id' => (int) $phanCong->getKey(),
            'member_id' => (int) $phanCong->hoi_vien_id,
            'trainer_id' => (int) $phanCong->huan_luyen_vien_id,
            'assigned_by_id' => (int) $phanCong->nguoi_phan_cong_id,
            'start_at' => CarbonImmutable::instance($phanCong->ngay_bat_dau)->toISOString(),
            'end_at' => $phanCong->ngay_ket_thuc === null ? null : CarbonImmutable::instance($phanCong->ngay_ket_thuc)->toISOString(),
            'reason' => $phanCong->ly_do_ket_thuc,
            'is_current' => $this->dangHieuLuc($phanCong, $hienTai),
        ];
    }

    /** Ghi audit assignment append-only trong cùng transaction với mutation. */
    private function ghiAuditAssignment(
        NguoiDung $actor,
        string $hanhDong,
        PhanCongHuanLuyenVien $phanCong,
        ?array $duLieuTruoc,
        array $duLieuSau,
        CarbonImmutable $hienTai,
        string $khoaTuongQuan,
    ): void {
        NhatKyHeThong::query()->create([
            'nguoi_thuc_hien_id' => $actor->getKey(),
            'loai_tac_nhan' => 'NGUOI_DUNG',
            'hanh_dong' => $hanhDong,
            'loai_doi_tuong' => 'PHAN_CONG_HUAN_LUYEN_VIEN',
            'dinh_danh_doi_tuong' => $phanCong->getKey(),
            'khoa_tuong_quan' => $khoaTuongQuan,
            'du_lieu_truoc' => $duLieuTruoc,
            'du_lieu_sau' => $duLieuSau,
            'ket_qua' => 'THANH_CONG',
            'thuc_hien_luc' => $hienTai,
            'ngay_tao' => $hienTai,
        ]);
    }
}
