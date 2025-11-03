<?php
declare(strict_types=1);

namespace App\Integrations\Payments;

use App\Core\Http\Request;

class PaparaGateway implements PaymentGatewayInterface
{
    public function createPayment(array $order, array $billing): array
    {
        return [
            'provider' => 'papara',
            'instructions' => 'Transfer the total to our Papara account and include the order reference.',
            'reference' => $order['reference'],
        ];
    }

    public function status(string $transactionId): array
    {
        return [
            'transaction_id' => $transactionId,
            'status' => 'manual-review',
        ];
    }

    public function verifyWebhook(Request $request): array
    {
        return [
            'reference' => (string) $request->input('reference', ''),
            'status' => $request->input('status') === 'confirmed' ? 'paid' : 'pending',
        ];
    }
}
