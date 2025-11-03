<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\PaymentService;
use App\Services\WebhookService;

class WebhookController extends ControllerBase
{
    private PaymentService $payments;
    private WebhookService $webhooks;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->payments = $container->get(PaymentService::class);
        $this->webhooks = $container->get(WebhookService::class);
    }

    public function paytr(Request $request): Response
    {
        return $this->handleGateway('paytr', $request);
    }

    public function iyzico(Request $request): Response
    {
        return $this->handleGateway('iyzico', $request);
    }

    public function param(Request $request): Response
    {
        return $this->handleGateway('param', $request);
    }

    public function papara(Request $request): Response
    {
        return $this->handleGateway('papara', $request);
    }

    public function bankTransfer(Request $request): Response
    {
        return $this->handleGateway('bank_transfer', $request);
    }

    private function handleGateway(string $provider, Request $request): Response
    {
        try {
            $gateway = $this->payments->gateway($provider);
            $payload = $gateway->verifyWebhook($request);
        } catch (\Throwable $throwable) {
            $webhookId = $this->webhooks->store($provider, $request->all(), ['error' => $throwable->getMessage()]);
            $this->webhooks->markFailed($webhookId, $throwable->getMessage());
            return $this->json(['success' => false, 'error' => $throwable->getMessage()], 400);
        }

        $reference = (string) ($payload['reference'] ?? '');

        if ($reference === '') {
            return $this->json(['success' => false, 'error' => 'Missing reference'], 422);
        }

        $webhookId = $this->webhooks->store($provider, $request->all(), $payload);
        $this->webhooks->enqueueProcessing($webhookId);

        return $this->json(['success' => true, 'queued' => true, 'id' => $webhookId], 202);
    }
}
