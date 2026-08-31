<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CreateWorkoutTemplateRequest;
use App\Http\Requests\Admin\Catalog\CreateWorkoutTemplateRevisionRequest;
use App\Http\Requests\Admin\Catalog\UpdateWorkoutTemplateRequest;
use App\Models\NguoiDung;
use App\Services\Admin\WorkoutTemplateCatalogAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutTemplateCatalogController extends Controller
{
    public function danhSach(Request $request, WorkoutTemplateCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json(['data' => $service->danhSach($this->actor($request))]));
    }

    public function chiTiet(
        Request $request,
        WorkoutTemplateCatalogAdminService $service,
        int $workoutTemplate,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->chiTiet($this->actor($request), $workoutTemplate),
        ]));
    }

    public function tao(
        CreateWorkoutTemplateRequest $request,
        WorkoutTemplateCatalogAdminService $service,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->tao($this->actor($request), $request->validated()),
        ], 201));
    }

    public function capNhat(
        UpdateWorkoutTemplateRequest $request,
        WorkoutTemplateCatalogAdminService $service,
        int $workoutTemplate,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->capNhat($this->actor($request), $workoutTemplate, $request->validated()),
        ]));
    }

    public function taoPhienBanMoi(
        CreateWorkoutTemplateRevisionRequest $request,
        WorkoutTemplateCatalogAdminService $service,
        int $workoutTemplate,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->taoPhienBanMoi($this->actor($request), $workoutTemplate, $request->validated()),
        ], 201));
    }

    private function actor(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }

    private function xuLy(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (AuthWorkflowException|CatalogWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }
}
