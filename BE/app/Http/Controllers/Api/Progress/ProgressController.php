<?php

namespace App\Http\Controllers\Api\Progress;

use App\Exceptions\Progress\ProgressWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Progress\CreateBodyMeasurementRequest;
use App\Http\Requests\Progress\ExerciseProgressRequest;
use App\Http\Requests\Progress\ListBodyMeasurementsRequest;
use App\Http\Requests\Progress\ProgressPeriodRequest;
use App\Models\NguoiDung;
use App\Services\Progress\BodyMeasurementService;
use App\Services\Progress\ProgressAuthorizationService;
use App\Services\Progress\ProgressQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function taoChiSo(
        CreateBodyMeasurementRequest $request,
        ProgressAuthorizationService $authorization,
        BodyMeasurementService $body,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $body->tao(
            $this->nguoiDung($request),
            $authorization->hoiVienCuaNguoiDung($this->nguoiDung($request)),
            $request->safe()->only(['measured_at', 'weight_kg', 'height_cm', 'waist_cm', 'notes', 'entry_id']),
        ), 201);
    }

    public function chiSos(
        ListBodyMeasurementsRequest $request,
        ProgressAuthorizationService $authorization,
        BodyMeasurementService $body,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $body->danhSach(
            $authorization->hoiVienCuaNguoiDung($this->nguoiDung($request)),
            (int) $request->validated('limit', 20),
            $request->validated('before_measured_at'),
            $request->validated('before_id') === null ? null : (int) $request->validated('before_id'),
        ));
    }

    public function chiSoMoiNhat(
        Request $request,
        ProgressAuthorizationService $authorization,
        BodyMeasurementService $body,
    ): JsonResponse {
        return $this->thucThiNullable(fn (): ?array => $body->moiNhat(
            $authorization->hoiVienCuaNguoiDung($this->nguoiDung($request)),
        ));
    }

    public function tongQuan(
        ProgressPeriodRequest $request,
        ProgressAuthorizationService $authorization,
        ProgressQueryService $progress,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $progress->tongQuan(
            $authorization->hoiVienCuaNguoiDung($this->nguoiDung($request)),
            $request->safe()->only(['from', 'to']),
        ));
    }

    public function baiTap(
        ExerciseProgressRequest $request,
        int $exercise,
        ProgressAuthorizationService $authorization,
        ProgressQueryService $progress,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $progress->baiTap(
            $authorization->hoiVienCuaNguoiDung($this->nguoiDung($request)),
            $exercise,
            $request->safe()->only(['from', 'to']),
            (int) $request->validated('limit', 50),
        ));
    }

    public function tongQuanCuaPt(
        ProgressPeriodRequest $request,
        int $member,
        ProgressAuthorizationService $authorization,
        ProgressQueryService $progress,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $progress->tongQuan(
            $authorization->hoiVienDuocPhanCong($this->nguoiDung($request), $member),
            $request->safe()->only(['from', 'to']),
        ));
    }

    public function chiSosCuaPt(
        ListBodyMeasurementsRequest $request,
        int $member,
        ProgressAuthorizationService $authorization,
        BodyMeasurementService $body,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $body->danhSach(
            $authorization->hoiVienDuocPhanCong($this->nguoiDung($request), $member),
            (int) $request->validated('limit', 20),
            $request->validated('before_measured_at'),
            $request->validated('before_id') === null ? null : (int) $request->validated('before_id'),
        ));
    }

    public function baiTapCuaPt(
        ExerciseProgressRequest $request,
        int $member,
        int $exercise,
        ProgressAuthorizationService $authorization,
        ProgressQueryService $progress,
    ): JsonResponse {
        return $this->thucThi(fn (): array => $progress->baiTap(
            $authorization->hoiVienDuocPhanCong($this->nguoiDung($request), $member),
            $exercise,
            $request->safe()->only(['from', 'to']),
            (int) $request->validated('limit', 50),
        ));
    }

    /** @param callable():array<string,mixed> $hanhDong */
    private function thucThi(callable $hanhDong, int $status = 200): JsonResponse
    {
        try {
            return response()->json(['data' => $hanhDong()], $status);
        } catch (ProgressWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    /** @param callable():?array<string,mixed> $hanhDong */
    private function thucThiNullable(callable $hanhDong): JsonResponse
    {
        try {
            return response()->json(['data' => $hanhDong()]);
        } catch (ProgressWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    private function loi(ProgressWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }
}
