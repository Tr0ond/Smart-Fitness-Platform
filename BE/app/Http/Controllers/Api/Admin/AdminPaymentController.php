<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminPaymentEventsRequest;
use App\Http\Requests\Admin\ListAdminPaymentsRequest;
use App\Models\NguoiDung;
use App\Services\Admin\AdminPaymentQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function danhSach(ListAdminPaymentsRequest $request, AdminPaymentQueryService $service): JsonResponse
    {
        return $this->traVe(fn (): array => $service->danhSachThanhToan($this->actor($request), $request->validated()));
    }

    public function chiTiet(Request $request, AdminPaymentQueryService $service, int $payment): JsonResponse
    {
        return $this->traVe(fn (): array => $service->chiTietThanhToan($this->actor($request), $payment));
    }

    public function suKiens(ListAdminPaymentEventsRequest $request, AdminPaymentQueryService $service): JsonResponse
    {
        return $this->traVe(fn (): array => $service->danhSachSuKien($this->actor($request), $request->validated()));
    }

    private function actor(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }

    private function traVe(callable $hanhDong): JsonResponse
    {
        try {
            return response()->json(['data' => $hanhDong()]);
        } catch (AuthWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }
}
