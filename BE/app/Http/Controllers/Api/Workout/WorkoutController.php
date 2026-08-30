<?php

namespace App\Http\Controllers\Api\Workout;

use App\Exceptions\Workout\WorkoutWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workout\CompleteWorkoutSessionRequest;
use App\Http\Requests\Workout\ListWorkoutScheduleRequest;
use App\Http\Requests\Workout\ListWorkoutSessionsRequest;
use App\Http\Requests\Workout\RecordWorkoutSetRequest;
use App\Http\Requests\Workout\StartWorkoutSessionRequest;
use App\Models\NguoiDung;
use App\Services\Workout\WorkoutPlanQueryService;
use App\Services\Workout\WorkoutScheduleService;
use App\Services\Workout\WorkoutSessionQueryService;
use App\Services\Workout\WorkoutSessionService;
use App\Services\Workout\WorkoutTemplateQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    public function templates(WorkoutTemplateQueryService $query): JsonResponse
    {
        return response()->json(['data' => $query->danhSach()]);
    }

    public function template(int $template, WorkoutTemplateQueryService $query): JsonResponse
    {
        return $this->thucThi(fn (): array => $query->chiTiet($template));
    }

    public function plans(Request $request, WorkoutPlanQueryService $query): JsonResponse
    {
        return $this->thucThi(fn (): array => $query->danhSach($this->nguoiDung($request)));
    }

    public function currentPlan(Request $request, WorkoutPlanQueryService $query): JsonResponse
    {
        try {
            return response()->json(['data' => $query->hienTai($this->nguoiDung($request))]);
        } catch (WorkoutWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function plan(Request $request, int $plan, WorkoutPlanQueryService $query): JsonResponse
    {
        return $this->thucThi(fn (): array => $query->chiTiet($this->nguoiDung($request), $plan));
    }

    public function schedule(ListWorkoutScheduleRequest $request, WorkoutScheduleService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->danhSach($this->nguoiDung($request), (string) $request->validated('from'), (string) $request->validated('to')));
    }

    public function skip(Request $request, int $scheduled, WorkoutScheduleService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->boQua($this->nguoiDung($request), $scheduled));
    }

    public function sessions(ListWorkoutSessionsRequest $request, WorkoutSessionQueryService $query): JsonResponse
    {
        return $this->thucThi(fn (): array => $query->danhSach(
            $this->nguoiDung($request),
            (int) $request->validated('limit', 20),
            $request->validated('before_id') === null ? null : (int) $request->validated('before_id'),
        ));
    }

    public function session(Request $request, int $session, WorkoutSessionQueryService $query): JsonResponse
    {
        return $this->thucThi(fn (): array => $query->chiTiet($this->nguoiDung($request), $session));
    }

    public function start(StartWorkoutSessionRequest $request, int $scheduled, WorkoutSessionService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->batDau($this->nguoiDung($request), $scheduled, $request->idempotencyKey()), 201);
    }

    public function recordSet(RecordWorkoutSetRequest $request, int $session, int $exercise, WorkoutSessionService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->ghiHiep(
            $this->nguoiDung($request),
            $session,
            $exercise,
            $request->safe()->only(['order', 'reps', 'weight_kg', 'actual_rest_seconds']),
            $request->idempotencyKey(),
        ), 201);
    }

    public function complete(CompleteWorkoutSessionRequest $request, int $session, WorkoutSessionService $service): JsonResponse
    {
        return $this->thucThi(fn (): array => $service->hoanThanh(
            $this->nguoiDung($request),
            $session,
            $request->idempotencyKey(),
            $request->validated('notes'),
        ));
    }

    private function thucThi(callable $hanhDong, int $status = 200): JsonResponse
    {
        try {
            return response()->json(['data' => $hanhDong()], $status);
        } catch (WorkoutWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    private function loi(WorkoutWorkflowException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage(), 'code' => $exception->safeCode], $exception->responseStatus);
    }

    private function nguoiDung(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }
}
