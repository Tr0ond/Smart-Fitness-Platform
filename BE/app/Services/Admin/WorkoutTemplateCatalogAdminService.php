<?php

namespace App\Services\Admin;

use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Models\BaiTap;
use App\Models\BaiTapTrongGiaoAn;
use App\Models\GiaoAnMau;
use App\Models\NgayTrongGiaoAn;
use App\Models\NguoiDung;
use App\Models\NhatKyHeThong;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkoutTemplateCatalogAdminService
{
    public function __construct(private readonly AdminActorGuard $guard) {}

    /** Trả toàn bộ template quản trị, kể cả template đã ngừng sử dụng. */
    public function danhSach(NguoiDung $actor): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);

        return GiaoAnMau::query()
            ->withCount('ngayTrongGiaoAns')
            ->orderBy('ma_giao_an')
            ->orderBy('id')
            ->get()
            ->map(fn (GiaoAnMau $giaoAn): array => $this->duLieuTomTat($giaoAn))
            ->values()
            ->all();
    }

    /** Trả cây template quản trị bằng DTO allow-list, không che trạng thái ngừng sử dụng. */
    public function chiTiet(NguoiDung $actor, int $giaoAnId): array
    {
        $this->guard->damBaoQuanTriVienHienTai($actor);
        $giaoAn = $this->truyVanCay()->find($giaoAnId);
        if (! $giaoAn instanceof GiaoAnMau) {
            throw new CatalogWorkflowException('Không tìm thấy giáo án mẫu.', 404, 'WORKOUT_TEMPLATE_NOT_FOUND');
        }

        return $this->duLieu($giaoAn);
    }

    /**
     * Tạo template và toàn bộ cây ngày/bài trong một transaction.
     * Code, creator và content version luôn do Backend quyết định.
     */
    public function tao(NguoiDung $actor, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $duLieu): array {
                $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $this->xacThucCauTruc((int) $duLieu['sessions_per_week'], $duLieu['days']);
                $hienTai = CarbonImmutable::now('UTC');
                $giaoAn = GiaoAnMau::query()->create([
                    'ma_giao_an' => strtoupper(trim((string) $duLieu['code'])),
                    'ten_giao_an' => trim((string) $duLieu['name']),
                    'mo_ta' => isset($duLieu['description']) ? trim((string) $duLieu['description']) : null,
                    'muc_tieu' => trim((string) $duLieu['goal']),
                    'trinh_do' => trim((string) $duLieu['level']),
                    'so_buoi_moi_tuan' => (int) $duLieu['sessions_per_week'],
                    'phien_ban_noi_dung' => 1,
                    'trang_thai' => (string) ($duLieu['status'] ?? 'NGUNG_SU_DUNG'),
                    'nguoi_tao_id' => $actorDaKhoa->getKey(),
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                $this->luuCay($giaoAn, $duLieu['days'], $hienTai);

                return $this->duLieu($this->truyVanCay()->findOrFail($giaoAn->getKey()));
            }, 3);
        } catch (QueryException $exception) {
            throw $this->loiTruyVan($exception);
        }
    }

    /**
     * PATCH chỉ sửa metadata/trạng thái của catalog hiện tại. Cây ngày/bài luôn
     * bất biến sau khi tạo, nên mọi revision nội dung phải đi qua taoPhienBanMoi.
     */
    public function capNhat(NguoiDung $actor, int $giaoAnId, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $giaoAnId, $duLieu): array {
                $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $giaoAn = GiaoAnMau::query()->lockForUpdate()->find($giaoAnId);
                if (! $giaoAn instanceof GiaoAnMau) {
                    throw new CatalogWorkflowException('Không tìm thấy giáo án mẫu.', 404, 'WORKOUT_TEMPLATE_NOT_FOUND');
                }

                $soBuoi = (int) ($duLieu['sessions_per_week'] ?? $giaoAn->so_buoi_moi_tuan);
                if (array_key_exists('sessions_per_week', $duLieu)) {
                    $soNgayHienTai = NgayTrongGiaoAn::query()->where('giao_an_mau_id', $giaoAnId)->count();
                    if ($soNgayHienTai !== $soBuoi) {
                        throw new CatalogWorkflowException(
                            'Số buổi mỗi tuần phải bằng số ngày trong giáo án.',
                            422,
                            'INVALID_WORKOUT_TEMPLATE_STRUCTURE',
                        );
                    }
                }

                $values = [];
                foreach ([
                    'name' => 'ten_giao_an',
                    'description' => 'mo_ta',
                    'goal' => 'muc_tieu',
                    'level' => 'trinh_do',
                    'sessions_per_week' => 'so_buoi_moi_tuan',
                    'status' => 'trang_thai',
                ] as $api => $cot) {
                    if (array_key_exists($api, $duLieu)) {
                        $values[$cot] = $api === 'sessions_per_week'
                            ? (int) $duLieu[$api]
                            : (is_string($duLieu[$api]) ? trim($duLieu[$api]) : $duLieu[$api]);
                    }
                }
                $noiDungThayDoi = array_intersect_key($duLieu, array_flip([
                    'name', 'description', 'goal', 'level', 'sessions_per_week',
                ])) !== [];
                $hienTai = CarbonImmutable::now('UTC');
                if ($noiDungThayDoi) {
                    $values['phien_ban_noi_dung'] = (int) $giaoAn->phien_ban_noi_dung + 1;
                }
                if ($values !== []) {
                    $values['ngay_cap_nhat'] = $hienTai;
                    $giaoAn->forceFill($values)->save();
                }

                return $this->duLieu($this->truyVanCay()->findOrFail($giaoAn->getKey()));
            }, 3);
        } catch (QueryException $exception) {
            throw $this->loiTruyVan($exception);
        }
    }

    /**
     * Copy-on-write revision: giữ nguyên template/cây cũ, tạo template mới rồi
     * ngừng sử dụng template cũ trong cùng transaction.
     *
     * @return array{new_template_id:int,replaces_template_id:int,content_version:int,status:string,previous_template_status:string}
     */
    public function taoPhienBanMoi(NguoiDung $actor, int $giaoAnId, array $duLieu): array
    {
        try {
            return DB::transaction(function () use ($actor, $giaoAnId, $duLieu): array {
                $actorDaKhoa = $this->guard->khoaVaDamBaoQuanTriVien($actor);
                $giaoAnCu = GiaoAnMau::query()->lockForUpdate()->find($giaoAnId);
                if (! $giaoAnCu instanceof GiaoAnMau) {
                    throw new CatalogWorkflowException('Không tìm thấy giáo án mẫu.', 404, 'WORKOUT_TEMPLATE_NOT_FOUND');
                }
                if ($giaoAnCu->trang_thai !== 'HOAT_DONG'
                    || (int) $giaoAnCu->phien_ban_noi_dung !== (int) $duLieu['expected_content_version']) {
                    throw new CatalogWorkflowException(
                        'Giáo án mẫu đã có phiên bản mới hoặc không còn dùng được.',
                        409,
                        'WORKOUT_TEMPLATE_STALE',
                    );
                }

                $this->xacThucCauTruc((int) $duLieu['sessions_per_week'], $duLieu['days']);
                $hienTai = CarbonImmutable::now('UTC');
                $trangThaiCu = (string) $giaoAnCu->trang_thai;
                $giaoAnMoi = GiaoAnMau::query()->create([
                    'ma_giao_an' => strtoupper(trim((string) $duLieu['new_code'])),
                    'ten_giao_an' => trim((string) $duLieu['name']),
                    'mo_ta' => isset($duLieu['description']) ? trim((string) $duLieu['description']) : null,
                    'muc_tieu' => trim((string) $duLieu['goal']),
                    'trinh_do' => trim((string) $duLieu['level']),
                    'so_buoi_moi_tuan' => (int) $duLieu['sessions_per_week'],
                    'phien_ban_noi_dung' => ((int) $giaoAnCu->phien_ban_noi_dung) + 1,
                    'trang_thai' => (string) ($duLieu['status'] ?? 'HOAT_DONG'),
                    'nguoi_tao_id' => $actorDaKhoa->getKey(),
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
                $this->luuCay($giaoAnMoi, $duLieu['days'], $hienTai);
                $giaoAnCu->forceFill([
                    'trang_thai' => 'NGUNG_SU_DUNG',
                    'ngay_cap_nhat' => $hienTai,
                ])->save();
                NhatKyHeThong::query()->create([
                    'nguoi_thuc_hien_id' => $actorDaKhoa->getKey(),
                    'loai_tac_nhan' => 'NGUOI_DUNG',
                    'hanh_dong' => 'TAO_PHIEN_BAN_GIAO_AN_MAU',
                    'loai_doi_tuong' => 'GIAO_AN_MAU',
                    'dinh_danh_doi_tuong' => $giaoAnMoi->getKey(),
                    'khoa_tuong_quan' => (string) Str::uuid(),
                    'du_lieu_truoc' => [
                        'giao_an_mau_id' => (int) $giaoAnCu->getKey(),
                        'phien_ban_noi_dung' => (int) $giaoAnCu->phien_ban_noi_dung,
                        'trang_thai' => $trangThaiCu,
                    ],
                    'du_lieu_sau' => [
                        'giao_an_mau_id' => (int) $giaoAnMoi->getKey(),
                        'ma_giao_an' => $giaoAnMoi->ma_giao_an,
                        'phien_ban_noi_dung' => (int) $giaoAnMoi->phien_ban_noi_dung,
                        'thay_the_giao_an_mau_id' => (int) $giaoAnCu->getKey(),
                    ],
                    'ket_qua' => 'THANH_CONG',
                    'thuc_hien_luc' => $hienTai,
                    'ngay_tao' => $hienTai,
                ]);

                return [
                    'new_template_id' => (int) $giaoAnMoi->getKey(),
                    'replaces_template_id' => (int) $giaoAnCu->getKey(),
                    'content_version' => (int) $giaoAnMoi->phien_ban_noi_dung,
                    'status' => (string) $giaoAnMoi->trang_thai,
                    'previous_template_status' => (string) $giaoAnCu->trang_thai,
                ];
            }, 3);
        } catch (QueryException $exception) {
            throw $this->loiTruyVan($exception);
        }
    }

    private function xacThucCauTruc(int $soBuoi, array $days): void
    {
        if ($soBuoi !== count($days)) {
            throw new CatalogWorkflowException(
                'Số buổi mỗi tuần phải bằng số ngày trong giáo án.',
                422,
                'INVALID_WORKOUT_TEMPLATE_STRUCTURE',
            );
        }

        $thuTuNgay = [];
        $baiTapIds = [];
        foreach ($days as $day) {
            $order = (int) $day['order'];
            if (isset($thuTuNgay[$order])) {
                throw new CatalogWorkflowException('Thứ tự ngày bị trùng.', 422, 'INVALID_WORKOUT_TEMPLATE_STRUCTURE');
            }
            $thuTuNgay[$order] = true;
            $thuTuBai = [];
            foreach ($day['exercises'] as $exercise) {
                $exerciseOrder = (int) $exercise['order'];
                if (isset($thuTuBai[$exerciseOrder])) {
                    throw new CatalogWorkflowException('Thứ tự bài tập bị trùng trong ngày.', 422, 'INVALID_WORKOUT_TEMPLATE_STRUCTURE');
                }
                if ((int) $exercise['min_reps'] > (int) $exercise['max_reps']) {
                    throw new CatalogWorkflowException('Khoảng số lần lặp không hợp lệ.', 422, 'INVALID_WORKOUT_TEMPLATE_STRUCTURE');
                }
                $thuTuBai[$exerciseOrder] = true;
                $baiTapIds[] = (int) $exercise['exercise_id'];
            }
        }

        $baiTapIds = array_values(array_unique($baiTapIds));
        if ($baiTapIds !== [] && BaiTap::query()
            ->whereIn('id', $baiTapIds)
            ->where('trang_thai', 'HOAT_DONG')
            ->lockForUpdate()
            ->count() !== count($baiTapIds)) {
            throw new CatalogWorkflowException(
                'Giáo án chứa bài tập không tồn tại hoặc đã ngừng sử dụng.',
                422,
                'INVALID_WORKOUT_TEMPLATE_EXERCISE',
            );
        }
    }

    private function luuCay(GiaoAnMau $giaoAn, array $days, CarbonImmutable $hienTai): void
    {
        foreach ($days as $day) {
            $ngay = NgayTrongGiaoAn::query()->create([
                'giao_an_mau_id' => $giaoAn->getKey(),
                'so_thu_tu' => (int) $day['order'],
                'ten_ngay' => trim((string) $day['name']),
                'thoi_luong_du_kien_phut' => (int) $day['estimated_minutes'],
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            foreach ($day['exercises'] as $exercise) {
                BaiTapTrongGiaoAn::query()->create([
                    'ngay_trong_giao_an_id' => $ngay->getKey(),
                    'bai_tap_id' => (int) $exercise['exercise_id'],
                    'so_thu_tu' => (int) $exercise['order'],
                    'so_hiep_muc_tieu' => (int) $exercise['target_sets'],
                    'so_lan_lap_toi_thieu' => (int) $exercise['min_reps'],
                    'so_lan_lap_toi_da' => (int) $exercise['max_reps'],
                    'thoi_gian_nghi_giay' => (int) $exercise['rest_seconds'],
                    'ghi_chu' => isset($exercise['notes']) ? trim((string) $exercise['notes']) : null,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
            }
        }
    }

    private function truyVanCay()
    {
        return GiaoAnMau::query()->with([
            'ngayTrongGiaoAns' => fn ($query) => $query->orderBy('so_thu_tu')->orderBy('id'),
            'ngayTrongGiaoAns.baiTapTrongGiaoAns' => fn ($query) => $query->orderBy('so_thu_tu')->orderBy('id'),
            'ngayTrongGiaoAns.baiTapTrongGiaoAns.baiTap',
        ]);
    }

    private function loiTruyVan(QueryException $exception): CatalogWorkflowException
    {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            return new CatalogWorkflowException('Mã hoặc thứ tự giáo án đã tồn tại.', 409, 'WORKOUT_TEMPLATE_CONFLICT');
        }

        throw $exception;
    }

    /** @return array<string, mixed> */
    private function duLieuTomTat(GiaoAnMau $giaoAn): array
    {
        return [
            'id' => (int) $giaoAn->getKey(),
            'code' => (string) $giaoAn->ma_giao_an,
            'name' => (string) $giaoAn->ten_giao_an,
            'goal' => (string) $giaoAn->muc_tieu,
            'level' => (string) $giaoAn->trinh_do,
            'sessions_per_week' => (int) $giaoAn->so_buoi_moi_tuan,
            'content_version' => (int) $giaoAn->phien_ban_noi_dung,
            'status' => (string) $giaoAn->trang_thai,
            'created_by_id' => (int) $giaoAn->nguoi_tao_id,
            'day_count' => isset($giaoAn->ngay_trong_giao_ans_count)
                ? (int) $giaoAn->ngay_trong_giao_ans_count
                : $giaoAn->ngayTrongGiaoAns->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function duLieu(GiaoAnMau $giaoAn): array
    {
        return [
            ...$this->duLieuTomTat($giaoAn),
            'description' => $giaoAn->mo_ta,
            'days' => $giaoAn->ngayTrongGiaoAns->map(fn (NgayTrongGiaoAn $ngay): array => [
                'id' => (int) $ngay->getKey(),
                'order' => (int) $ngay->so_thu_tu,
                'name' => (string) $ngay->ten_ngay,
                'estimated_minutes' => (int) $ngay->thoi_luong_du_kien_phut,
                'exercises' => $ngay->baiTapTrongGiaoAns->map(fn (BaiTapTrongGiaoAn $muc): array => [
                    'id' => (int) $muc->getKey(),
                    'exercise_id' => (int) $muc->bai_tap_id,
                    'exercise_name' => $muc->baiTap?->ten_bai_tap,
                    'order' => (int) $muc->so_thu_tu,
                    'target_sets' => (int) $muc->so_hiep_muc_tieu,
                    'min_reps' => (int) $muc->so_lan_lap_toi_thieu,
                    'max_reps' => (int) $muc->so_lan_lap_toi_da,
                    'rest_seconds' => (int) $muc->thoi_gian_nghi_giay,
                    'notes' => $muc->ghi_chu,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
