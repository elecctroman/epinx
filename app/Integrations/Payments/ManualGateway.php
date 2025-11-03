<?php
declare(strict_types=1);

namespace App\Integrations\Payments;

use App\Core\Http\Request;

class ManualGateway implements PaymentGatewayInterface
{
    public function createPayment(array $order, array $billing): array
    {
        return [
            'provider' => 'manual',
            'instructions' => 'Please contact support to confirm your manual payment.',
            'reference' => $order['reference'],
        ];
    }

    public function status(string $transactionId): array
    {
        return [
            'transaction_id' => $transactionId,
            'status' => 'pending',
        ];
    }

    public function verifyWebhook(Request $request): array
    {
        return [
            'reference' => (string) $request->input('reference', ''),
            'status' => 'pending',
        ];
    }
}
