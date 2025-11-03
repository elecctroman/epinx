<?php
declare(strict_types=1);

namespace App\Services;

use App\Integrations\Suppliers\SupplierAdapterInterface;
use App\Integrations\Suppliers\TurkpinClient;
use App\Integrations\Suppliers\PinabiClient;
use App\Integrations\Suppliers\LotusClient;
use PDO;

class SupplierService
{
    /** @var array<string, SupplierAdapterInterface> */
    private array $adapters;

    public function __construct(private readonly PDO $connection)
    {
        $this->adapters = [
            'turkpin' => new TurkpinClient(),
            'pinabi' => new PinabiClient(),
            'lotus' => new LotusClient(),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function placeOrder(array $item, array $input): array
    {
        $mapping = $this->findMapping((int) $item['product_id']);
        $adapter = $this->adapter($mapping['supplier'] ?? 'turkpin');

        $payload = [
            'order_id' => $item['order_id'],
            'product_id' => $mapping['remote_product_id'] ?? $item['product_id'],
            'quantity' => $item['quantity'],
            'input' => $input,
        ];

        $response = $adapter->placeOrder($payload);

        $this->connection->prepare('UPDATE order_items SET delivery_status = :status, delivery_json = JSON_SET(COALESCE(delivery_json, JSON_OBJECT()), "$.supplier", :supplier, "$.remote_id", :remote, "$.last_payload", :payload) WHERE id = :id')
            ->execute([
                'status' => $response['status'] ?? 'processing',
                'supplier' => $mapping['supplier'] ?? 'turkpin',
                'remote' => $response['remote_id'] ?? null,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'id' => $item['id'],
            ]);

        return $response;
    }

    /**
     * @param array<int, int> $remoteIds
     * @return array<int, array<string, mixed>>
     */
    public function syncStock(string $supplier, array $remoteIds): array
    {
        return $this->adapter($supplier)->syncStock($remoteIds);
    }

    public function queueJob(string $type, array $payload, int $delaySeconds = 0): void
    {
        $statement = $this->connection->prepare('INSERT INTO job_queue (type, payload, available_at) VALUES (:type, :payload, DATE_ADD(NOW(), INTERVAL :delay SECOND))');
        $statement->execute([
            'type' => $type,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'delay' => $delaySeconds,
        ]);
    }

    public function nextJob(): ?array
    {
        $statement = $this->connection->query('SELECT * FROM job_queue WHERE available_at <= NOW() ORDER BY id ASC LIMIT 1');
        $job = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            return null;
        }

        return $job;
    }

    public function markJobAttempted(int $jobId, ?string $error = null): void
    {
        $statement = $this->connection->prepare('UPDATE job_queue SET attempts = attempts + 1, last_error = :error, available_at = CASE WHEN :error IS NULL THEN available_at ELSE DATE_ADD(NOW(), INTERVAL POW(2, attempts) MINUTE) END WHERE id = :id');
        $statement->execute([
            'error' => $error,
            'id' => $jobId,
        ]);
    }

    public function deleteJob(int $jobId): void
    {
        $statement = $this->connection->prepare('DELETE FROM job_queue WHERE id = :id');
        $statement->execute(['id' => $jobId]);
    }

    private function findMapping(int $productId): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM supplier_product_mappings WHERE product_id = :product_id LIMIT 1');
        $statement->execute(['product_id' => $productId]);

        $mapping = $statement->fetch(PDO::FETCH_ASSOC);

        return $mapping ?: null;
    }

    private function adapter(string $supplier): SupplierAdapterInterface
    {
        return $this->adapters[$supplier] ?? $this->adapters['turkpin'];
    }
}
