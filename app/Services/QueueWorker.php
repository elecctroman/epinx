<?php
declare(strict_types=1);

namespace App\Services;

use JsonException;
use RuntimeException;
use Throwable;

class QueueWorker
{
    public function __construct(
        private readonly QueueService $queue,
        private readonly SupplierService $supplier,
        private readonly WebhookService $webhooks,
        private readonly DeliveryService $delivery
    ) {
    }

    public function run(int $maxJobs = 25): int
    {
        $processed = 0;

        while ($processed < $maxJobs) {
            $job = $this->queue->reserve();
            if ($job === null) {
                break;
            }

            $jobId = (int) $job['id'];
            try {
                $this->handleJob((string) $job['type'], (string) $job['payload']);
                $this->queue->delete($jobId);
            } catch (Throwable $throwable) {
                $this->queue->markAttempted($jobId, $throwable->getMessage());
                $payload = $this->decodePayload((string) $job['payload']);
                if (($job['type'] ?? '') === 'webhook.process' && isset($payload['webhook_id'])) {
                    $this->webhooks->markFailed((int) $payload['webhook_id'], $throwable->getMessage());
                }
            }

            $processed++;
        }

        return $processed;
    }

    private function handleJob(string $type, string $encodedPayload): void
    {
        $payload = $this->decodePayload($encodedPayload);

        switch ($type) {
            case 'webhook.process':
                if (!isset($payload['webhook_id'])) {
                    throw new RuntimeException('Webhook job missing identifier.');
                }
                $this->webhooks->process((int) $payload['webhook_id']);
                break;
            case 'epin.restock':
                if (isset($payload['variant_id'])) {
                    $this->supplier->restockVariant((int) $payload['variant_id']);
                }
                break;
            case 'supplier.order_followup':
                if (isset($payload['reference'])) {
                    $this->delivery->handlePaidOrder((string) $payload['reference']);
                }
                break;
            default:
                // Unknown job type; treat as processed.
                break;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $encodedPayload): array
    {
        if ($encodedPayload === '') {
            return [];
        }

        try {
            $decoded = json_decode($encodedPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
