<?php

namespace App\Services\Admin;

use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Models\BaiTap;
use App\Models\BaiTapDungCu;
use App\Models\BaiTapNhomCo;
use App\Models\DungCu;
use App\Models\NguoiDung;
use App\Models\NhomCo;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ExerciseCatalogAdminService
{
    public function __construct(private readonly AdminActorGuard $guard) {}

    /** @return array<int, array<string, mixed>> */
    public function danhSachDungCu(NguoiDung $actor): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);

        return DungCu::query()->orderBy('ma_dung_cu')->get()
            ->map(fn (DungCu $dungCu): array => $this->duLieuDungCu($dungCu))->all();
    }

    /** @return array<string, mixed> */
    public function taoDungCu(NguoiDung $actor, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $duLieu): array {
                $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $hienTai = CarbonImmutable::now('UTC');
                $dungCu = DungCu::query()->create([
                    'ma_dung_cu' => strtoupper(trim((string) $duLieu['code'])),
                    'ten_dung_cu' => trim((string) $duLieu['name']),
                    'mo_ta' => isset($duLieu['description']) ? trim((string) $duLieu['description']) : null,
                    'trang_thai' => (string) ($duLieu['status'] ?? 'HOAT_DONG'),
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                return $this->duLieuDungCu($dungCu);
            }, 3);
        } catch (QueryException $exception) {
            throw $this->xungDot($exception, 'Mã dụng cụ đã tồn tại.', 'EQUIPMENT_CONFLICT');
        }
    }

    /** @return array<string, mixed> */
    public function capNhatDungCu(NguoiDung $actor, int $dungCuId, array $duLieu): array
    {
        return DB::transaction(function () use ($actor, $dungCuId, $duLieu): array {
            $this->guard->khoaVaDamBaoQuanTriVien($actor);
            $dungCu = DungCu::query()->lockForUpdate()->find($dungCuId);
            if (! $dungCu instanceof DungCu) {
                throw new CatalogWorkflowException('Không tìm thấy dụng cụ.', 404, 'EQUIPMENT_NOT_FOUND');
            }
            $values = [];
            foreach (['name' => 'ten_dung_cu', 'description' => 'mo_ta', 'status' => 'trang_thai'] as $api => $cot) {
                if (array_key_exists($api, $duLieu)) {
                    $values[$cot] = is_string($duLieu[$api]) ? trim($duLieu[$api]) : $duLieu[$api];
                }
            }
            if ($values !== []) {
                $values['ngay_cap_nhat'] = CarbonImmutable::now('UTC');
                $dungCu->forceFill($values)->save();
            }

            return $this->duLieuDungCu($dungCu->refresh());
        }, 3);
    }

    /** @return array<int, array<string, mixed>> */
    public function danhSachNhomCo(NguoiDung $actor): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);

        return NhomCo::query()->orderBy('ma_nhom_co')->get()
            ->map(fn (NhomCo $nhomCo): array => $this->duLieuNhomCo($nhomCo))->all();
    }

    /** @return array<string, mixed> */
    public function taoNhomCo(NguoiDung $actor, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $duLieu): array {
                $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $hienTai = CarbonImmutable::now('UTC');
                $nhomCo = NhomCo::query()->create([
                    'ma_nhom_co' => strtoupper(trim((string) $duLieu['code'])),
                    'ten_nhom_co' => trim((string) $duLieu['name']),
                    'mo_ta' => isset($duLieu['description']) ? trim((string) $duLieu['description']) : null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);

                return $this->duLieuNhomCo($nhomCo);
            }, 3);
        } catch (QueryException $exception) {
            throw $this->xungDot($exception, 'Mã nhóm cơ đã tồn tại.', 'MUSCLE_GROUP_CONFLICT');
        }
    }

    /** @return array<string, mixed> */
    public function capNhatNhomCo(NguoiDung $actor, int $nhomCoId, array $duLieu): array
    {
        return DB::transaction(function () use ($actor, $nhomCoId, $duLieu): array {
            $this->guard->khoaVaDamBaoQuanTriVien($actor);
            $nhomCo = NhomCo::query()->lockForUpdate()->find($nhomCoId);
            if (! $nhomCo instanceof NhomCo) {
                throw new CatalogWorkflowException('Không tìm thấy nhóm cơ.', 404, 'MUSCLE_GROUP_NOT_FOUND');
            }
            $values = [];
            foreach (['name' => 'ten_nhom_co', 'description' => 'mo_ta'] as $api => $cot) {
                if (array_key_exists($api, $duLieu)) {
                    $values[$cot] = is_string($duLieu[$api]) ? trim($duLieu[$api]) : $duLieu[$api];
                }
            }
            if ($values !== []) {
                $values['ngay_cap_nhat'] = CarbonImmutable::now('UTC');
                $nhomCo->forceFill($values)->save();
            }

            return $this->duLieuNhomCo($nhomCo->refresh());
        }, 3);
    }

    /** @return array<int, array<string, mixed>> */
    public function danhSachBaiTap(NguoiDung $actor, array $boLoc): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $query = BaiTap::query()->with(['baiTapDungCus.dungCu', 'baiTapNhomCos.nhomCo']);
        if (isset($boLoc['status'])) {
            $query->where('trang_thai', $boLoc['status']);
        }
        if (isset($boLoc['search']) && trim((string) $boLoc['search']) !== '') {
            $mau = '%'.addcslashes(trim((string) $boLoc['search']), '\\%_').'%';
            $query->where(fn ($q) => $q->where('ma_bai_tap', 'like', $mau)->orWhere('ten_bai_tap', 'like', $mau));
        }

        return $query->orderBy('ma_bai_tap')->orderBy('id')->limit(500)->get()
            ->map(fn (BaiTap $baiTap): array => $this->duLieuBaiTap($baiTap))->all();
    }

    /** @return array<string, mixed> */
    public function chiTietBaiTap(NguoiDung $actor, int $baiTapId): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $baiTap = BaiTap::query()->with(['baiTapDungCus.dungCu', 'baiTapNhomCos.nhomCo'])->find($baiTapId);
        if (! $baiTap instanceof BaiTap) {
            throw new CatalogWorkflowException('Không tìm thấy bài tập.', 404, 'EXERCISE_NOT_FOUND');
        }

        return $this->duLieuBaiTap($baiTap);
    }

    /** @return array<string, mixed> */
    public function taoBaiTap(NguoiDung $actor, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $duLieu): array {
                $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $hienTai = CarbonImmutable::now('UTC');
                $this->khoaVaXacThucLienKet($duLieu['equipment_ids'], $duLieu['muscle_groups']);
                $baiTap = BaiTap::query()->create([
                    'ma_bai_tap' => strtoupper(trim((string) $duLieu['code'])),
                    'ten_bai_tap' => trim((string) $duLieu['name']),
                    'do_kho' => trim((string) $duLieu['difficulty']),
                    'huong_dan' => trim((string) $duLieu['instructions']),
                    'duong_dan_hinh_anh' => $duLieu['image_path'] ?? null,
                    'duong_dan_video' => $duLieu['video_path'] ?? null,
                    'thong_tin_bo_sung' => $duLieu['metadata'] ?? null,
                    'phien_ban_noi_dung' => 1,
                    'trang_thai' => (string) ($duLieu['status'] ?? 'NGUNG_SU_DUNG'),
                    'nguoi_tao_id' => $actorDaKhoa->getKey(),
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                $this->thayTheLienKet($baiTap, $duLieu['equipment_ids'], $duLieu['muscle_groups'], $hienTai);

                return $this->duLieuBaiTap($baiTap->load(['baiTapDungCus.dungCu', 'baiTapNhomCos.nhomCo']));
            }, 3);
        } catch (QueryException $exception) {
            throw $this->xungDot($exception, 'Mã bài tập hoặc liên kết đã tồn tại.', 'EXERCISE_CONFLICT');
        }
    }

    /** @return array<string, mixed> */
    public function capNhatBaiTap(NguoiDung $actor, int $baiTapId, array $duLieu): array
    {
        return DB::transaction(function () use ($actor, $baiTapId, $duLieu): array {
            $this->guard->khoaVaDamBaoQuanTriVien($actor);
            $baiTap = BaiTap::query()->lockForUpdate()->find($baiTapId);
            if (! $baiTap instanceof BaiTap) {
                throw new CatalogWorkflowException('Không tìm thấy bài tập.', 404, 'EXERCISE_NOT_FOUND');
            }

            $equipment = $duLieu['equipment_ids'] ?? null;
            $muscles = $duLieu['muscle_groups'] ?? null;
            if ($equipment !== null || $muscles !== null) {
                $this->khoaVaXacThucLienKet(
                    $equipment ?? BaiTapDungCu::query()->where('bai_tap_id', $baiTapId)->pluck('dung_cu_id')->all(),
                    $muscles ?? BaiTapNhomCo::query()->where('bai_tap_id', $baiTapId)
                        ->get()->map(fn (BaiTapNhomCo $row): array => ['id' => $row->nhom_co_id, 'role' => $row->vai_tro_nhom_co])->all(),
                );
            }

            $values = [];
            foreach ([
                'name' => 'ten_bai_tap',
                'difficulty' => 'do_kho',
                'instructions' => 'huong_dan',
                'image_path' => 'duong_dan_hinh_anh',
                'video_path' => 'duong_dan_video',
                'metadata' => 'thong_tin_bo_sung',
                'status' => 'trang_thai',
            ] as $api => $cot) {
                if (array_key_exists($api, $duLieu)) {
                    $values[$cot] = is_string($duLieu[$api]) ? trim($duLieu[$api]) : $duLieu[$api];
                }
            }
            $noiDung = array_intersect_key($duLieu, array_flip([
                'name', 'difficulty', 'instructions', 'image_path', 'video_path', 'metadata', 'equipment_ids', 'muscle_groups',
            ])) !== [];
            $hienTai = CarbonImmutable::now('UTC');
            if ($noiDung) {
                $values['phien_ban_noi_dung'] = (int) $baiTap->phien_ban_noi_dung + 1;
            }
            if ($values !== []) {
                $values['ngay_cap_nhat'] = $hienTai;
                $baiTap->forceFill($values)->save();
            }
            if ($equipment !== null || $muscles !== null) {
                $this->thayTheLienKet(
                    $baiTap,
                    $equipment ?? BaiTapDungCu::query()->where('bai_tap_id', $baiTapId)->pluck('dung_cu_id')->all(),
                    $muscles ?? BaiTapNhomCo::query()->where('bai_tap_id', $baiTapId)
                        ->get()->map(fn (BaiTapNhomCo $row): array => ['id' => $row->nhom_co_id, 'role' => $row->vai_tro_nhom_co])->all(),
                    $hienTai,
                );
            }

            return $this->duLieuBaiTap($baiTap->refresh()->load(['baiTapDungCus.dungCu', 'baiTapNhomCos.nhomCo']));
        }, 3);
    }

    private function khoaVaXacThucLienKet(array $equipmentIds, array $muscleGroups): void
    {
        $equipmentIds = array_values(array_unique(array_map('intval', $equipmentIds)));
        $muscleIds = array_values(array_unique(array_map(fn (array $row): int => (int) $row['id'], $muscleGroups)));
        if ($equipmentIds !== [] && DungCu::query()->whereIn('id', $equipmentIds)->lockForUpdate()->count() !== count($equipmentIds)) {
            throw new CatalogWorkflowException('Danh sách dụng cụ chứa ID không tồn tại.', 422, 'INVALID_EQUIPMENT');
        }
        if ($muscleIds !== [] && NhomCo::query()->whereIn('id', $muscleIds)->lockForUpdate()->count() !== count($muscleIds)) {
            throw new CatalogWorkflowException('Danh sách nhóm cơ chứa ID không tồn tại.', 422, 'INVALID_MUSCLE_GROUP');
        }
    }

    private function thayTheLienKet(BaiTap $baiTap, array $equipmentIds, array $muscleGroups, CarbonImmutable $hienTai): void
    {
        BaiTapDungCu::query()->where('bai_tap_id', $baiTap->getKey())->delete();
        foreach (array_values(array_unique(array_map('intval', $equipmentIds))) as $dungCuId) {
            BaiTapDungCu::query()->create([
                'bai_tap_id' => $baiTap->getKey(),
                'dung_cu_id' => $dungCuId,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
        }
        BaiTapNhomCo::query()->where('bai_tap_id', $baiTap->getKey())->delete();
        foreach ($muscleGroups as $row) {
            BaiTapNhomCo::query()->create([
                'bai_tap_id' => $baiTap->getKey(),
                'nhom_co_id' => (int) $row['id'],
                'vai_tro_nhom_co' => (string) $row['role'],
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
        }
    }

    private function xungDot(QueryException $exception, string $message, string $code): CatalogWorkflowException
    {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            return new CatalogWorkflowException($message, 409, $code);
        }

        throw $exception;
    }

    /** @return array<string, mixed> */
    private function duLieuDungCu(DungCu $dungCu): array
    {
        return [
            'id' => (int) $dungCu->getKey(),
            'code' => (string) $dungCu->ma_dung_cu,
            'name' => (string) $dungCu->ten_dung_cu,
            'description' => $dungCu->mo_ta,
            'status' => (string) $dungCu->trang_thai,
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuNhomCo(NhomCo $nhomCo): array
    {
        return [
            'id' => (int) $nhomCo->getKey(),
            'code' => (string) $nhomCo->ma_nhom_co,
            'name' => (string) $nhomCo->ten_nhom_co,
            'description' => $nhomCo->mo_ta,
        ];
    }

    /** @return array<string, mixed> */
    private function duLieuBaiTap(BaiTap $baiTap): array
    {
        return [
            'id' => (int) $baiTap->getKey(),
            'code' => (string) $baiTap->ma_bai_tap,
            'name' => (string) $baiTap->ten_bai_tap,
            'difficulty' => (string) $baiTap->do_kho,
            'instructions' => (string) $baiTap->huong_dan,
            'image_path' => $baiTap->duong_dan_hinh_anh,
            'video_path' => $baiTap->duong_dan_video,
            'metadata' => $baiTap->thong_tin_bo_sung,
            'content_version' => (int) $baiTap->phien_ban_noi_dung,
            'status' => (string) $baiTap->trang_thai,
            'created_by_id' => (int) $baiTap->nguoi_tao_id,
            'equipment_semantics' => 'AND',
            'equipment' => $baiTap->baiTapDungCus->sortBy('dung_cu_id')->map(fn (BaiTapDungCu $row): array => [
                'id' => (int) $row->dung_cu_id,
                'code' => (string) $row->dungCu?->ma_dung_cu,
                'name' => (string) $row->dungCu?->ten_dung_cu,
            ])->values()->all(),
            'muscle_groups' => $baiTap->baiTapNhomCos->sortBy('nhom_co_id')->map(fn (BaiTapNhomCo $row): array => [
                'id' => (int) $row->nhom_co_id,
                'code' => (string) $row->nhomCo?->ma_nhom_co,
                'name' => (string) $row->nhomCo?->ten_nhom_co,
                'role' => (string) $row->vai_tro_nhom_co,
            ])->values()->all(),
        ];
    }
}
