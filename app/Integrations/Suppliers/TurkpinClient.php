<?php
declare(strict_types=1);

namespace App\Integrations\Suppliers;

use App\Core\Config;
use RuntimeException;

class TurkpinClient implements SupplierAdapterInterface
{
    public function placeOrder(array $payload): array
    {
        return [
            'status' => 'pending',
            'remote_id' => 'TRK' . ($payload['order_id'] ?? ''),
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

    private function config(): array
    {
        $config = Config::get('suppliers.turkpin', []);
        if (empty($config['api_key'])) {
            throw new RuntimeException('Turkpin configuration is missing.');
        }

        return $config;
    }
}
