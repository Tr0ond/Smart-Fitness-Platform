<?php

namespace App\Http\Controllers\Api\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pt\CompletePtDirectServiceRequest;
use App\Models\NguoiDung;
use App\Services\Pt\PtDirectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtDirectServiceController extends Controller
{
    public function hoanTat(CompletePtDirectServiceRequest $request, PtDirectService $service): JsonResponse
    {
        try {
            return response()->json([
                'data' => $service->hoanTat(
                    $this->nguoiDung($request),
                    $request->safe()->only(['assignment_id', 'notes']),
                    $request->idempotencyKey(),
                ),
            ], 201);
        } catch (PtWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }

    public function lichSu(Request $request, PtDirectService $service): JsonResponse
    {
        try {
            return response()->json(['data' => $service->layLichSu($this->nguoiDung($request))]);
        } catch (PtWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }
}
