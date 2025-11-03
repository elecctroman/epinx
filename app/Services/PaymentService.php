<?php
declare(strict_types=1);

namespace App\Services;

use App\Integrations\Payments\BankTransferGateway;
use App\Integrations\Payments\IyzicoGateway;
use App\Integrations\Payments\ManualGateway;
use App\Integrations\Payments\PaparaGateway;
use App\Integrations\Payments\ParamGateway;
use App\Integrations\Payments\PayTRGateway;
use App\Integrations\Payments\PaymentGatewayInterface;
use RuntimeException;

class PaymentService
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways;

    public function __construct()
    {
        $this->gateways = [
            'manual' => new ManualGateway(),
            'paytr' => new PayTRGateway(),
            'iyzico' => new IyzicoGateway(),
            'param' => new ParamGateway(),
            'papara' => new PaparaGateway(),
            'bank_transfer' => new BankTransferGateway(),
        ];
    }

    public function gateway(string $provider): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$provider])) {
            throw new RuntimeException('Unsupported payment provider: ' . $provider);
        }

        return $this->gateways[$provider];
    }
}
