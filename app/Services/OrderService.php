<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

class OrderService
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @param array<int, array{product_id:int, variant_id:int|null, quantity:int}> $items
     * @param array<string, mixed> $billing
     * @return array<string, mixed>
     */
    public function createOrder(?int $userId, array $items, array $billing): array
    {
        if (empty($items)) {
            throw new PDOException('Cannot create an order with an empty cart.');
        }

        $this->connection->beginTransaction();

        try {
            $reference = $this->generateReference();
            $total = 0.0;

            $orderInsert = $this->connection->prepare('INSERT INTO orders (user_id, reference, status, total, currency, created_at, updated_at) VALUES (:user_id, :reference, :status, :total, :currency, NOW(), NOW())');
            $orderInsert->execute([
                'user_id' => $userId,
                'reference' => $reference,
                'status' => 'pending',
                'total' => 0,
                'currency' => $billing['currency'] ?? 'USD',
            ]);
            $orderId = (int) $this->connection->lastInsertId();

            $itemInsert = $this->connection->prepare('INSERT INTO order_items (order_id, product_id, product_variant_id, quantity, price) VALUES (:order_id, :product_id, :variant_id, :quantity, :price)');

            $inputPayload = $billing['delivery_inputs'] ?? [];

            foreach ($items as $item) {
                $productData = $this->resolveProductPricing($item['product_id'], $item['variant_id']);
                $linePrice = $productData['price'] * $item['quantity'];
                $total += $linePrice;

                $itemInsert->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $productData['price'],
                ]);

                $orderItemId = (int) $this->connection->lastInsertId();
                if (!empty($inputPayload)) {
                    $this->connection->prepare('UPDATE order_items SET delivery_json = JSON_SET(COALESCE(delivery_json, JSON_OBJECT()), "$.customer_input", CAST(:input AS JSON)) WHERE id = :id')
                        ->execute([
                            'input' => json_encode($inputPayload, JSON_THROW_ON_ERROR),
                            'id' => $orderItemId,
                        ]);
                }
            }

            $total = round($total, 2);

            $updateTotal = $this->connection->prepare('UPDATE orders SET total = :total, updated_at = NOW() WHERE id = :id');
            $updateTotal->execute([
                'total' => $total,
                'id' => $orderId,
            ]);

            $paymentInsert = $this->connection->prepare('INSERT INTO payments (order_id, provider, transaction_reference, amount, status, created_at) VALUES (:order_id, :provider, NULL, :amount, :status, NOW())');
            $paymentInsert->execute([
                'order_id' => $orderId,
                'provider' => $billing['provider'] ?? 'manual',
                'amount' => $total,
                'status' => 'pending',
            ]);

            $this->connection->commit();

            return [
                'id' => $orderId,
                'reference' => $reference,
                'total' => $total,
                'currency' => $billing['currency'] ?? 'USD',
                'status' => 'pending',
            ];
        } catch (PDOException $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function markPaid(string $reference): bool
    {
        return $this->updateOrderStatus($reference, 'paid');
    }

    public function markFailed(string $reference): bool
    {
        return $this->updateOrderStatus($reference, 'failed');
    }

    public function markCancelled(string $reference): bool
    {
        return $this->updateOrderStatus($reference, 'cancelled');
    }

    public function findByReference(string $reference): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM orders WHERE reference = :reference LIMIT 1');
        $statement->execute(['reference' => $reference]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return null;
        }

        $items = $this->connection->prepare('SELECT oi.*, p.name, p.fulfillment_type, pv.name AS variant_name FROM order_items oi
            INNER JOIN products p ON p.id = oi.product_id
            LEFT JOIN product_variants pv ON pv.id = oi.product_variant_id
            WHERE oi.order_id = :order_id');
        $items->execute(['order_id' => $order['id']]);
        $order['items'] = [];
        foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $item['delivery_json'] = $item['delivery_json'] ? json_decode((string) $item['delivery_json'], true, 512, JSON_THROW_ON_ERROR) : [];
            $order['items'][] = $item;
        }

        return $order;
    }

    private function generateReference(): string
    {
        return strtoupper(bin2hex(random_bytes(4)));
    }

    private function resolveProductPricing(int $productId, ?int $variantId): array
    {
        if ($variantId !== null) {
            $statement = $this->connection->prepare('SELECT price, product_id FROM product_variants WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $variantId]);
            $variant = $statement->fetch(PDO::FETCH_ASSOC);
            if ($variant && (int) $variant['product_id'] === $productId) {
                return ['price' => (float) $variant['price']];
            }
        }

        $statement = $this->connection->prepare('SELECT price FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $productId]);
        $price = $statement->fetchColumn();

        if ($price === false) {
            throw new PDOException('Product not found for order creation.');
        }

        return ['price' => (float) $price];
    }

    private function updateOrderStatus(string $reference, string $status): bool
    {
        $order = $this->findByReference($reference);
        if (!$order) {
            return false;
        }

        $this->connection->beginTransaction();
        try {
            $updateOrder = $this->connection->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
            $updateOrder->execute([
                'status' => $status,
                'id' => $order['id'],
            ]);

            $paymentStatus = match ($status) {
                'paid', 'completed' => 'completed',
                'failed', 'cancelled', 'refunded' => 'failed',
                default => 'pending',
            };

            $updatePayment = $this->connection->prepare('UPDATE payments SET status = :status, processed_at = CASE WHEN :status = "pending" THEN processed_at ELSE NOW() END WHERE order_id = :order_id');
            $updatePayment->execute([
                'status' => $paymentStatus,
                'order_id' => $order['id'],
            ]);

            $this->connection->commit();
        } catch (PDOException $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return true;
    }
}
