<?php

namespace App\Http\Controllers\Api\Membership;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use App\Services\MembershipQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function hienThi(Request $request, MembershipQueryService $membership): JsonResponse
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return response()->json(['data' => $membership->layCuaNguoiDung($nguoiDung)]);
    }
}
