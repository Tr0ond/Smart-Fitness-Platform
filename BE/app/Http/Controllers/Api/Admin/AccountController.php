<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAccountsRequest;
use App\Http\Requests\Admin\ManageAccountRoleRequest;
use App\Http\Requests\Admin\UpdateAccountStatusRequest;
use App\Models\NguoiDung;
use App\Services\Admin\AccountManagementService;
use App\Services\Admin\RoleManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function danhSach(ListAccountsRequest $request, AccountManagementService $service): JsonResponse
    {
        try {
            return response()->json(['data' => $service->danhSach($this->actor($request), $request->validated())]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function chiTiet(Request $request, AccountManagementService $service, int $account): JsonResponse
    {
        try {
            return response()->json(['data' => $service->chiTiet($this->actor($request), $account)]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function capNhatTrangThai(
        UpdateAccountStatusRequest $request,
        AccountManagementService $service,
        int $account,
    ): JsonResponse {
        try {
            return response()->json([
                'data' => $service->capNhatTrangThai(
                    $this->actor($request),
                    $account,
                    (string) $request->validated('status'),
                ),
            ]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function ganVaiTro(
        ManageAccountRoleRequest $request,
        RoleManagementService $service,
        int $account,
        string $role,
    ): JsonResponse {
        try {
            return response()->json(['data' => $service->gan($this->actor($request), $account, $role)]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function thuHoiVaiTro(
        ManageAccountRoleRequest $request,
        RoleManagementService $service,
        int $account,
        string $role,
    ): JsonResponse {
        try {
            return response()->json(['data' => $service->thuHoi($this->actor($request), $account, $role)]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    private function actor(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }

    private function loi(AuthWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
