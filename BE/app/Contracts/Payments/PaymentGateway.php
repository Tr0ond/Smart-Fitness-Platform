<?php

namespace App\Contracts\Payments;

use App\Data\Payments\PaymentLinkResult;

interface PaymentGateway
{
    /**
     * @param  array{order_code: int, amount: int, description: string, cancel_url: string, return_url: string, expired_at: int}  $duLieu
     */
    public function taoLienKetThanhToan(array $duLieu): PaymentLinkResult;

    /** @return array<string, mixed> Dữ liệu payOS đã được SDK xác minh chữ ký. */
    public function xacMinhWebhook(array $payload): array;
}
