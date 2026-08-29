<?php

namespace App\Gateways;

use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\PaymentLinkResult;
use App\Exceptions\Payments\InvalidWebhookSignatureException;
use App\Exceptions\Payments\PaymentGatewayException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PayOS\Core\HTTPClient;
use PayOS\Exceptions\APIException;
use PayOS\Exceptions\ConnectionException;
use PayOS\Exceptions\ConnectionTimeoutError;
use PayOS\Exceptions\PayOSException;
use PayOS\Exceptions\TooManyRequestException;
use PayOS\Models\V2\PaymentRequests\CreatePaymentLinkRequest;
use PayOS\PayOS;
use Throwable;

class PayOSGateway implements PaymentGateway
{
    private ?PayOS $client = null;

    public function taoLienKetThanhToan(array $duLieu): PaymentLinkResult
    {
        try {
            $request = new CreatePaymentLinkRequest(
                orderCode: $duLieu['order_code'],
                amount: $duLieu['amount'],
                description: $duLieu['description'],
                cancelUrl: $duLieu['cancel_url'],
                returnUrl: $duLieu['return_url'],
                expiredAt: $duLieu['expired_at'],
            );
            $ketQua = $this->client()->paymentRequests->create($request);

            return new PaymentLinkResult(
                orderCode: $ketQua->orderCode,
                amount: $ketQua->amount,
                currency: $ketQua->currency,
                paymentLinkId: $ketQua->paymentLinkId,
                checkoutUrl: $ketQua->checkoutUrl,
                qrCode: $ketQua->qrCode,
                expiredAt: $ketQua->expiredAt,
            );
        } catch (PaymentGatewayException $exception) {
            throw $exception;
        } catch (TooManyRequestException $exception) {
            throw new PaymentGatewayException('PAYOS_RATE_LIMIT', true, 503, $exception);
        } catch (ConnectionTimeoutError $exception) {
            throw new PaymentGatewayException('PAYOS_TIMEOUT', true, 503, $exception);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('PAYOS_CONNECTION', true, 503, $exception);
        } catch (APIException $exception) {
            $status = $exception->status ?? 502;
            $retryable = $status === 429 || $status >= 500;
            $code = match (true) {
                $status === 401 || $status === 403 => 'PAYOS_AUTH',
                $status === 429 => 'PAYOS_RATE_LIMIT',
                $status >= 500 => 'PAYOS_UPSTREAM',
                default => 'PAYOS_REJECTED',
            };

            throw new PaymentGatewayException($code, $retryable, $retryable ? 503 : 502, $exception);
        } catch (PayOSException $exception) {
            throw new PaymentGatewayException('PAYOS_INVALID_RESPONSE', false, 502, $exception);
        } catch (Throwable $exception) {
            throw new PaymentGatewayException('PAYOS_CLIENT_ERROR', false, 502, $exception);
        }
    }

    public function xacMinhWebhook(array $payload): array
    {
        try {
            return $this->client()->webhooks->verify($payload, ['asArray' => true]);
        } catch (Throwable $exception) {
            throw new InvalidWebhookSignatureException;
        }
    }

    private function client(): PayOS
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $clientId = (string) config('payos.client_id');
        $apiKey = (string) config('payos.api_key');
        $checksumKey = (string) config('payos.checksum_key');
        if ($clientId === '' || $apiKey === '' || $checksumKey === '') {
            throw new PaymentGatewayException('PAYOS_NOT_CONFIGURED', false, 503);
        }

        $timeout = max(1.0, (float) config('payos.timeout_seconds', 10));
        $httpFactory = new HttpFactory;
        $httpClient = new HTTPClient(new Client([
            'timeout' => $timeout,
            'connect_timeout' => min($timeout, 5.0),
        ]), $httpFactory, $httpFactory);

        return $this->client = new PayOS(
            clientId: $clientId,
            apiKey: $apiKey,
            checksumKey: $checksumKey,
            baseURL: (string) config('payos.base_url'),
            maxRetries: 0,
            httpClient: $httpClient,
        );
    }
}
