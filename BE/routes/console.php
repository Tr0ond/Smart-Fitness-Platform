<?php

use App\Services\Pt\Chat\PtChatDeliveryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pt-chat:retry-outbox {--limit=}', function (PtChatDeliveryService $service): int {
    $limit = $this->option('limit');
    $result = $service->phatDangCho($limit === null ? null : (int) $limit);
    $this->line(json_encode($result, JSON_THROW_ON_ERROR));

    return 0;
})->purpose('Retry due PT Chat realtime outbox events');

Schedule::command('pt-chat:retry-outbox')
    ->everyMinute()
    ->withoutOverlapping();
