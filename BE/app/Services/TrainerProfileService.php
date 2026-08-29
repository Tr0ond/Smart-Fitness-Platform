<?php

namespace App\Services;

use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use Illuminate\Support\Facades\DB;

class TrainerProfileService
{
    /** @return array<string, mixed> */
    public function lay(NguoiDung $nguoiDung): array
    {
        return $this->duLieuHoSo($this->timHoSoCuaNguoiDung($nguoiDung));
    }

    /** @return array<string, mixed> */
    public function capNhat(NguoiDung $nguoiDung, array $duLieu): array
    {
        return DB::transaction(function () use ($nguoiDung, $duLieu): array {
            $hoSo = $this->timHoSoCuaNguoiDung($nguoiDung, true);
            $anhXa = ['introduction' => 'gioi_thieu', 'specialties' => 'chuyen_mon'];

            foreach ($anhXa as $truongApi => $cot) {
                if (array_key_exists($truongApi, $duLieu)) {
                    $hoSo->{$cot} = $duLieu[$truongApi];
                }
            }

            if ($hoSo->isDirty()) {
                $hoSo->save();
            }

            return $this->duLieuHoSo($hoSo->refresh());
        });
    }

    public function timNeuCo(NguoiDung $nguoiDung): ?HoSoHuanLuyenVien
    {
        return HoSoHuanLuyenVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
    }

    /** @return array<string, mixed> */
    public function duLieuHoSo(HoSoHuanLuyenVien $hoSo): array
    {
        return [
            'id' => $hoSo->getKey(),
            'trainer_code' => $hoSo->ma_huan_luyen_vien,
            'introduction' => $hoSo->gioi_thieu,
            'specialties' => $hoSo->chuyen_mon,
            'status' => $hoSo->trang_thai,
            'updated_at' => $hoSo->ngay_cap_nhat?->toISOString(),
        ];
    }

    private function timHoSoCuaNguoiDung(NguoiDung $nguoiDung, bool $khoa = false): HoSoHuanLuyenVien
    {
        $truyVan = HoSoHuanLuyenVien::query()->where('nguoi_dung_id', $nguoiDung->getKey());
        if ($khoa) {
            $truyVan->lockForUpdate();
        }

        $hoSo = $truyVan->first();
        abort_if($hoSo === null, 404, 'Không tìm thấy hồ sơ huấn luyện viên.');

        return $hoSo;
    }
}
