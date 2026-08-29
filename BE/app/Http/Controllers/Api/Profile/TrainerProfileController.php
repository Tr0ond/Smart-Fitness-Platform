<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateTrainerProfileRequest;
use App\Models\NguoiDung;
use App\Services\TrainerProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainerProfileController extends Controller
{
    public function hienThi(Request $request, TrainerProfileService $hoSo): JsonResponse
    {
        return response()->json(['data' => $hoSo->lay($this->nguoiDung($request))]);
    }

    public function capNhat(UpdateTrainerProfileRequest $request, TrainerProfileService $hoSo): JsonResponse
    {
        return response()->json([
            'data' => $hoSo->capNhat($this->nguoiDung($request), $request->validated()),
        ]);
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }
}
