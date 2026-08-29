<?php

namespace App\Http\Controllers\Api\Package;

use App\Http\Controllers\Controller;
use App\Services\PackageCatalogService;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function danhSach(PackageCatalogService $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->layDanhSach()]);
    }

    public function chiTiet(int $package, PackageCatalogService $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->layChiTiet($package)]);
    }
}
