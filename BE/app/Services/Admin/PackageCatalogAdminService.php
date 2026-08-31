<?php

namespace App\Services\Admin;

use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Models\GoiTap;
use App\Models\NguoiDung;
use App\Models\QuyenLoiGoiTap;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PackageCatalogAdminService
{
    public function __construct(private readonly AdminActorGuard $guard) {}

    /** @return array<int, array<string, mixed>> */
    public function danhSach(NguoiDung $actor): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);

        return GoiTap::query()
            ->with('quyenLoiGoiTap')
            ->where('chi_nhanh_id', $actor->chi_nhanh_id)
            ->orderBy('ma_goi')
            ->orderBy('id')
            ->get()
            ->map(fn (GoiTap $goiTap): array => $this->duLieu($goiTap))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function chiTiet(NguoiDung $actor, int $goiTapId): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $goiTap = GoiTap::query()
            ->with('quyenLoiGoiTap')
            ->where('chi_nhanh_id', $actor->chi_nhanh_id)
            ->find($goiTapId);
        if (! $goiTap instanceof GoiTap) {
            throw new CatalogWorkflowException('Không tìm thấy gói tập.', 404, 'PACKAGE_NOT_FOUND');
        }

        return $this->duLieu($goiTap);
    }

    /** @return array<string, mixed> */
    public function tao(NguoiDung $actor, array $duLieu): array
    {
        $this->damBaoQuyenLoiHopLe($duLieu['benefits']);

        try {
            return DB::transaction(function () use ($actor, $duLieu): array {
                $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $hienTai = CarbonImmutable::now('UTC');
                $goiTap = GoiTap::query()->create([
                    'chi_nhanh_id' => $actorDaKhoa->chi_nhanh_id,
                    'ma_goi' => strtoupper(trim((string) $duLieu['code'])),
                    'ten_goi' => trim((string) $duLieu['name']),
                    'gia' => (int) $duLieu['price'],
                    'thoi_han_ngay' => (int) $duLieu['duration_days'],
                    'mo_ta' => isset($duLieu['description']) ? trim((string) $duLieu['description']) : null,
                    'trang_thai' => (string) ($duLieu['status'] ?? 'NGUNG_BAN'),
                    'phien_ban_cau_hinh' => 1,
                    'nguoi_tao_id' => $actorDaKhoa->getKey(),
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                $this->taoQuyenLoi($goiTap, $duLieu['benefits'], $hienTai);

                return $this->duLieu($goiTap->load('quyenLoiGoiTap'));
            }, 3);
        } catch (QueryException $exception) {
            throw $this->loiTruyVan($exception, 'Mã gói đã tồn tại trong chi nhánh.');
        }
    }

    /** @return array<string, mixed> */
    public function capNhat(NguoiDung $actor, int $goiTapId, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $goiTapId, $duLieu): array {
                $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $goiTap = GoiTap::query()
                    ->where('chi_nhanh_id', $actorDaKhoa->chi_nhanh_id)
                    ->lockForUpdate()
                    ->find($goiTapId);
                if (! $goiTap instanceof GoiTap) {
                    throw new CatalogWorkflowException('Không tìm thấy gói tập.', 404, 'PACKAGE_NOT_FOUND');
                }

                $hienTai = CarbonImmutable::now('UTC');
                $thayDoiCauHinh = array_intersect_key($duLieu, array_flip(['price', 'duration_days'])) !== [];
                $thayDoi = [];
                foreach ([
                    'name' => 'ten_goi',
                    'price' => 'gia',
                    'duration_days' => 'thoi_han_ngay',
                    'description' => 'mo_ta',
                    'status' => 'trang_thai',
                ] as $api => $cot) {
                    if (array_key_exists($api, $duLieu)) {
                        $thayDoi[$cot] = in_array($api, ['price', 'duration_days'], true)
                            ? (int) $duLieu[$api]
                            : (is_string($duLieu[$api]) ? trim($duLieu[$api]) : $duLieu[$api]);
                    }
                }
                if ($thayDoiCauHinh) {
                    $thayDoi['phien_ban_cau_hinh'] = (int) $goiTap->phien_ban_cau_hinh + 1;
                }
                if ($thayDoi !== []) {
                    $thayDoi['ngay_cap_nhat'] = $hienTai;
                    $goiTap->forceFill($thayDoi)->save();
                }

                return $this->duLieu($goiTap->refresh()->load('quyenLoiGoiTap'));
            }, 3);
        } catch (QueryException $exception) {
            throw $this->loiTruyVan($exception, 'Không thể cập nhật gói tập.');
        }
    }

    /** @return array<string, mixed> */
    public function capNhatQuyenLoi(NguoiDung $actor, int $goiTapId, array $duLieu): array
    {
        $this->damBaoQuyenLoiHopLe($duLieu);

        return DB::transaction(function () use ($actor, $goiTapId, $duLieu): array {
            $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
            $goiTap = GoiTap::query()
                ->where('chi_nhanh_id', $actorDaKhoa->chi_nhanh_id)
                ->lockForUpdate()
                ->find($goiTapId);
            if (! $goiTap instanceof GoiTap) {
                throw new CatalogWorkflowException('Không tìm thấy gói tập.', 404, 'PACKAGE_NOT_FOUND');
            }
            $quyenLoi = QuyenLoiGoiTap::query()->where('goi_tap_id', $goiTap->getKey())->lockForUpdate()->first();
            $hienTai = CarbonImmutable::now('UTC');
            $values = $this->duLieuQuyenLoiDeLuu($duLieu);
            if ($quyenLoi === null) {
                $this->taoQuyenLoi($goiTap, $duLieu, $hienTai);
            } else {
                $quyenLoi->forceFill([...$values, 'ngay_cap_nhat' => $hienTai])->save();
            }
            $goiTap->forceFill([
                'phien_ban_cau_hinh' => (int) $goiTap->phien_ban_cau_hinh + 1,
                'ngay_cap_nhat' => $hienTai,
            ])->save();

            return $this->duLieu($goiTap->refresh()->load('quyenLoiGoiTap'));
        }, 3);
    }

    private function taoQuyenLoi(GoiTap $goiTap, array $duLieu, CarbonImmutable $hienTai): void
    {
        QuyenLoiGoiTap::query()->create([
            'goi_tap_id' => $goiTap->getKey(),
            ...$this->duLieuQuyenLoiDeLuu($duLieu),
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ]);
    }

    /** @return array<string, bool|int|null> */
    private function duLieuQuyenLoiDeLuu(array $duLieu): array
    {
        return [
            'cho_phep_vao_phong_tap' => (bool) $duLieu['gym_access'],
            'cho_phep_tro_ly_tap_luyen' => (bool) $duLieu['fitness_assistant'],
            'gioi_han_luot_tro_ly' => $duLieu['fitness_assistant_limit'] === null
                ? null
                : (int) $duLieu['fitness_assistant_limit'],
            'cho_phep_tro_chuyen_huan_luyen_vien' => (bool) $duLieu['trainer_chat'],
            'so_buoi_huan_luyen_vien' => (int) $duLieu['direct_trainer_sessions'],
        ];
    }

    private function damBaoQuyenLoiHopLe(array $duLieu): void
    {
        $ai = (bool) $duLieu['fitness_assistant'];
        $limit = $duLieu['fitness_assistant_limit'];
        $hopLeAi = (! $ai && $limit !== null && (int) $limit === 0)
            || ($ai && ($limit === null || (int) $limit > 0));
        $coQuyen = (bool) $duLieu['gym_access']
            || $ai
            || (bool) $duLieu['trainer_chat']
            || (int) $duLieu['direct_trainer_sessions'] > 0;
        if (! $hopLeAi || ! $coQuyen) {
            throw new CatalogWorkflowException('Cấu hình quyền lợi không hợp lệ.', 422, 'INVALID_PACKAGE_BENEFITS');
        }
    }

    private function loiTruyVan(QueryException $exception, string $message): CatalogWorkflowException
    {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            return new CatalogWorkflowException($message, 409, 'PACKAGE_CONFLICT');
        }

        throw $exception;
    }

    /** @return array<string, mixed> */
    private function duLieu(GoiTap $goiTap): array
    {
        $quyenLoi = $goiTap->quyenLoiGoiTap;

        return [
            'id' => (int) $goiTap->getKey(),
            'branch_id' => (int) $goiTap->chi_nhanh_id,
            'code' => (string) $goiTap->ma_goi,
            'name' => (string) $goiTap->ten_goi,
            'price' => (int) $goiTap->gia,
            'currency' => 'VND',
            'duration_days' => (int) $goiTap->thoi_han_ngay,
            'description' => $goiTap->mo_ta,
            'status' => (string) $goiTap->trang_thai,
            'configuration_version' => (int) $goiTap->phien_ban_cau_hinh,
            'created_by_id' => (int) $goiTap->nguoi_tao_id,
            'benefits' => $quyenLoi === null ? null : [
                'gym_access' => (bool) $quyenLoi->cho_phep_vao_phong_tap,
                'fitness_assistant' => (bool) $quyenLoi->cho_phep_tro_ly_tap_luyen,
                'fitness_assistant_limit' => $quyenLoi->gioi_han_luot_tro_ly,
                'trainer_chat' => (bool) $quyenLoi->cho_phep_tro_chuyen_huan_luyen_vien,
                'direct_trainer_sessions' => (int) $quyenLoi->so_buoi_huan_luyen_vien,
            ],
            'created_at' => $goiTap->ngay_tao?->toISOString(),
            'updated_at' => $goiTap->ngay_cap_nhat?->toISOString(),
        ];
    }
}
