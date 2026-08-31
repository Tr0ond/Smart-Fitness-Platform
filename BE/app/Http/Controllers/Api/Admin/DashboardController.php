<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminDashboardRequest;
use App\Models\NguoiDung;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function tongQuan(AdminDashboardRequest $request, AdminDashboardService $service): JsonResponse
    {
        try {
            /** @var NguoiDung $actor */
            $actor = $request->user();

            return response()->json(['data' => $service->tongQuan(
                $actor,
                $request->safe()->only(['from', 'to']),
            )]);
        } catch (AuthWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }
}
