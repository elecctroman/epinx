<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Security\Crypto;
use App\Core\Support\FileStorage;
use PDO;
use RuntimeException;

class DeliveryService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly FileStorage $storage,
        private readonly Mailer $mailer,
        private readonly SupplierService $supplier,
        private readonly AuditService $audit
    ) {
    }

    public function handlePaidOrder(string $reference, array $billing = []): void
    {
        $order = $this->fetchOrder($reference);
        if (!$order) {
            throw new RuntimeException('Order not found for fulfillment.');
        }

        foreach ($order['items'] as $item) {
            if ($item['delivery_status'] === 'delivered') {
                continue;
            }

            if ($item['fulfillment_type'] === 'epin') {
                $this->deliverEpin($order, $item);
            } elseif ($item['fulfillment_type'] === 'topup') {
                $input = $item['delivery_json']['customer_input'] ?? $billing['delivery_inputs'] ?? [];
                $this->initiateTopup($order, $item, (array) $input);
            }
        }

        $this->refreshOrderStatus((int) $order['id']);
    }

    public function finalizeTopup(int $orderItemId, string $status, array $details = []): void
    {
        $statement = $this->connection->prepare('SELECT oi.*, o.user_id, o.reference FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE oi.id = :id');
        $statement->execute(['id' => $orderItemId]);
        $item = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new RuntimeException('Order item not found for topup finalization.');
        }

        $newStatus = $status === 'completed' ? 'delivered' : 'pending';
        $this->connection->prepare('UPDATE order_items SET delivery_status = :status, delivery_json = JSON_SET(COALESCE(delivery_json, JSON_OBJECT()), "$.topup", :details) WHERE id = :id')
            ->execute([
                'status' => $newStatus,
                'details' => json_encode(['status' => $status, 'details' => $details], JSON_THROW_ON_ERROR),
                'id' => $orderItemId,
            ]);

        $this->refreshOrderStatus((int) $item['order_id']);
        $this->audit->log($item['user_id'] ? (int) $item['user_id'] : null, 'topup.finalized', ['item_id' => $orderItemId, 'status' => $status]);
    }

    public function allowRefund(int $orderId): bool
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = :order_id AND delivery_status = "delivered"');
        $statement->execute(['order_id' => $orderId]);
        $delivered = (int) $statement->fetchColumn();

        return $delivered === 0;
    }

    public function processRefund(int $orderId, ?int $userId = null): void
    {
        if (!$this->allowRefund($orderId)) {
            throw new RuntimeException('Refund is not allowed after delivery.');
        }

        $this->connection->prepare('UPDATE orders SET status = "refunded", updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $orderId]);
        $this->connection->prepare('UPDATE order_items SET delivery_status = "refunded" WHERE order_id = :order_id')
            ->execute(['order_id' => $orderId]);

        $this->audit->log($userId, 'order.refund', ['order_id' => $orderId]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchOrder(string $reference): ?array
    {
        $statement = $this->connection->prepare('SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.reference = :reference LIMIT 1');
        $statement->execute(['reference' => $reference]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }

        $itemStatement = $this->connection->prepare('SELECT oi.*, p.name, p.fulfillment_type, p.delivery_instructions, pv.name AS variant_name, COALESCE(JSON_EXTRACT(oi.delivery_json, "$.codes"), JSON_ARRAY()) AS raw_codes, COALESCE(JSON_EXTRACT(oi.delivery_json, "$.masked_codes"), JSON_ARRAY()) AS raw_masked, COALESCE(JSON_EXTRACT(oi.delivery_json, "$.customer_input"), JSON_OBJECT()) AS raw_input FROM order_items oi INNER JOIN products p ON p.id = oi.product_id LEFT JOIN product_variants pv ON pv.id = oi.product_variant_id WHERE oi.order_id = :order_id');
        $itemStatement->execute(['order_id' => $order['id']]);
        $items = [];
        foreach ($itemStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['delivery_json'] = [
                'codes' => json_decode((string) $row['raw_codes'], true, 512, JSON_THROW_ON_ERROR) ?: [],
                'masked_codes' => json_decode((string) $row['raw_masked'], true, 512, JSON_THROW_ON_ERROR) ?: [],
                'customer_input' => json_decode((string) $row['raw_input'], true, 512, JSON_THROW_ON_ERROR) ?: [],
            ];
            unset($row['raw_codes'], $row['raw_masked'], $row['raw_input']);
            $items[] = $row;
        }
        $order['items'] = $items;

        return $order;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $item
     */
    private function deliverEpin(array $order, array $item): void
    {
        $codes = $this->pullCodes((int) $item['product_variant_id'], (int) $item['quantity']);
        if (count($codes) < (int) $item['quantity']) {
            $this->supplier->queueJob('epin.restock', ['variant_id' => $item['product_variant_id']]);
            throw new RuntimeException('Insufficient stock for EPIN delivery.');
        }

        $masked = array_map([$this, 'maskCode'], $codes);

        $this->connection->prepare('UPDATE order_items SET delivery_status = "delivered", delivery_json = JSON_SET(COALESCE(delivery_json, JSON_OBJECT()), "$.codes", :codes, "$.masked_codes", :masked, "$.delivered_at", NOW()) WHERE id = :id')
            ->execute([
                'codes' => json_encode($codes, JSON_THROW_ON_ERROR),
                'masked' => json_encode($masked, JSON_THROW_ON_ERROR),
                'id' => $item['id'],
            ]);

        $fileContent = "Order {$order['reference']}\n" . implode("\n", $codes);
        $filePath = 'orders/' . $order['id'] . '/codes.txt';
        $this->storage->put($filePath, $fileContent);

        if (!empty($order['email'])) {
            $body = '<p>Dear customer,</p><p>Your codes are ready:</p><pre>' . htmlspecialchars(implode("\n", $masked), ENT_QUOTES, 'UTF-8') . '</pre>';
            $this->mailer->send((string) $order['email'], 'Order ' . $order['reference'] . ' Codes', $body);
        }

        $this->audit->log($order['user_id'] ? (int) $order['user_id'] : null, 'delivery.epin', ['order_id' => $order['id'], 'item_id' => $item['id']]);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $item
     * @param array<string, mixed> $input
     */
    private function initiateTopup(array $order, array $item, array $input): void
    {
        $this->connection->prepare('UPDATE order_items SET delivery_status = "processing" WHERE id = :id')
            ->execute(['id' => $item['id']]);

        $response = $this->supplier->placeOrder($item, $input);
        $this->audit->log($order['user_id'] ? (int) $order['user_id'] : null, 'delivery.topup', ['order_id' => $order['id'], 'item_id' => $item['id'], 'response' => $response]);
    }

    private function refreshOrderStatus(int $orderId): void
    {
        $statement = $this->connection->prepare('SELECT delivery_status FROM order_items WHERE order_id = :order_id');
        $statement->execute(['order_id' => $orderId]);
        $statuses = $statement->fetchAll(PDO::FETCH_COLUMN);
        if (!$statuses) {
            return;
        }

        if (count(array_unique($statuses)) === 1 && $statuses[0] === 'delivered') {
            $this->connection->prepare('UPDATE orders SET status = "completed", fulfilled_at = NOW(), updated_at = NOW() WHERE id = :id')
                ->execute(['id' => $orderId]);
            return;
        }

        if (in_array('processing', $statuses, true)) {
            $this->connection->prepare('UPDATE orders SET status = "processing", updated_at = NOW() WHERE id = :id')
                ->execute(['id' => $orderId]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function pullCodes(int $variantId, int $quantity): array
    {
        $statement = $this->connection->prepare('SELECT id, code_encrypted FROM stocks WHERE product_variant_id = :variant AND status = "available" ORDER BY id ASC LIMIT :quantity');
        $statement->bindValue(':variant', $variantId, PDO::PARAM_INT);
        $statement->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $statement->execute();
        $codes = [];
        $stockIds = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $stockIds[] = (int) $row['id'];
            $codes[] = Crypto::decrypt((string) $row['code_encrypted']);
        }

        if ($stockIds) {
            $in = implode(',', array_fill(0, count($stockIds), '?'));
            $update = $this->connection->prepare('UPDATE stocks SET status = "sold", updated_at = NOW() WHERE id IN (' . $in . ')');
            foreach ($stockIds as $index => $id) {
                $update->bindValue($index + 1, $id, PDO::PARAM_INT);
            }
            $update->execute();
        }

        return $codes;
    }

    private function maskCode(string $code): string
    {
        if (strlen($code) <= 4) {
            return str_repeat('*', strlen($code));
        }

        return substr($code, 0, 4) . str_repeat('*', max(0, strlen($code) - 6)) . substr($code, -2);
    }
}
