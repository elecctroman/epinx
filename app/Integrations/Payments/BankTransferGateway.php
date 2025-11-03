<?php
declare(strict_types=1);

namespace App\Integrations\Payments;

use App\Core\Http\Request;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function createPayment(array $order, array $billing): array
    {
        return [
            'provider' => 'bank_transfer',
            'instructions' => 'Please complete the transfer via bank and notify support with receipt.',
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
            'status' => $request->input('status') === 'approved' ? 'paid' : 'pending',
        ];
    }
}
