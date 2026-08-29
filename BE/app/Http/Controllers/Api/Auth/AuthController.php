<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\NguoiDung;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /** Đăng nhập, phát hành raw token đúng một lần và không trả secret nội bộ. */
    public function dangNhap(LoginRequest $request, AuthenticationService $xacThuc): JsonResponse
    {
        $duLieu = $request->validated();
        $ketQua = $xacThuc->dangNhap(
            $duLieu['email'],
            $duLieu['password'],
            $duLieu['device_name'] ?? 'API client',
        );

        if ($ketQua === null) {
            return response()->json(['message' => 'Thông tin đăng nhập không hợp lệ.'], 401);
        }

        return response()->json([
            'data' => [
                'access_token' => $ketQua['raw_token'],
                'token_type' => 'Bearer',
                'expires_at' => $ketQua['token']->het_han_luc->toISOString(),
                'user' => $this->duLieuNguoiDung($ketQua['user'], $xacThuc),
            ],
        ]);
    }

    /** Trả thông tin tối thiểu và role đang hiệu lực của user đã xác thực. */
    public function thongTinHienTai(Request $request, AuthenticationService $xacThuc): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $this->duLieuNguoiDung($nguoiDung, $xacThuc)]);
    }

    /** Thu hồi current token, giữ nguyên mọi token thiết bị khác và hàng lịch sử. */
    public function dangXuat(Request $request, AuthenticationService $xacThuc): JsonResponse
    {
        $xacThuc->thuHoiTheHienTai($request);

        return response()->json([
            'data' => null,
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    /** @return array{id: int, name: string, email: string, status: string, roles: array<int, string>} */
    private function duLieuNguoiDung(NguoiDung $nguoiDung, AuthenticationService $xacThuc): array
    {
        return [
            'id' => $nguoiDung->getKey(),
            'name' => $nguoiDung->ho_ten,
            'email' => $nguoiDung->thu_dien_tu,
            'status' => $nguoiDung->trang_thai,
            'roles' => $xacThuc->layMaVaiTroDangHoatDong($nguoiDung),
        ];
    }
}
