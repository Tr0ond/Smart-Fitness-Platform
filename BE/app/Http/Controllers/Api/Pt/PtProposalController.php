<?php

namespace App\Http\Controllers\Api\Pt;

use App\Exceptions\Pt\PtWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pt\ConfirmPtProposalRequest;
use App\Http\Requests\Pt\CreatePtNoteRequest;
use App\Http\Requests\Pt\CreatePtProposalRequest;
use App\Http\Requests\Pt\RejectPtProposalRequest;
use App\Models\NguoiDung;
use App\Services\Pt\PtNoteService;
use App\Services\Pt\PtProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtProposalController extends Controller
{
    public function tao(
        CreatePtProposalRequest $request,
        int $member,
        PtProposalService $service,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $service->tao(
            $this->nguoiDung($request),
            $member,
            $request->safe()->only(['change_type', 'title', 'explanation', 'effective_from', 'plan']),
            $request->idempotencyKey(),
        ), 201);
    }

    public function danhSachCuaPt(Request $request, int $member, PtProposalService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->danhSachCuaHuanLuyenVien(
            $this->nguoiDung($request),
            $member,
        ));
    }

    public function danhSach(Request $request, PtProposalService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->danhSachCuaHoiVien($this->nguoiDung($request)));
    }

    public function chiTiet(Request $request, int $proposal, PtProposalService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->chiTiet($this->nguoiDung($request), $proposal));
    }

    public function xacNhan(
        ConfirmPtProposalRequest $request,
        int $proposal,
        PtProposalService $service,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $service->xacNhan(
            $this->nguoiDung($request),
            $proposal,
            $request->idempotencyKey(),
        ));
    }

    public function tuChoi(
        RejectPtProposalRequest $request,
        int $proposal,
        PtProposalService $service,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $service->tuChoi(
            $this->nguoiDung($request),
            $proposal,
            $request->idempotencyKey(),
            $request->validated('reason'),
        ));
    }

    public function taoGhiChu(CreatePtNoteRequest $request, int $member, PtNoteService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->tao(
            $this->nguoiDung($request),
            $member,
            $request->safe()->only(['content', 'plan_id', 'session_id']),
        ), 201);
    }

    public function ghiChusCuaPt(Request $request, int $member, PtNoteService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->danhSachCuaHuanLuyenVien(
            $this->nguoiDung($request),
            $member,
        ));
    }

    public function ghiChus(Request $request, PtNoteService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->danhSachCuaHoiVien($this->nguoiDung($request)));
    }

    /** @param callable():array<string,mixed>|array<int,array<string,mixed>> $hanhDong */
    private function thucThi(callable $hanhDong, int $status = 200): JsonResponse
    {
        try {
            return response()->json(['data' => $hanhDong()], $status);
        } catch (PtWorkflowException $exception) {
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
