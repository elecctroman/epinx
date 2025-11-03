<?php
declare(strict_types=1);

namespace App\Integrations\Payments;

use App\Core\Config;
use App\Core\Http\Request;
use RuntimeException;

class PayTRGateway implements PaymentGatewayInterface
{
    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $billing
     * @return array<string, mixed>
     */
    public function createPayment(array $order, array $billing): array
    {
        $config = Config::get('payments.providers.paytr', []);
        if (empty($config['merchant_id']) || empty($config['merchant_key']) || empty($config['merchant_salt'])) {
            throw new RuntimeException('PayTR credentials are not configured.');
        }

        $userIp = $billing['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $basket = base64_encode(json_encode([
            [$order['reference'], (string) $order['total'], $order['total']],
        ], JSON_THROW_ON_ERROR));

        $hashStr = $config['merchant_id'] . $userIp . $order['email'] . $order['total'] . $basket . $config['merchant_salt'];
        $token = base64_encode(hash_hmac('sha256', $hashStr, $config['merchant_key'], true));

        return [
            'provider' => 'paytr',
            'endpoint' => $config['endpoint'] ?? 'https://www.paytr.com/odeme/api/',
            'token' => $token,
            'basket' => $basket,
            'merchant_id' => $config['merchant_id'],
            'user_ip' => $userIp,
            'debug' => (bool) ($config['debug'] ?? false),
        ];
    }

    public function status(string $transactionId): array
    {
        return [
            'transaction_id' => $transactionId,
            'status' => 'pending',
        ];
    }

    public function verifyWebhook(Request $request): array
    {
        $config = Config::get('payments.providers.paytr', []);
        $payload = $request->input('hash');
        $status = $request->input('status');
        $orderId = $request->input('merchant_oid');

        $hashStr = $orderId . $config['merchant_salt'] . $status;
        $expected = base64_encode(hash_hmac('sha256', $hashStr, $config['merchant_key'], true));

        if (!hash_equals($expected, (string) $payload)) {
            throw new RuntimeException('Invalid PayTR webhook signature.');
        }

        return [
            'reference' => (string) $orderId,
            'status' => $status === 'success' ? 'paid' : 'failed',
        ];
    }
}
