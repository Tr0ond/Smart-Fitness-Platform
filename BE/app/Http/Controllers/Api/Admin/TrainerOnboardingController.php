<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateTrainerRequest;
use App\Http\Requests\Admin\OnboardTrainerProfileRequest;
use App\Models\NguoiDung;
use App\Services\Admin\TrainerOnboardingService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainerOnboardingController extends Controller
{
    public function tao(
        CreateTrainerRequest $request,
        TrainerOnboardingService $onboarding,
        PasswordResetService $passwordReset,
    ): JsonResponse {
        try {
            $ketQua = $onboarding->tao($this->actor($request), $request->validated());
            if (! $ketQua['replayed']) {
                $passwordReset->yeuCau((string) $ketQua['account']['email']);
            }

            return response()->json(['data' => $ketQua], $ketQua['replayed'] ? 200 : 201);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    public function onboardTaiKhoanDaCo(
        OnboardTrainerProfileRequest $request,
        TrainerOnboardingService $onboarding,
        int $account,
    ): JsonResponse {
        try {
            return response()->json([
                'data' => $onboarding->onboardTaiKhoanDaCo($this->actor($request), $account, $request->validated()),
            ]);
        } catch (AuthWorkflowException $exception) {
            return $this->loi($exception);
        }
    }

    private function actor(Request $request): NguoiDung
    {
        /** @var NguoiDung $nguoiDung */
        $nguoiDung = $request->user();

        return $nguoiDung;
    }

    private function loi(AuthWorkflowException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->safeCode,
        ], $exception->responseStatus);
    }
}
