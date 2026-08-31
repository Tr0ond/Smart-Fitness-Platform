<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\NguoiDung;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\RegistrationService;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /** Public registration chỉ tạo MEMBER và không phát hành token ngầm. */
    public function dangKy(RegisterRequest $request, RegistrationService $dangKy): JsonResponse
    {
        try {
            $nguoiDung = $dangKy->dangKy($request->validated());

            return response()->json([
                'data' => [
                    'id' => (int) $nguoiDung->getKey(),
                    'name' => (string) $nguoiDung->ho_ten,
                    'email' => (string) $nguoiDung->thu_dien_tu,
                    'status' => (string) $nguoiDung->trang_thai,
                    'roles' => ['MEMBER'],
                ],
                'message' => 'Đăng ký thành công.',
            ], 201);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    /** Cùng response bên ngoài cho email có và không tồn tại. */
    public function quenMatKhau(ForgotPasswordRequest $request, PasswordResetService $datLai): JsonResponse
    {
        try {
            $datLai->yeuCau((string) $request->validated('email'));
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }

        return response()->json([
            'data' => null,
            'message' => 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
        ]);
    }

    /** Consume reset credential đúng một lần; không đăng nhập tự động. */
    public function datLaiMatKhau(ResetPasswordRequest $request, PasswordResetService $datLai): JsonResponse
    {
        try {
            $datLai->datLai(
                (string) $request->validated('token'),
                (string) $request->validated('password'),
            );

            return response()->json([
                'data' => null,
                'message' => 'Đặt lại mật khẩu thành công.',
            ]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

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

    private function loi(AuthWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
