<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ReplaceAvailabilityRequest;
use App\Http\Requests\Profile\ReplaceEquipmentRequest;
use App\Http\Requests\Profile\UpdateMemberProfileRequest;
use App\Models\NguoiDung;
use App\Services\MemberProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberProfileController extends Controller
{
    public function hienThi(Request $request, MemberProfileService $hoSo): JsonResponse
    {
        return response()->json(['data' => $hoSo->lay($this->nguoiDung($request))]);
    }

    public function capNhat(UpdateMemberProfileRequest $request, MemberProfileService $hoSo): JsonResponse
    {
        return response()->json([
            'data' => $hoSo->capNhat($this->nguoiDung($request), $request->validated()),
        ]);
    }

    public function lichRanh(Request $request, MemberProfileService $hoSo): JsonResponse
    {
        return response()->json(['data' => $hoSo->layLichRanh($this->nguoiDung($request))]);
    }

    public function thayTheLichRanh(
        ReplaceAvailabilityRequest $request,
        MemberProfileService $hoSo,
    ): JsonResponse {
        return response()->json([
            'data' => $hoSo->thayTheLichRanh($this->nguoiDung($request), $request->validated('days')),
        ]);
    }

    public function dungCu(Request $request, MemberProfileService $hoSo): JsonResponse
    {
        return response()->json(['data' => $hoSo->layDungCu($this->nguoiDung($request))]);
    }

    public function thayTheDungCu(
        ReplaceEquipmentRequest $request,
        MemberProfileService $hoSo,
    ): JsonResponse {
        return response()->json([
            'data' => $hoSo->thayTheDungCu($this->nguoiDung($request), $request->validated('equipment_ids')),
        ]);
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }
}
