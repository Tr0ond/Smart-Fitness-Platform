<?php

namespace App\Http\Controllers\Api\Pt;

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pt\ListPtChatMessagesRequest;
use App\Http\Requests\Pt\ResolveCurrentPtChatRequest;
use App\Http\Requests\Pt\SendPtChatMessageRequest;
use App\Models\NguoiDung;
use App\Services\Pt\Chat\PtChatConversationService;
use App\Services\Pt\Chat\PtChatMessageService;
use App\Services\Pt\Chat\PtChatQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PtChatController extends Controller
{
    public function danhSach(Request $request, PtChatQueryService $service): JsonResponse
    {
        try {
            return response()->json(['data' => $service->danhSach($this->nguoiDung($request))]);
        } catch (PtChatWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function hienTai(
        ResolveCurrentPtChatRequest $request,
        PtChatConversationService $service,
    ): JsonResponse {
        try {
            $phanCongId = $request->validated('assignment_id');

            return response()->json([
                'data' => $service->hienTai(
                    $this->nguoiDung($request),
                    $phanCongId === null ? null : (int) $phanCongId,
                ),
            ]);
        } catch (PtChatWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function chiTiet(
        Request $request,
        PtChatQueryService $service,
        int $conversation,
    ): JsonResponse {
        try {
            return response()->json([
                'data' => $service->chiTiet($this->nguoiDung($request), $conversation),
            ]);
        } catch (PtChatWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function tinNhans(
        ListPtChatMessagesRequest $request,
        PtChatQueryService $service,
        int $conversation,
    ): JsonResponse {
        try {
            $ketQua = $service->tinNhans(
                $this->nguoiDung($request),
                $conversation,
                $request->validated('before_sequence') === null
                    ? null
                    : (int) $request->validated('before_sequence'),
                (int) ($request->validated('limit') ?? 50),
            );

            return response()->json($ketQua);
        } catch (PtChatWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function gui(
        SendPtChatMessageRequest $request,
        PtChatMessageService $service,
        int $conversation,
    ): JsonResponse {
        try {
            $ketQua = $service->gui(
                $this->nguoiDung($request),
                $conversation,
                $request->safe()->only(['client_message_id', 'content']),
            );

            return response()->json(['data' => $ketQua], $ketQua['replayed'] ? 200 : 201);
        } catch (PtChatWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }

    private function loi(PtChatWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
