<?php

namespace Tests\Concerns;

use App\Contracts\Payments\PaymentGateway;
use App\Gateways\PayOSGateway;
use App\Models\DonMuaGoi;
use App\Models\LanThanhToan;
use Illuminate\Testing\TestResponse;
use PayOS\Crypto\CryptoProvider;
use Tests\Fakes\FakePaymentGateway;

trait CreatesPaymentFixtures
{
    protected FakePaymentGateway $fakePaymentGateway;

    protected function cauHinhPaymentTest(): void
    {
        config([
            'payos.client_id' => 'test-client-id',
            'payos.api_key' => 'test-api-key',
            'payos.checksum_key' => 'test-checksum-key-20260829',
            'payos.return_url' => 'https://frontend.test/payment/return',
            'payos.cancel_url' => 'https://frontend.test/payment/cancel',
            'payos.order_ttl_minutes' => 30,
            'payos.idempotency_ttl_hours' => 24,
            'payos.channel_code' => 'PAYOS',
        ]);
        $this->fakePaymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->fakePaymentGateway);
    }

    protected function dungGatewayPayOSXacMinhChuKy(): void
    {
        $this->app->instance(PaymentGateway::class, new PayOSGateway);
    }

    /** @return array{response: TestResponse, order: DonMuaGoi, payment: LanThanhToan, key: string} */
    protected function taoDonPaymentQuaApi(array $fixture, int $packageId, ?string $key = null): array
    {
        $key ??= $this->uuidPayment();
        $token = $this->layTokenProfile($fixture);
        $response = $this->postJson(
            '/api/packages/'.$packageId.'/orders',
            [],
            array_merge($this->bearer($token), ['Idempotency-Key' => $key]),
        )->assertCreated();
        $order = DonMuaGoi::query()->findOrFail($response->json('data.id'));
        $payment = LanThanhToan::query()->where('don_mua_goi_id', $order->getKey())->sole();

        return compact('response', 'order', 'payment', 'key');
    }

    /** @return array<string, mixed> */
    protected function webhookPayment(LanThanhToan $payment, array $ghiDe = [], ?string $checksumKey = null): array
    {
        $data = array_merge([
            'orderCode' => (int) $payment->ma_don_cong_thanh_toan,
            'amount' => (int) $payment->so_tien_yeu_cau,
            'description' => 'SFP '.$payment->don_mua_goi_id,
            'accountNumber' => 'TEST-REDACTED',
            'reference' => 'REF-'.strtoupper(bin2hex(random_bytes(6))),
            'transactionDateTime' => '2026-08-29 12:30:45',
            'currency' => 'VND',
            'paymentLinkId' => $payment->ma_lien_ket_thanh_toan,
            'code' => '00',
            'desc' => 'success',
            'counterAccountBankId' => null,
            'counterAccountBankName' => null,
            'counterAccountName' => null,
            'counterAccountNumber' => null,
            'virtualAccountName' => null,
            'virtualAccountNumber' => null,
        ], $ghiDe);
        $signature = (new CryptoProvider)->createSignatureFromObj(
            $data,
            $checksumKey ?? (string) config('payos.checksum_key'),
        );

        return [
            'code' => '00',
            'desc' => 'success',
            'success' => true,
            'data' => $data,
            'signature' => $signature,
        ];
    }

    protected function uuidPayment(): string
    {
        return (new CryptoProvider)->createUuidv4();
    }
}
