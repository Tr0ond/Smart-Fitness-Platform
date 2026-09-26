<?php

namespace App\Http\Controllers\Api\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workout\ListWorkoutSessionsRequest;
use App\Models\NguoiDung;
use App\Services\Pt\PtMemberWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtMemberWorkspaceController extends Controller
{
    /** Trả coaching profile an toàn của Member trong exact current assignment. */
    public function chiTietHoiVien(Request $request, int $member, PtMemberWorkspaceService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->chiTietHoiVien($this->nguoiDung($request), $member));
    }

    /** Trả Plan chính thức hiện tại và lịch tương lai bounded theo server window. */
    public function keHoachHienTai(Request $request, int $member, PtMemberWorkspaceService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->keHoachHienTai($this->nguoiDung($request), $member));
    }

    /** Trả danh sách Workout Session read-only theo cursor bounded. */
    public function danhSachPhien(
        ListWorkoutSessionsRequest $request,
        int $member,
        PtMemberWorkspaceService $service,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $service->danhSachPhien(
            $this->nguoiDung($request),
            $member,
            (int) $request->validated('limit', 20),
            $request->validated('before_id') === null ? null : (int) $request->validated('before_id'),
        ));
    }

    /** Trả immutable Workout Session snapshot của đúng Member đã được authorize. */
    public function chiTietPhien(Request $request, int $member, int $session, PtMemberWorkspaceService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->chiTietPhien($this->nguoiDung($request), $member, $session));
    }

    /** @param callable():array<string,mixed>|array<int,array<string,mixed>> $hanhDong */
    private function thucThi(callable $hanhDong): JsonResponse
    {
        try {
            return response()->json(['data' => $hanhDong()]);
        } catch (PtWorkflowException|WorkoutWorkflowException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->safeCode,
            ], $exception->responseStatus);
        }
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }
}
