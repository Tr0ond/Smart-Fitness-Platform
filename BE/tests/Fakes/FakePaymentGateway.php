<?php

namespace Tests\Fakes;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\PaymentLinkResult;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Exceptions\Payments\PaymentGatewayException;

class FakePaymentGateway implements PaymentGateway
{
    /** @var array<int, array<string, mixed>> */
    public array $requests = [];

    public ?PaymentGatewayException $createException = null;

    /** @var null|callable(array<string, mixed>): PaymentLinkResult */
    public $resultFactory = null;

    public function taoLienKetThanhToan(array $duLieu): PaymentLinkResult
    {
        $this->requests[] = $duLieu;
        if ($this->createException !== null) {
            throw $this->createException;
        }
        if ($this->resultFactory !== null) {
            return ($this->resultFactory)($duLieu);
        }

        return new PaymentLinkResult(
            orderCode: $duLieu['order_code'],
            amount: $duLieu['amount'],
            currency: 'VND',
            paymentLinkId: 'plink_'.$duLieu['order_code'],
            checkoutUrl: 'https://pay.test/checkout/'.$duLieu['order_code'],
            qrCode: 'PAYOS-QR-'.$duLieu['order_code'],
            expiredAt: $duLieu['expired_at'],
        );
    }

    public function xacMinhWebhook(array $payload): array
    {
        throw new InvalidWebhookSignatureException;
    }
}
