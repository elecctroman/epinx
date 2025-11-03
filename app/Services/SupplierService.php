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

    public function __construct(
        private readonly PDO $connection,
        private readonly QueueService $queue
    )
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
        $this->queue->enqueue($type, $payload, $delaySeconds);
    }

    public function nextJob(): ?array
    {
        return $this->queue->reserve();
    }

    public function markJobAttempted(int $jobId, ?string $error = null): void
    {
        $this->queue->markAttempted($jobId, $error);
    }

    public function deleteJob(int $jobId): void
    {
        $this->queue->delete($jobId);
    }

    private function findMapping(int $productId): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM supplier_product_mappings WHERE product_id = :product_id LIMIT 1');
        $statement->execute(['product_id' => $productId]);

        $mapping = $statement->fetch(PDO::FETCH_ASSOC);

        return $mapping ?: null;
    }

    public function restockVariant(int $variantId): void
    {
        $statement = $this->connection->prepare('SELECT pv.product_id, spm.supplier, spm.remote_product_id FROM product_variants pv LEFT JOIN supplier_product_mappings spm ON spm.product_id = pv.product_id WHERE pv.id = :id LIMIT 1');
        $statement->execute(['id' => $variantId]);
        $variant = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$variant || empty($variant['supplier']) || empty($variant['remote_product_id'])) {
            return;
        }

        $remoteId = $variant['remote_product_id'];
        $remoteIds = is_array($remoteId) ? $remoteId : [$remoteId];
        $this->adapter((string) $variant['supplier'])->syncStock(array_map('intval', $remoteIds));
    }

    public function queuePendingFollowUps(): int
    {
        $statement = $this->connection->query('SELECT DISTINCT o.reference FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE oi.delivery_status = "processing" AND o.status IN ("paid", "processing")');
        $references = $statement ? $statement->fetchAll(PDO::FETCH_COLUMN) : [];
        $queued = 0;

        foreach ($references as $reference) {
            $this->queueJob('supplier.order_followup', ['reference' => $reference], 60);
            $queued++;
        }

        return $queued;
    }

    /**
     * @return array<string, int>
     */
    public function syncCatalog(): array
    {
        $statement = $this->connection->query('SELECT spm.*, p.id AS product_id FROM supplier_product_mappings spm INNER JOIN products p ON p.id = spm.product_id');
        $mappings = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

        $updatedProducts = 0;
        $updatedVariants = 0;

        foreach ($mappings as $mapping) {
            $metadata = [];
            if (!empty($mapping['metadata'])) {
                try {
                    $metadata = json_decode((string) $mapping['metadata'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $metadata = [];
                }
            }

            $basePrice = $metadata['base_price'] ?? null;
            if ($basePrice !== null) {
                $margin = (float) ($mapping['margin_percent'] ?? 0);
                $price = round((float) $basePrice * (1 + $margin / 100), 2);
                $update = $this->connection->prepare('UPDATE products SET price = :price, updated_at = NOW() WHERE id = :id');
                $update->execute([
                    'price' => $price,
                    'id' => $mapping['product_id'],
                ]);
                $updatedProducts++;
            }

            if (!empty($metadata['variants']) && is_array($metadata['variants'])) {
                foreach ($metadata['variants'] as $variantId => $variantPrice) {
                    $margin = (float) ($mapping['margin_percent'] ?? 0);
                    $price = round((float) $variantPrice * (1 + $margin / 100), 2);
                    $update = $this->connection->prepare('UPDATE product_variants SET price = :price, updated_at = NOW() WHERE id = :id');
                    $update->execute([
                        'price' => $price,
                        'id' => (int) $variantId,
                    ]);
                    $updatedVariants++;
                }
            }
        }

        return [
            'products' => $updatedProducts,
            'variants' => $updatedVariants,
        ];
    }

    private function adapter(string $supplier): SupplierAdapterInterface
    {
        return $this->adapters[$supplier] ?? $this->adapters['turkpin'];
    }
}
