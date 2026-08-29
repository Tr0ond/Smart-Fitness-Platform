<?php

namespace Tests\Unit;

use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Gateways\PayOSGateway;
use PayOS\Crypto\CryptoProvider;
use Tests\TestCase;

class PayOSSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'payos.client_id' => 'signature-client',
            'payos.api_key' => 'signature-api-key',
            'payos.checksum_key' => 'known-checksum-key',
            'payos.base_url' => 'https://api-merchant.payos.vn',
        ]);
    }

    public function test_official_sdk_create_signature_matches_known_fixture(): void
    {
        $crypto = new CryptoProvider;
        $data = [
            'amount' => 500000,
            'cancelUrl' => 'https://example.test/cancel',
            'description' => 'SFP 42',
            'orderCode' => 42001,
            'returnUrl' => 'https://example.test/return',
        ];

        $signature = $crypto->createSignatureOfPaymentRequest($data, 'known-checksum-key');
        $this->assertSame('b2f2a3a091b887abd7527f4446de4c8a3548c06a2e61663b3c10b9f19e63c5df', $signature);
        $data['amount'] = 499000;
        $this->assertNotSame($signature, $crypto->createSignatureOfPaymentRequest($data, 'known-checksum-key'));
    }

    public function test_gateway_uses_official_sdk_to_verify_webhook_and_rejects_tampering(): void
    {
        $data = $this->duLieuWebhook();
        $payload = [
            'code' => '00',
            'desc' => 'success',
            'success' => true,
            'data' => $data,
            'signature' => (new CryptoProvider)->createSignatureFromObj($data, 'known-checksum-key'),
        ];
        $gateway = new PayOSGateway;

        $this->assertSame(42001, $gateway->xacMinhWebhook($payload)['orderCode']);

        $payload['data']['amount'] = 499000;
        $this->expectException(InvalidWebhookSignatureException::class);
        $gateway->xacMinhWebhook($payload);
    }

    public function test_webhook_wrong_key_and_signature_case_are_rejected(): void
    {
        $data = $this->duLieuWebhook();
        $signature = (new CryptoProvider)->createSignatureFromObj($data, 'wrong-key');
        $payload = ['code' => '00', 'desc' => 'success', 'success' => true, 'data' => $data, 'signature' => $signature];

        try {
            (new PayOSGateway)->xacMinhWebhook($payload);
            $this->fail('Wrong checksum key must fail.');
        } catch (InvalidWebhookSignatureException) {
            $this->assertTrue(true);
        }

        $payload['signature'] = strtoupper((string) (new CryptoProvider)->createSignatureFromObj($data, 'known-checksum-key'));
        $this->expectException(InvalidWebhookSignatureException::class);
        (new PayOSGateway)->xacMinhWebhook($payload);
    }

    /** @return array<string, mixed> */
    private function duLieuWebhook(): array
    {
        return [
            'orderCode' => 42001,
            'amount' => 500000,
            'description' => 'SFP 42',
            'accountNumber' => 'TEST',
            'reference' => 'REF-KNOWN',
            'transactionDateTime' => '2026-08-29 12:30:45',
            'currency' => 'VND',
            'paymentLinkId' => 'plink_42001',
            'code' => '00',
            'desc' => 'success',
            'counterAccountBankId' => null,
            'counterAccountBankName' => null,
            'counterAccountName' => null,
            'counterAccountNumber' => null,
            'virtualAccountName' => null,
            'virtualAccountNumber' => null,
        ];
    }
}
