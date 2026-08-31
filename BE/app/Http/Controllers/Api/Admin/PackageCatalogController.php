<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Exceptions\Catalog\CatalogWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\CreatePackageRequest;
use App\Http\Requests\Admin\Catalog\UpdatePackageBenefitsRequest;
use App\Http\Requests\Admin\Catalog\UpdatePackageRequest;
use App\Models\NguoiDung;
use App\Services\Admin\PackageCatalogAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageCatalogController extends Controller
{
    public function danhSach(Request $request, PackageCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json(['data' => $service->danhSach($this->actor($request))]));
    }

    public function chiTiet(Request $request, PackageCatalogAdminService $service, int $package): JsonResponse
    {
        return $this->xuLy(fn () => response()->json(['data' => $service->chiTiet($this->actor($request), $package)]));
    }

    public function tao(CreatePackageRequest $request, PackageCatalogAdminService $service): JsonResponse
    {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->tao($this->actor($request), $request->validated()),
        ], 201));
    }

    public function capNhat(
        UpdatePackageRequest $request,
        PackageCatalogAdminService $service,
        int $package,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->capNhat($this->actor($request), $package, $request->validated()),
        ]));
    }

    public function capNhatQuyenLoi(
        UpdatePackageBenefitsRequest $request,
        PackageCatalogAdminService $service,
        int $package,
    ): JsonResponse {
        return $this->xuLy(fn () => response()->json([
            'data' => $service->capNhatQuyenLoi($this->actor($request), $package, $request->validated()),
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
