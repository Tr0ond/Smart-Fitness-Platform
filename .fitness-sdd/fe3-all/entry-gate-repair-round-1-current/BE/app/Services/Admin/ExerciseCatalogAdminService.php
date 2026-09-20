<?php

namespace App\Services\Admin;

use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Models\BaiTap;
use App\Models\BaiTapDungCu;
use App\Models\BaiTapNhomCo;
use App\Models\DungCu;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use App\Models\NhomCo;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                    'trang_thai' => $this->chuanHoaTrangThai($duLieu['status'] ?? 'HOAT_DONG'),
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
            $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
            $nhomCo = NhomCo::query()->lockForUpdate()->find($nhomCoId);
            if (! $nhomCo instanceof NhomCo) {
                throw new CatalogWorkflowException('Không tìm thấy nhóm cơ.', 404, 'MUSCLE_GROUP_NOT_FOUND');
            }
            $trangThaiCu = (string) $nhomCo->trang_thai;
            $values = [];
            foreach (['name' => 'ten_nhom_co', 'description' => 'mo_ta', 'status' => 'trang_thai'] as $api => $cot) {
                if (array_key_exists($api, $duLieu)) {
                    $values[$cot] = $api === 'status'
                        ? $this->chuanHoaTrangThai($duLieu[$api])
                        : (is_string($duLieu[$api]) ? trim($duLieu[$api]) : $duLieu[$api]);
                }
            }
            if ($values !== []) {
                $nhomCo->forceFill($values);
                if ($nhomCo->isDirty()) {
                    $hienTai = CarbonImmutable::now('UTC');
                    $nhomCo->forceFill(['ngay_cap_nhat' => $hienTai])->save();
                    if (array_key_exists('trang_thai', $values) && $trangThaiCu !== (string) $nhomCo->trang_thai) {
                        NhatKyHeThong::query()->create([
                            'nguoi_thuc_hien_id' => $actorDaKhoa->getKey(),
                            'loai_tac_nhan' => 'NGUOI_DUNG',
                            'hanh_dong' => 'CAP_NHAT_TRANG_THAI_NHOM_CO',
                            'loai_doi_tuong' => 'NHOM_CO',
                            'dinh_danh_doi_tuong' => $nhomCo->getKey(),
                            'khoa_tuong_quan' => (string) Str::uuid(),
                            'du_lieu_truoc' => ['trang_thai' => $trangThaiCu],
                            'du_lieu_sau' => ['trang_thai' => (string) $nhomCo->trang_thai],
                            'ket_qua' => 'THANH_CONG',
                            'thuc_hien_luc' => $hienTai,
                            'ngay_tao' => $hienTai,
                        ]);
                    }
                }
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
                $lienKet = $this->khoaVaXacThucLienKet($duLieu['equipment_ids'], $duLieu['muscle_groups']);
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
                $this->dongBoDungCu($baiTap, $lienKet['equipment_ids'], $hienTai);
                $this->dongBoNhomCo($baiTap, $lienKet['muscle_groups'], $hienTai);

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

            $equipmentSubmitted = array_key_exists('equipment_ids', $duLieu);
            $musclesSubmitted = array_key_exists('muscle_groups', $duLieu);
            $equipment = $equipmentSubmitted ? $this->chuanHoaIdList($duLieu['equipment_ids']) : null;
            $muscles = $musclesSubmitted ? $this->chuanHoaNhomCoPayload($duLieu['muscle_groups']) : null;
            $currentMuscleRows = null;
            if ($equipmentSubmitted) {
                $this->khoaVaXacThucDungCu($equipment);
            }
            if ($musclesSubmitted) {
                $currentMuscleRows = BaiTapNhomCo::query()
                    ->where('bai_tap_id', $baiTapId)
                    ->orderBy('nhom_co_id')
                    ->orderBy('id')
                    ->get();
                $currentMuscleIds = $currentMuscleRows->pluck('nhom_co_id')->map(fn ($id): int => (int) $id)->all();
                $submittedMuscleIds = array_map(fn (array $row): int => $row['id'], $muscles);
                $lockedMuscleGroups = $this->khoaVaXacThucNhomCo(array_merge($currentMuscleIds, $submittedMuscleIds));
                $this->xacThucThayTheNhomCo($currentMuscleRows, $muscles, $lockedMuscleGroups);
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
            if ($equipmentSubmitted) {
                $this->dongBoDungCu($baiTap, $equipment, $hienTai);
            }
            if ($musclesSubmitted) {
                $this->dongBoNhomCo($baiTap, $muscles, $hienTai, $currentMuscleRows);
            }

            return $this->duLieuBaiTap($baiTap->refresh()->load(['baiTapDungCus.dungCu', 'baiTapNhomCos.nhomCo']));
        }, 3);
    }

    /** @return array{equipment_ids: array<int, int>, muscle_groups: array<int, array{id:int,role:string}>} */
    private function khoaVaXacThucLienKet(array $equipmentIds, array $muscleGroups): array
    {
        $equipmentIds = $this->chuanHoaIdList($equipmentIds);
        $muscleGroups = $this->chuanHoaNhomCoPayload($muscleGroups);
        $this->khoaVaXacThucDungCu($equipmentIds);
        $lockedMuscleGroups = $this->khoaVaXacThucNhomCo(array_map(
            fn (array $row): int => $row['id'],
            $muscleGroups,
        ));
        foreach ($muscleGroups as $row) {
            $nhomCo = $lockedMuscleGroups->get($row['id']);
            if (! $nhomCo instanceof NhomCo || (string) $nhomCo->trang_thai !== 'HOAT_DONG') {
                throw new CatalogWorkflowException(
                    'Nhóm cơ không hoạt động không thể được liên kết mới.',
                    422,
                    'INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE',
                );
            }
        }

        return ['equipment_ids' => $equipmentIds, 'muscle_groups' => $muscleGroups];
    }

    /** @param array<int, int> $ids */
    private function khoaVaXacThucDungCu(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $found = DungCu::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
        if (count($found) !== count($ids)) {
            throw new CatalogWorkflowException('Danh sách dụng cụ chứa ID không tồn tại.', 422, 'INVALID_EQUIPMENT');
        }
    }

    /** @param array<int, int> $ids @return Collection<int, NhomCo> */
    private function khoaVaXacThucNhomCo(array $ids): Collection
    {
        $ids = $this->chuanHoaIdList($ids);
        if ($ids === []) {
            return collect();
        }

        $groups = NhomCo::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy(
            fn (NhomCo $nhomCo): int => (int) $nhomCo->getKey(),
        );
        if ($groups->count() !== count($ids)) {
            throw new CatalogWorkflowException('Danh sách nhóm cơ chứa ID không tồn tại.', 422, 'INVALID_MUSCLE_GROUP');
        }

        return $groups;
    }

    /** @param array<int, int> $ids @return array<int, int> */
    private function chuanHoaIdList(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            $normalized[(int) $id] = true;
        }
        $result = array_map('intval', array_keys($normalized));
        sort($result, SORT_NUMERIC);

        return $result;
    }

    /** @param array<int, mixed> $muscleGroups @return array<int, array{id:int,role:string}> */
    private function chuanHoaNhomCoPayload(array $muscleGroups): array
    {
        $result = [];
        foreach ($muscleGroups as $row) {
            if (! is_array($row) || ! array_key_exists('id', $row) || ! array_key_exists('role', $row)) {
                throw new CatalogWorkflowException('Danh sách nhóm cơ không hợp lệ.', 422, 'INVALID_MUSCLE_GROUP');
            }
            $id = (int) $row['id'];
            $role = (string) $row['role'];
            if ($id < 1 || ! in_array($role, ['CHINH', 'PHU'], true) || array_key_exists($id, $result)) {
                throw new CatalogWorkflowException('Danh sách nhóm cơ không hợp lệ.', 422, 'INVALID_MUSCLE_GROUP');
            }
            $result[$id] = ['id' => $id, 'role' => $role];
        }
        ksort($result, SORT_NUMERIC);

        return array_values($result);
    }

    /**
     * @param  Collection<int, BaiTapNhomCo>  $currentRows
     * @param  array<int, array{id:int,role:string}>  $submittedRows
     * @param  Collection<int, NhomCo>  $lockedGroups
     */
    private function xacThucThayTheNhomCo(Collection $currentRows, array $submittedRows, Collection $lockedGroups): void
    {
        $currentById = $currentRows->keyBy(fn (BaiTapNhomCo $row): int => (int) $row->nhom_co_id);
        $submittedById = collect($submittedRows)->keyBy('id');
        foreach ($submittedRows as $submitted) {
            $nhomCo = $lockedGroups->get($submitted['id']);
            if (! $nhomCo instanceof NhomCo) {
                throw new CatalogWorkflowException('Danh sách nhóm cơ chứa ID không tồn tại.', 422, 'INVALID_MUSCLE_GROUP');
            }
            if ((string) $nhomCo->trang_thai !== 'HOAT_DONG') {
                $current = $currentById->get($submitted['id']);
                if (! $current instanceof BaiTapNhomCo || (string) $current->vai_tro_nhom_co !== $submitted['role']) {
                    throw $this->loiLienKetNhomCoKhongHoatDong();
                }
            }
        }
        foreach ($currentRows as $current) {
            $nhomCo = $lockedGroups->get((int) $current->nhom_co_id);
            if ($nhomCo instanceof NhomCo && (string) $nhomCo->trang_thai !== 'HOAT_DONG') {
                $submitted = $submittedById->get((int) $current->nhom_co_id);
                if (! is_array($submitted) || (string) $current->vai_tro_nhom_co !== (string) ($submitted['role'] ?? '')) {
                    throw $this->loiLienKetNhomCoKhongHoatDong();
                }
            }
        }
    }

    /** @param array<int, int> $equipmentIds */
    private function dongBoDungCu(BaiTap $baiTap, array $equipmentIds, CarbonImmutable $hienTai): void
    {
        $currentIds = BaiTapDungCu::query()->where('bai_tap_id', $baiTap->getKey())->pluck('dung_cu_id')
            ->map(fn ($id): int => (int) $id)->all();
        $current = array_fill_keys($currentIds, true);
        $remove = array_values(array_diff($currentIds, $equipmentIds));
        if ($remove !== []) {
            BaiTapDungCu::query()->where('bai_tap_id', $baiTap->getKey())->whereIn('dung_cu_id', $remove)->delete();
        }
        foreach ($equipmentIds as $dungCuId) {
            if (! isset($current[$dungCuId])) {
                BaiTapDungCu::query()->create([
                    'bai_tap_id' => $baiTap->getKey(),
                    'dung_cu_id' => $dungCuId,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
            }
        }
    }

    /** @param array<int, array{id:int,role:string}> $muscleGroups @param Collection<int, BaiTapNhomCo>|null $currentRows */
    private function dongBoNhomCo(
        BaiTap $baiTap,
        array $muscleGroups,
        CarbonImmutable $hienTai,
        ?Collection $currentRows = null,
    ): void {
        $currentRows ??= BaiTapNhomCo::query()->where('bai_tap_id', $baiTap->getKey())
            ->orderBy('nhom_co_id')->orderBy('id')->get();
        $currentById = $currentRows->keyBy(fn (BaiTapNhomCo $row): int => (int) $row->nhom_co_id);
        $targetById = collect($muscleGroups)->keyBy('id');
        $remove = $currentRows->filter(fn (BaiTapNhomCo $row): bool => ! $targetById->has((int) $row->nhom_co_id))
            ->pluck('nhom_co_id')->map(fn ($id): int => (int) $id)->all();
        if ($remove !== []) {
            BaiTapNhomCo::query()->where('bai_tap_id', $baiTap->getKey())->whereIn('nhom_co_id', $remove)->delete();
        }
        foreach ($muscleGroups as $target) {
            $current = $currentById->get($target['id']);
            if ($current instanceof BaiTapNhomCo) {
                if ((string) $current->vai_tro_nhom_co !== $target['role']) {
                    $current->forceFill([
                        'vai_tro_nhom_co' => $target['role'],
                        'ngay_cap_nhat' => $hienTai,
                    ])->save();
                }

                continue;
            }
            BaiTapNhomCo::query()->create([
                'bai_tap_id' => $baiTap->getKey(),
                'nhom_co_id' => $target['id'],
                'vai_tro_nhom_co' => $target['role'],
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
        }
    }

    private function loiLienKetNhomCoKhongHoatDong(): CatalogWorkflowException
    {
        return new CatalogWorkflowException(
            'Quan hệ với nhóm cơ ngừng sử dụng phải được giữ nguyên.',
            422,
            'INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE',
        );
    }

    private function chuanHoaTrangThai(mixed $status): string
    {
        return strtoupper(trim((string) $status));
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
            'status' => (string) $nhomCo->trang_thai,
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
                'status' => (string) $row->nhomCo?->trang_thai,
            ])->values()->all(),
        ];
    }
}
