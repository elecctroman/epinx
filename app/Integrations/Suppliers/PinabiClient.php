<?php
declare(strict_types=1);

namespace App\Integrations\Suppliers;

use RuntimeException;

class PinabiClient implements SupplierAdapterInterface
{
    public function placeOrder(array $payload): array
    {
        if (($payload['response_code'] ?? 200) >= 450) {
            throw new RuntimeException('Pinabi returned an error for order payload.');
        }

        return [
            'status' => 'processing',
            'remote_id' => 'PNB' . ($payload['order_id'] ?? ''),
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
