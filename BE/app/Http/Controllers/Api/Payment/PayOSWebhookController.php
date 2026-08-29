<?php

namespace App\Http\Controllers\Api\Payment;

use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Http\Controllers\Controller;
use App\Services\Payments\PayOSWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayOSWebhookController extends Controller
{
    public function xuLy(Request $request, PayOSWebhookService $service): JsonResponse
    {
        try {
            $ketQua = $service->xuLy($request->all());

            return response()->json(['data' => $ketQua]);
        } catch (InvalidWebhookSignatureException $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }
    }
}
