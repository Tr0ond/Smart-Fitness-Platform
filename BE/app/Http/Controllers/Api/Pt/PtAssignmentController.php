<?php

namespace App\Http\Controllers\Api\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pt\CreatePtAssignmentRequest;
use App\Http\Requests\Pt\EndPtAssignmentRequest;
use App\Http\Requests\Pt\ListPtAssignmentsRequest;
use App\Http\Requests\Pt\ReassignPtAssignmentRequest;
use App\Models\NguoiDung;
use App\Services\Pt\PtAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtAssignmentController extends Controller
{
    public function danhSach(ListPtAssignmentsRequest $request, PtAssignmentService $service): JsonResponse
    {
        try {
            return response()->json([
                'data' => $service->danhSach($this->nguoiDung($request), $request->validated()),
            ]);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function chiTiet(Request $request, PtAssignmentService $service, int $assignment): JsonResponse
    {
        try {
            return response()->json([
                'data' => $service->chiTiet($this->nguoiDung($request), $assignment),
            ]);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function tao(CreatePtAssignmentRequest $request, PtAssignmentService $service): JsonResponse
    {
        try {
            return response()->json(['data' => $service->tao($this->nguoiDung($request), $request->validated())], 201);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function ketThuc(
        EndPtAssignmentRequest $request,
        PtAssignmentService $service,
        int $assignment,
    ): JsonResponse {
        try {
            return response()->json([
                'data' => $service->ketThuc($this->nguoiDung($request), $assignment, $request->validated('reason')),
            ]);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function phanCongLai(
        ReassignPtAssignmentRequest $request,
        PtAssignmentService $service,
        int $assignment,
    ): JsonResponse {
        try {
            return response()->json([
                'data' => $service->phanCongLai($this->nguoiDung($request), $assignment, $request->validated()),
            ], 201);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function cuaHoiVien(Request $request, PtAssignmentService $service): JsonResponse
    {
        try {
            return response()->json(['data' => $service->layCuaHoiVien($this->nguoiDung($request))]);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function thanhVienCuaHuanLuyenVien(Request $request, PtAssignmentService $service): JsonResponse
    {
        try {
            return response()->json(['data' => $service->layThanhVienCuaHuanLuyenVien($this->nguoiDung($request))]);
        } catch (PtWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }

    private function loi(PtWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
