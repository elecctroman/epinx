<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\Services\PaymentService;
use PDO;

class WebhookController extends ControllerBase
{
    private OrderService $orders;
    private DeliveryService $delivery;
    private PaymentService $payments;
    private PDO $pdo;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->orders = $container->get(OrderService::class);
        $this->delivery = $container->get(DeliveryService::class);
        $this->payments = $container->get(PaymentService::class);
        $this->pdo = $container->get(Connection::class);
    }

    public function paytr(Request $request): Response
    {
        return $this->handleGateway('paytr', $request);
    }

    public function iyzico(Request $request): Response
    {
        return $this->handleGateway('iyzico', $request);
    }

    public function param(Request $request): Response
    {
        return $this->handleGateway('param', $request);
    }

    public function papara(Request $request): Response
    {
        return $this->handleGateway('papara', $request);
    }

    public function bankTransfer(Request $request): Response
    {
        return $this->handleGateway('bank_transfer', $request);
    }

    private function handleGateway(string $provider, Request $request): Response
    {
        try {
            $gateway = $this->payments->gateway($provider);
            $payload = $gateway->verifyWebhook($request);
        } catch (\Throwable $throwable) {
            $this->logWebhook($provider, $request->all(), false, $throwable->getMessage());
            return $this->json(['success' => false, 'error' => $throwable->getMessage()], 400);
        }

        $reference = (string) ($payload['reference'] ?? '');
        $status = (string) ($payload['status'] ?? 'pending');
        $this->logWebhook($provider, $request->all(), true);

        if ($reference === '') {
            return $this->json(['success' => false, 'error' => 'Missing reference'], 422);
        }

        if ($status === 'paid' || $status === 'completed') {
            $this->orders->markPaid($reference);
            try {
                $this->delivery->handlePaidOrder($reference);
            } catch (\Throwable $throwable) {
                return $this->json(['success' => true, 'message' => 'Fulfillment pending: ' . $throwable->getMessage()], 202);
            }
        } elseif (in_array($status, ['failed', 'cancelled'], true)) {
            $this->orders->markFailed($reference);
        }

        return $this->json(['success' => true]);
    }

    private function logWebhook(string $provider, array $payload, bool $processed, ?string $error = null): void
    {
        $statement = $this->pdo->prepare('INSERT INTO webhooks (provider, payload, processed, created_at) VALUES (:provider, :payload, :processed, NOW())');
        $statement->execute([
            'provider' => $provider,
            'payload' => json_encode(['payload' => $payload, 'error' => $error], JSON_THROW_ON_ERROR),
            'processed' => $processed ? 1 : 0,
        ]);
    }
}
