<?php
declare(strict_types=1);

namespace App\Integrations\Payments;

use App\Core\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed> $billing
     * @return array<string, mixed>
     */
    public function createPayment(array $order, array $billing): array;

    /**
     * @param string $transactionId
     * @return array<string, mixed>
     */
    public function status(string $transactionId): array;

    /**
     * @return array<string, mixed>
     */
    public function verifyWebhook(Request $request): array;
}
