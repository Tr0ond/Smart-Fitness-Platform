<?php

namespace App\Http\Controllers\Api\Gym;

use App\Exceptions\Gym\GymWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gym\CheckInRequest;
use App\Http\Requests\Gym\IssueGymQrRequest;
use App\Models\NguoiDung;
use App\Services\Gym\GymCheckInQueryService;
use App\Services\Gym\GymCheckInService;
use App\Services\Gym\GymQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GymController extends Controller
{
    public function phatHanhQr(IssueGymQrRequest $request, GymQrService $service): JsonResponse
    {
        try {
            /** @var NguoiDung $nguoiDung */
            $nguoiDung = $request->user();

            return response()
                ->json(['data' => $service->phatHanh($nguoiDung)], 201)
                ->header('Cache-Control', 'no-store');
        } catch (GymWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function xacNhan(CheckInRequest $request, GymCheckInService $service): JsonResponse
    {
        try {
            /** @var NguoiDung $nguoiDung */
            $nguoiDung = $request->user();

            return response()->json([
                'data' => $service->xacNhan($nguoiDung, (string) $request->validated('qr_token')),
            ]);
        } catch (GymWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function lichSu(Request $request, GymCheckInQueryService $service): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $service->layCuaNguoiDung($nguoiDung)]);
    }

    private function loi(GymWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
