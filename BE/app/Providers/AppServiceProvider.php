<?php

namespace App\Providers;

use App\Auth\AccessTokenGuard;
use App\Contracts\Ai\WorkoutAiProvider;
use App\Contracts\Payments\PaymentGateway;
use App\Exceptions\Ai\AiProviderException;
use App\Gateways\GeminiWorkoutAiProvider;
use App\Gateways\PayOSGateway;
use App\Gateways\UnavailableWorkoutAiProvider;
use App\Services\AuthenticationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, PayOSGateway::class);
        $this->app->bind(WorkoutAiProvider::class, function ($app): WorkoutAiProvider {
            return match (strtolower(trim((string) config('ai.provider', 'unavailable')))) {
                'gemini' => $app->make(GeminiWorkoutAiProvider::class),
                'unavailable' => $app->make(UnavailableWorkoutAiProvider::class),
                default => throw AiProviderException::unsupportedProvider(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('access-token', function ($app, string $name, array $config): AccessTokenGuard {
            $guard = new AccessTokenGuard(
                fn (Request $request) => $app->make(AuthenticationService::class)->xacThucYeuCau($request),
                $app['request'],
                $app['auth']->createUserProvider($config['provider'] ?? null),
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        RateLimiter::for('dang-nhap', function (Request $request): Limit {
            $soLanMoiPhut = max(1, (int) config('auth.login_rate_limit_per_minute', 5));

            return Limit::perMinute($soLanMoiPhut)->by((string) $request->ip());
        });
        RateLimiter::for('dang-ky', function (Request $request): Limit {
            $soLanMoiPhut = max(1, (int) config('auth.register_rate_limit_per_minute', 5));

            return Limit::perMinute($soLanMoiPhut)->by((string) $request->ip());
        });
        RateLimiter::for('quen-mat-khau', function (Request $request): Limit {
            $soLanMoiPhut = max(1, (int) config('auth.forgot_password_rate_limit_per_minute', 5));

            return Limit::perMinute($soLanMoiPhut)->by((string) $request->ip());
        });
        RateLimiter::for('dat-lai-mat-khau', function (Request $request): Limit {
            $soLanMoiPhut = max(1, (int) config('auth.reset_password_rate_limit_per_minute', 10));

            return Limit::perMinute($soLanMoiPhut)->by((string) $request->ip());
        });
    }
}
