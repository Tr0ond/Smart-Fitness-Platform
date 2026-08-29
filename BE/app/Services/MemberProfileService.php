<?php

namespace App\Services;

use App\Models\DungCu;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberProfileService
{
    /** @return array<string, mixed> */
    public function lay(NguoiDung $nguoiDung, bool $kemSoThich = true): array
    {
        $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung);

        return $this->duLieuHoSo($hoSo, $kemSoThich);
    }

    /** @return array<string, mixed> */
    public function capNhat(NguoiDung $nguoiDung, array $duLieu): array
    {
        return DB::transaction(function () use ($nguoiDung, $duLieu): array {
            $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung, true);
            $anhXa = [
                'birth_date' => 'ngay_sinh',
                'gender' => 'gioi_tinh',
                'training_goal' => 'muc_tieu_tap_luyen',
                'training_experience' => 'kinh_nghiem_tap_luyen',
                'desired_training_days' => 'so_ngay_tap_mong_muon',
                'session_duration_minutes' => 'thoi_luong_moi_buoi_phut',
            ];

            foreach ($anhXa as $truongApi => $cot) {
                if (array_key_exists($truongApi, $duLieu)) {
                    $hoSo->{$cot} = $duLieu[$truongApi];
                }
            }

            if ($hoSo->isDirty()) {
                $hoSo->phien_ban_ho_so = ((int) $hoSo->phien_ban_ho_so) + 1;
                $hoSo->save();
            }

            return $this->duLieuHoSo($hoSo->refresh(), true);
        });
    }

    /** @return array{days: array<int, int>, profile_version: int} */
    public function layLichRanh(NguoiDung $nguoiDung): array
    {
        $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung);

        return $this->duLieuLichRanh($hoSo);
    }

    /** @param array<int, int> $cacNgay */
    public function thayTheLichRanh(NguoiDung $nguoiDung, array $cacNgay): array
    {
        sort($cacNgay);
        $cacNgay = array_values($cacNgay);

        return DB::transaction(function () use ($nguoiDung, $cacNgay): array {
            $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung, true);
            $hienTai = DB::table('ngay_ranh_hoi_vien')
                ->where('hoi_vien_id', $hoSo->getKey())
                ->orderBy('thu_trong_tuan')
                ->pluck('thu_trong_tuan')
                ->map(fn ($ngay) => (int) $ngay)
                ->all();

            if ($hienTai !== $cacNgay) {
                DB::table('ngay_ranh_hoi_vien')
                    ->where('hoi_vien_id', $hoSo->getKey())
                    ->whereNotIn('thu_trong_tuan', $cacNgay)
                    ->delete();

                $canThem = array_values(array_diff($cacNgay, $hienTai));
                $thoiDiem = now('UTC');
                if ($canThem !== []) {
                    DB::table('ngay_ranh_hoi_vien')->insert(array_map(
                        fn (int $ngay): array => [
                            'hoi_vien_id' => $hoSo->getKey(),
                            'thu_trong_tuan' => $ngay,
                            'ngay_tao' => $thoiDiem,
                            'ngay_cap_nhat' => $thoiDiem,
                        ],
                        $canThem,
                    ));
                }

                $this->tangPhienBan($hoSo);
            }

            return $this->duLieuLichRanh($hoSo->refresh());
        });
    }

    /** @return array{equipment: array<int, array<string, mixed>>, profile_version: int} */
    public function layDungCu(NguoiDung $nguoiDung): array
    {
        $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung);

        return $this->duLieuDungCu($hoSo);
    }

    /** @param array<int, int> $cacDungCuId */
    public function thayTheDungCu(NguoiDung $nguoiDung, array $cacDungCuId): array
    {
        sort($cacDungCuId);
        $cacDungCuId = array_values($cacDungCuId);

        return DB::transaction(function () use ($nguoiDung, $cacDungCuId): array {
            $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung, true);
            $soDungCuTonTai = DungCu::query()->whereKey($cacDungCuId)->sharedLock()->count();
            if ($soDungCuTonTai !== count($cacDungCuId)) {
                throw ValidationException::withMessages([
                    'equipment_ids' => ['Có dụng cụ không còn tồn tại.'],
                ]);
            }

            $hienTai = DB::table('dung_cu_hoi_vien')
                ->where('hoi_vien_id', $hoSo->getKey())
                ->orderBy('dung_cu_id')
                ->pluck('dung_cu_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($hienTai !== $cacDungCuId) {
                DB::table('dung_cu_hoi_vien')
                    ->where('hoi_vien_id', $hoSo->getKey())
                    ->whereNotIn('dung_cu_id', $cacDungCuId)
                    ->delete();

                $canThem = array_values(array_diff($cacDungCuId, $hienTai));
                $thoiDiem = now('UTC');
                if ($canThem !== []) {
                    DB::table('dung_cu_hoi_vien')->insert(array_map(
                        fn (int $dungCuId): array => [
                            'hoi_vien_id' => $hoSo->getKey(),
                            'dung_cu_id' => $dungCuId,
                            'ngay_tao' => $thoiDiem,
                            'ngay_cap_nhat' => $thoiDiem,
                        ],
                        $canThem,
                    ));
                }

                $this->tangPhienBan($hoSo);
            }

            return $this->duLieuDungCu($hoSo->refresh());
        });
    }

    public function timNeuCo(NguoiDung $nguoiDung): ?HoSoHoiVien
    {
        return HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
    }

    /** @return array<string, mixed> */
    public function duLieuHoSo(HoSoHoiVien $hoSo, bool $kemSoThich = true): array
    {
        $duLieu = [
            'id' => $hoSo->getKey(),
            'member_code' => $hoSo->ma_hoi_vien,
            'birth_date' => $hoSo->ngay_sinh?->format('Y-m-d'),
            'gender' => $hoSo->gioi_tinh,
            'training_goal' => $hoSo->muc_tieu_tap_luyen,
            'training_experience' => $hoSo->kinh_nghiem_tap_luyen,
            'desired_training_days' => $hoSo->so_ngay_tap_mong_muon,
            'session_duration_minutes' => $hoSo->thoi_luong_moi_buoi_phut,
            'profile_version' => $hoSo->phien_ban_ho_so,
            'plan_change_marker' => $hoSo->moc_thay_doi_ke_hoach,
            'updated_at' => $hoSo->ngay_cap_nhat?->toISOString(),
        ];

        if ($kemSoThich) {
            $duLieu['availability'] = $this->duLieuLichRanh($hoSo)['days'];
            $duLieu['equipment'] = $this->duLieuDungCu($hoSo)['equipment'];
        }

        return $duLieu;
    }

    private function timHoSoCuaNguoiDung(NguoiDung $nguoiDung, bool $khoa = false): HoSoHoiVien
    {
        $truyVan = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey());
        if ($khoa) {
            $truyVan->lockForUpdate();
        }

        $hoSo = $truyVan->first();
        abort_if($hoSo === null, 404, 'Không tìm thấy hồ sơ hội viên.');

        return $hoSo;
    }

    /** @return array{days: array<int, int>, profile_version: int} */
    private function duLieuLichRanh(HoSoHoiVien $hoSo): array
    {
        return [
            'days' => DB::table('ngay_ranh_hoi_vien')
                ->where('hoi_vien_id', $hoSo->getKey())
                ->orderBy('thu_trong_tuan')
                ->pluck('thu_trong_tuan')
                ->map(fn ($ngay) => (int) $ngay)
                ->all(),
            'profile_version' => (int) $hoSo->phien_ban_ho_so,
        ];
    }

    /** @return array{equipment: array<int, array<string, mixed>>, profile_version: int} */
    private function duLieuDungCu(HoSoHoiVien $hoSo): array
    {
        $dungCu = DungCu::query()
            ->select(['dung_cu.id', 'ma_dung_cu', 'ten_dung_cu', 'trang_thai'])
            ->join('dung_cu_hoi_vien', 'dung_cu_hoi_vien.dung_cu_id', '=', 'dung_cu.id')
            ->where('dung_cu_hoi_vien.hoi_vien_id', $hoSo->getKey())
            ->orderBy('dung_cu.id')
            ->get()
            ->map(fn (DungCu $item): array => [
                'id' => $item->getKey(),
                'code' => $item->ma_dung_cu,
                'name' => $item->ten_dung_cu,
                'status' => $item->trang_thai,
            ])
            ->all();

        return ['equipment' => $dungCu, 'profile_version' => (int) $hoSo->phien_ban_ho_so];
    }

    private function tangPhienBan(HoSoHoiVien $hoSo): void
    {
        $hoSo->phien_ban_ho_so = ((int) $hoSo->phien_ban_ho_so) + 1;
        $hoSo->save();
    }
}
