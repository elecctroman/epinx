<?php
declare(strict_types=1);

namespace App\Integrations\Suppliers;

class LotusClient implements SupplierAdapterInterface
{
    public function placeOrder(array $payload): array
    {
        return [
            'status' => 'pending',
            'remote_id' => 'LTS' . ($payload['order_id'] ?? ''),
        ];
    }

    public function syncProducts(): array
    {
        return [];
    }

    public function syncStock(array $remoteProductIds): array
    {
        return [];
    }
}
