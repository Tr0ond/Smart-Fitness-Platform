<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\NguoiDung;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function hienThi(Request $request, ProfileService $hoSo): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $hoSo->layTongHop($nguoiDung)]);
    }

    public function capNhat(UpdateProfileRequest $request, ProfileService $hoSo): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $hoSo->capNhatTaiKhoan($nguoiDung, $request->validated())]);
    }
}
