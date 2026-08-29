<?php

namespace App\Providers;

use App\Auth\AccessTokenGuard;
use App\Contracts\Payments\PaymentGateway;
use App\Gateways\PayOSGateway;
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
    }
}
