<?php

use App\Models\NguoiDung;
use App\Services\Pt\Chat\PtChatAuthorizationService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'pt.conversation.{hoiThoaiId}',
    fn (NguoiDung $nguoiDung, int $hoiThoaiId): bool => app(PtChatAuthorizationService::class)
        ->laNguoiThamGia($nguoiDung, $hoiThoaiId),
    ['guards' => ['api']],
);
