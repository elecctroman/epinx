<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\OrderService;
use App\Services\TwoFactorService;

class OrderController extends ControllerBase
{
    private OrderService $orders;
    private CartService $cart;
    private DeliveryService $delivery;
    private TwoFactorService $twoFactor;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->orders = $container->get(OrderService::class);
        $this->cart = $container->get(CartService::class);
        $this->delivery = $container->get(DeliveryService::class);
        $this->twoFactor = new TwoFactorService();
    }

    public function confirmation(Request $request): Response
    {
        $reference = (string) $request->route('reference', '');
        $order = $this->orders->findByReference($reference);
        if ($order === null) {
            return $this->json(['message' => 'Order not found'], 404);
        }

        $paymentPayload = $_SESSION['payment_payload'] ?? null;
        unset($_SESSION['payment_payload']);

        $canRefund = $this->delivery->allowRefund((int) $order['id']);

        return $this->view('store/order-confirmation', [
            'title' => 'Order confirmation',
            'order' => $order,
            'paymentPayload' => $paymentPayload,
            'canRefund' => $canRefund,
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
            'cartQuantity' => $this->cart->totalQuantity(),
        ]);
    }

    public function updateStatus(Request $request): Response
    {
        $reference = (string) $request->route('reference', '');
        $status = strtolower((string) $request->query('status', ''));
        $updated = false;

        if ($status === 'paid') {
            $updated = $this->orders->markPaid($reference);
            if ($updated) {
                try {
                    $this->delivery->handlePaidOrder($reference);
                    $this->flash('success', 'Payment confirmed. Your order will now be fulfilled.');
                } catch (\Throwable $throwable) {
                    $this->flash('error', 'Payment confirmed but fulfillment pending: ' . $throwable->getMessage());
                }
            }
        } elseif ($status === 'failed') {
            $updated = $this->orders->markFailed($reference);
            if ($updated) {
                $this->flash('error', 'Payment failed or was cancelled. Please try again.');
            }
        } elseif ($status === 'cancelled') {
            $updated = $this->orders->markCancelled($reference);
            if ($updated) {
                $this->flash('error', 'Order cancelled successfully.');
            }
        }

        if (!$updated) {
            $this->flash('error', 'Unable to update order status.');
        }

        return $this->redirect('/order/' . $reference . '/confirmation');
    }

    public function codes(Request $request): Response
    {
        $this->guard();
        $reference = (string) $request->route('reference', '');
        $order = $this->orders->findByReference($reference);
        $user = $this->auth->user();
        if ($order === null || !$user || (int) $order['user_id'] !== (int) $user['id']) {
            return $this->json(['message' => 'Order not accessible'], 404);
        }

        $revealed = $_SESSION['revealed_codes'][$order['id']] ?? false;

        return $this->view('store/order-codes', [
            'title' => 'Order codes',
            'order' => $order,
            'revealed' => $revealed,
            'requiresTwoFactor' => !empty($user['two_factor_secret']),
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
            'cartQuantity' => $this->cart->totalQuantity(),
        ]);
    }

    public function revealCodes(Request $request): Response
    {
        $this->guard();
        $this->validateCsrf($request);
        $reference = (string) $request->route('reference', '');
        $order = $this->orders->findByReference($reference);
        $user = $this->auth->user();
        if ($order === null || !$user || (int) $order['user_id'] !== (int) $user['id']) {
            return $this->json(['message' => 'Order not accessible'], 404);
        }

        if (empty($user['two_factor_secret'])) {
            $this->flash('error', 'Enable two-factor authentication to reveal codes.');
            return $this->redirect('/order/' . $reference . '/codes');
        }

        $code = (string) $request->input('two_factor_code', '');
        if ($code === '' || !$this->twoFactor->verify((string) $user['two_factor_secret'], $code)) {
            $this->flash('error', 'Invalid two-factor code.');
            return $this->redirect('/order/' . $reference . '/codes');
        }

        $_SESSION['revealed_codes'][$order['id']] = true;
        $this->flash('success', 'Codes unlocked successfully.');

        return $this->redirect('/order/' . $reference . '/codes');
    }

    public function refund(Request $request): Response
    {
        $this->guard();
        $this->validateCsrf($request);
        $reference = (string) $request->route('reference', '');
        $order = $this->orders->findByReference($reference);
        $user = $this->auth->user();
        if ($order === null || !$user || (int) $order['user_id'] !== (int) $user['id']) {
            return $this->json(['message' => 'Order not accessible'], 404);
        }

        try {
            $this->delivery->processRefund((int) $order['id'], (int) $user['id']);
            $this->flash('success', 'Your order has been refunded.');
        } catch (\Throwable $throwable) {
            $this->flash('error', $throwable->getMessage());
        }

        return $this->redirect('/order/' . $reference . '/confirmation');
    }
}
