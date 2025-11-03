<?php
declare(strict_types=1);

namespace App\Integrations\Suppliers;

interface SupplierAdapterInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function placeOrder(array $payload): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncProducts(): array;

    /**
     * @param array<int, int> $remoteProductIds
     * @return array<int, array<string, mixed>>
     */
    public function syncStock(array $remoteProductIds): array;
}
