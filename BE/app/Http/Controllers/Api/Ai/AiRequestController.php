<?php

namespace App\Http\Controllers\Api\Ai;

use App\Exceptions\Ai\AiWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\CreateAiRequest;
use App\Models\NguoiDung;
use App\Services\Ai\AiRequestQueryService;
use App\Services\Ai\AiRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiRequestController extends Controller
{
    public function tao(CreateAiRequest $request, AiRequestService $service): JsonResponse
    {
        try {
            /** @var NguoiDung $nguoiDung */
            $nguoiDung = $request->user();
            $duLieu = $service->tao(
                $nguoiDung,
                $request->safe()->only(['request_type', 'prompt']),
                $request->idempotencyKey(),
            );

            return response()->json(['data' => $duLieu], 201);
        } catch (AiWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function danhSach(Request $request, AiRequestQueryService $query): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $query->danhSach($nguoiDung)]);
    }

    public function chiTiet(Request $request, int $assistantRequest, AiRequestQueryService $query): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $query->chiTiet($nguoiDung, $assistantRequest)]);
    }

    public function deXuat(Request $request, int $proposal, AiRequestQueryService $query): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $query->deXuat($nguoiDung, $proposal)]);
    }

    private function loi(AiWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
