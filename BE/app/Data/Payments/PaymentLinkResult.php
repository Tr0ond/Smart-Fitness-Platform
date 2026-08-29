<?php

namespace App\Data\Payments;

final readonly class PaymentLinkResult
{
    public function __construct(
        public int $orderCode,
        public int $amount,
        public string $currency,
        public string $paymentLinkId,
        public string $checkoutUrl,
        public string $qrCode,
        public ?int $expiredAt,
    ) {}
}
