<?php

namespace App\Http\Controllers\Api\Payment;

use App\Exceptions\Payments\PaymentWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CreateOrderRequest;
use App\Models\NguoiDung;
use App\Services\Payments\OrderPaymentService;
use App\Services\Payments\OrderQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function tao(CreateOrderRequest $request, int $package, OrderPaymentService $service): JsonResponse
    {
        try {
            /** @var NguoiDung $nguoiDung */
            $nguoiDung = $request->user();
            $duLieu = $service->taoDon($nguoiDung, $package, (string) $request->validated('idempotency_key'));

            return response()->json(['data' => $duLieu], 201);
        } catch (PaymentWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }

    public function danhSach(Request $request, OrderQueryService $service): JsonResponse
    {
        return response()->json(['data' => $service->layDanhSachCuaToi($request->user())]);
    }

    public function chiTiet(Request $request, int $order, OrderQueryService $service): JsonResponse
    {
        return response()->json(['data' => $service->layChiTietCuaToi($request->user(), $order)]);
    }

    public function thanhToan(Request $request, int $order, OrderQueryService $service): JsonResponse
    {
        return response()->json(['data' => $service->layThanhToanCuaToi($request->user(), $order)]);
    }
}
