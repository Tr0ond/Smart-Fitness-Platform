<?php

namespace App\Services;

use App\Models\NguoiDung;

class ProfileService
{
    public function __construct(
        private readonly AuthenticationService $xacThuc,
        private readonly MemberProfileService $hoiVien,
        private readonly TrainerProfileService $huanLuyenVien,
    ) {}

    /** @return array<string, mixed> */
    public function layTongHop(NguoiDung $nguoiDung): array
    {
        $vaiTro = $this->xacThuc->layMaVaiTroDangHoatDong($nguoiDung);
        $cacHoSo = [];

        if (in_array('MEMBER', $vaiTro, true) && ($hoSo = $this->hoiVien->timNeuCo($nguoiDung))) {
            $cacHoSo['member'] = $this->hoiVien->duLieuHoSo($hoSo, true);
        }
        if (in_array('PT', $vaiTro, true) && ($hoSo = $this->huanLuyenVien->timNeuCo($nguoiDung))) {
            $cacHoSo['trainer'] = $this->huanLuyenVien->duLieuHoSo($hoSo);
        }

        return [
            'user' => $this->duLieuTaiKhoan($nguoiDung),
            'active_roles' => $vaiTro,
            'profiles' => $cacHoSo,
        ];
    }

    /** @return array<string, mixed> */
    public function capNhatTaiKhoan(NguoiDung $nguoiDung, array $duLieu): array
    {
        $anhXa = ['name' => 'ho_ten', 'phone' => 'so_dien_thoai', 'avatar_url' => 'anh_dai_dien'];
        foreach ($anhXa as $truongApi => $cot) {
            if (array_key_exists($truongApi, $duLieu)) {
                $nguoiDung->{$cot} = $duLieu[$truongApi];
            }
        }

        if ($nguoiDung->isDirty()) {
            $nguoiDung->save();
        }

        return $this->duLieuTaiKhoan($nguoiDung->refresh());
    }

    /** @return array<string, mixed> */
    public function duLieuTaiKhoan(NguoiDung $nguoiDung): array
    {
        return [
            'id' => $nguoiDung->getKey(),
            'branch_id' => $nguoiDung->chi_nhanh_id,
            'name' => $nguoiDung->ho_ten,
            'email' => $nguoiDung->thu_dien_tu,
            'phone' => $nguoiDung->so_dien_thoai,
            'avatar_url' => $nguoiDung->anh_dai_dien,
            'status' => $nguoiDung->trang_thai,
            'created_at' => $nguoiDung->ngay_tao?->toISOString(),
            'updated_at' => $nguoiDung->ngay_cap_nhat?->toISOString(),
        ];
    }
}
