<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CreateEquipmentRequest;
use App\Http\Requests\Admin\Catalog\CreateExerciseRequest;
use App\Http\Requests\Admin\Catalog\CreateMuscleGroupRequest;
use App\Http\Requests\Admin\Catalog\ListExercisesRequest;
use App\Http\Requests\Admin\Catalog\UpdateEquipmentRequest;
use App\Http\Requests\Admin\Catalog\UpdateExerciseRequest;
use App\Http\Requests\Admin\Catalog\UpdateMuscleGroupRequest;
use App\Models\NguoiDung;
use App\Services\Admin\ExerciseCatalogAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseCatalogController extends Controller
{
    public function danhSachDungCu(Request $request, ExerciseCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json(['data' => $service->danhSachDungCu($this->actor($request))]));
    }

    public function taoDungCu(CreateEquipmentRequest $request, ExerciseCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->taoDungCu($this->actor($request), $request->validated()),
        ], 201));
    }

    public function capNhatDungCu(
        UpdateEquipmentRequest $request,
        ExerciseCatalogAdminService $service,
        int $equipment,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->capNhatDungCu($this->actor($request), $equipment, $request->validated()),
        ]));
    }

    public function danhSachNhomCo(Request $request, ExerciseCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json(['data' => $service->danhSachNhomCo($this->actor($request))]));
    }

    public function taoNhomCo(CreateMuscleGroupRequest $request, ExerciseCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->taoNhomCo($this->actor($request), $request->validated()),
        ], 201));
    }

    public function capNhatNhomCo(
        UpdateMuscleGroupRequest $request,
        ExerciseCatalogAdminService $service,
        int $muscleGroup,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->capNhatNhomCo($this->actor($request), $muscleGroup, $request->validated()),
        ]));
    }

    public function danhSachBaiTap(ListExercisesRequest $request, ExerciseCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->danhSachBaiTap($this->actor($request), $request->validated()),
        ]));
    }

    public function chiTietBaiTap(
        Request $request,
        ExerciseCatalogAdminService $service,
        int $exercise,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->chiTietBaiTap($this->actor($request), $exercise),
        ]));
    }

    public function taoBaiTap(CreateExerciseRequest $request, ExerciseCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->taoBaiTap($this->actor($request), $request->validated()),
        ], 201));
    }

    public function capNhatBaiTap(
        UpdateExerciseRequest $request,
        ExerciseCatalogAdminService $service,
        int $exercise,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->capNhatBaiTap($this->actor($request), $exercise, $request->validated()),
        ]));
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
