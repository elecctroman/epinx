<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

class WebhookService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly OrderService $orders,
        private readonly DeliveryService $delivery,
        private readonly QueueService $queue
    ) {
    }

    /**
     * @param array<string, mixed> $rawPayload
     * @param array<string, mixed> $verifiedPayload
     */
    public function store(string $provider, array $rawPayload, array $verifiedPayload, bool $processed = false): int
    {
        $statement = $this->connection->prepare('INSERT INTO webhooks (provider, payload, processed, created_at) VALUES (:provider, :payload, :processed, NOW())');
        $statement->execute([
            'provider' => $provider,
            'payload' => json_encode([
                'raw' => $rawPayload,
                'verified' => $verifiedPayload,
            ], JSON_THROW_ON_ERROR),
            'processed' => $processed ? 1 : 0,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function enqueueProcessing(int $webhookId): void
    {
        $this->queue->enqueue('webhook.process', ['webhook_id' => $webhookId]);
    }

    public function markProcessed(int $webhookId): void
    {
        $payload = $this->fetchPayload($webhookId);
        $payload['processed_at'] = date('c');

        $statement = $this->connection->prepare('UPDATE webhooks SET processed = 1, payload = :payload WHERE id = :id');
        $statement->execute([
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'id' => $webhookId,
        ]);
    }

    public function markFailed(int $webhookId, string $error): void
    {
        $payload = $this->fetchPayload($webhookId);
        $payload['last_error'] = $error;

        $statement = $this->connection->prepare('UPDATE webhooks SET payload = :payload WHERE id = :id');
        $statement->execute([
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'id' => $webhookId,
        ]);
    }

    public function process(int $webhookId): void
    {
        $statement = $this->connection->prepare('SELECT * FROM webhooks WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $webhookId]);
        $webhook = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$webhook) {
            throw new RuntimeException('Webhook not found for processing.');
        }

        if ((int) $webhook['processed'] === 1) {
            return;
        }

        $payload = json_decode((string) $webhook['payload'], true, 512, JSON_THROW_ON_ERROR);
        $verified = (array) ($payload['verified'] ?? []);
        $reference = (string) ($verified['reference'] ?? '');
        $status = (string) ($verified['status'] ?? 'pending');

        if ($reference === '') {
            throw new RuntimeException('Webhook payload missing reference.');
        }

        if (in_array($status, ['paid', 'completed'], true)) {
            $this->orders->markPaid($reference);
            $this->delivery->handlePaidOrder($reference);
        } elseif (in_array($status, ['failed', 'cancelled'], true)) {
            $this->orders->markFailed($reference);
        }

        $this->markProcessed($webhookId);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPayload(int $webhookId): array
    {
        $statement = $this->connection->prepare('SELECT payload FROM webhooks WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $webhookId]);
        $payload = $statement->fetchColumn();

        return $payload ? json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR) : [];
    }
}
