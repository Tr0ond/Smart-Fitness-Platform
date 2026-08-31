<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Throwable;

class SmartFitnessPreflightCommand extends Command
{
    protected $signature = 'smart-fitness:preflight {--no-network : Only validate configuration and internal dependencies} {--external : Also probe explicitly authorised staging Gemini/Reverb endpoints}';

    protected $description = 'Kiểm tra cấu hình production Smart Fitness mà không in secret';

    private bool $coLoi = false;

    public function handle(): int
    {
        if ($this->option('no-network') && $this->option('external')) {
            $this->line('[FAIL] Chỉ chọn một trong --no-network hoặc --external');

            return self::FAILURE;
        }

        $this->kiemTraProduction();
        $this->kiemTraDatabase();
        $this->kiemTraQueue();
        $this->kiemTraCache();
        $this->kiemTraScheduler();
        $this->kiemTraGemini();
        $this->kiemTraReverb();

        if ($this->option('external')) {
            $this->kiemTraGeminiBenNgoai();
            $this->kiemTraReverbBenNgoai();
        }

        if ($this->coLoi) {
            $this->line('PREFLIGHT_FAILED');

            return self::FAILURE;
        }

        $this->line('PREFLIGHT_OK');

        return self::SUCCESS;
    }

    private function kiemTraProduction(): void
    {
        $this->ketQua('APP_ENV=production', app()->environment('production'), 'APP_ENV must be production');
        $this->ketQua('APP_DEBUG=false', ! (bool) config('app.debug'), 'APP_DEBUG must be false in production');
    }

    private function kiemTraDatabase(): void
    {
        try {
            DB::selectOne('SELECT 1 AS ket_qua');
            $this->ketQua('Database', true);
        } catch (Throwable $exception) {
            $this->ketQua('Database', false, 'database connection is unavailable');
        }
    }

    private function kiemTraQueue(): void
    {
        $ten = trim((string) config('queue.default'));
        $driver = trim((string) config("queue.connections.{$ten}.driver"));
        if ($ten === '' || in_array($driver, ['', 'sync', 'null'], true)) {
            $this->ketQua('Queue', false, 'QUEUE_CONNECTION must not be sync/null in production');

            return;
        }

        try {
            if ($driver === 'database') {
                $connection = config("queue.connections.{$ten}.connection") ?: config('database.default');
                $table = (string) config("queue.connections.{$ten}.table", 'jobs');
                DB::connection((string) $connection)->table($table)->limit(1)->count();
            } elseif ($driver === 'redis') {
                $connection = (string) config("queue.connections.{$ten}.connection", 'default');
                Redis::connection($connection)->ping();
            } else {
                Queue::connection($ten)->size();
            }
            $this->ketQua('Queue', true);
        } catch (Throwable $exception) {
            $this->ketQua('Queue', false, 'queue backend is unavailable or not initialized');
        }
    }

    private function kiemTraCache(): void
    {
        try {
            $key = 'smart_fitness_preflight:'.Str::uuid();
            Cache::put($key, 'ok', 10);
            $hopLe = Cache::get($key) === 'ok';
            Cache::forget($key);
            $this->ketQua('Cache', $hopLe, 'cache store cannot round-trip a probe value');
        } catch (Throwable $exception) {
            $this->ketQua('Cache', false, 'cache backend is unavailable');
        }
    }

    private function kiemTraScheduler(): void
    {
        try {
            $coRetryOutbox = collect(Schedule::events())
                ->contains(fn ($event): bool => str_contains((string) $event->command, 'pt-chat:retry-outbox'));
            $this->ketQua('Scheduler configuration', $coRetryOutbox, 'pt-chat:retry-outbox schedule is missing');
        } catch (Throwable $exception) {
            $this->ketQua('Scheduler configuration', false, 'scheduler definition is unavailable');
        }
    }

    private function kiemTraGemini(): void
    {
        $provider = strtolower(trim((string) config('ai.provider')));
        $model = trim((string) config('ai.model'));
        $key = trim((string) config('ai.gemini.api_key'));
        $baseUrl = trim((string) config('ai.gemini.base_url'));
        $hopLe = $provider === 'gemini'
            && preg_match('/^[A-Za-z0-9._-]+$/', $model) === 1
            && $key !== ''
            && filter_var($baseUrl, FILTER_VALIDATE_URL) !== false;
        $this->ketQua('Gemini configuration', $hopLe, 'AI_PROVIDER, AI_MODEL, GEMINI_API_KEY or GEMINI_BASE_URL is invalid/missing');
    }

    private function kiemTraReverb(): void
    {
        $hopLe = config('broadcasting.default') === 'reverb';
        foreach (['key', 'secret', 'app_id'] as $truong) {
            $hopLe = $hopLe && trim((string) config("broadcasting.connections.reverb.{$truong}")) !== '';
        }
        $hopLe = $hopLe
            && trim((string) config('broadcasting.connections.reverb.options.host')) !== ''
            && in_array(config('broadcasting.connections.reverb.options.scheme'), ['http', 'https'], true)
            && (int) config('broadcasting.connections.reverb.options.port') > 0;
        $this->ketQua('Reverb configuration', $hopLe, 'BROADCAST_CONNECTION or REVERB credentials/endpoint is incomplete');
    }

    private function kiemTraGeminiBenNgoai(): void
    {
        if ($this->coLoi) {
            $this->ketQua('Gemini external', false, 'configuration checks must pass before external probe');

            return;
        }
        try {
            $url = rtrim((string) config('ai.gemini.base_url'), '/')
                .'/v1beta/models/'.rawurlencode((string) config('ai.model'));
            $response = Http::acceptJson()->withHeaders([
                'x-goog-api-key' => (string) config('ai.gemini.api_key'),
            ])->connectTimeout(5)->timeout(10)->get($url);
            $this->ketQua('Gemini external', $response->successful(), 'Gemini staging probe failed');
        } catch (Throwable $exception) {
            $this->ketQua('Gemini external', false, 'Gemini staging probe is unreachable');
        }
    }

    private function kiemTraReverbBenNgoai(): void
    {
        if ($this->coLoi) {
            $this->ketQua('Reverb external', false, 'configuration checks must pass before external probe');

            return;
        }
        try {
            $options = (array) config('broadcasting.connections.reverb.options');
            $url = $options['scheme'].'://'.$options['host'].':'.$options['port'];
            $response = Http::connectTimeout(5)->timeout(10)->get($url);
            $this->ketQua('Reverb external', $response->status() < 500, 'Reverb staging probe failed');
        } catch (Throwable $exception) {
            $this->ketQua('Reverb external', false, 'Reverb staging probe is unreachable');
        }
    }

    private function ketQua(string $ten, bool $dat, ?string $lyDo = null): void
    {
        if ($dat) {
            $this->line('[PASS] '.$ten);

            return;
        }
        $this->coLoi = true;
        $this->line('[FAIL] '.$ten.($lyDo === null ? '' : ': '.$lyDo));
    }
}
