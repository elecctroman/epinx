<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\CatalogService;
use App\Services\OrderService;
use App\Services\PaymentService;

class CheckoutController extends ControllerBase
{
    private CartService $cart;
    private CatalogService $catalog;
    private OrderService $orders;
    private PaymentService $payments;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->cart = $container->get(CartService::class);
        $this->catalog = $container->get(CatalogService::class);
        $this->orders = $container->get(OrderService::class);
        $this->payments = $container->get(PaymentService::class);
    }

    public function show(Request $request): Response
    {
        $cartDetails = $this->detailedCart();
        if (empty($cartDetails['items'])) {
            $this->flash('error', 'Your cart is empty.');
            return $this->redirect('/cart');
        }

        return $this->view('store/checkout', [
            'title' => 'Checkout',
            'items' => $cartDetails['items'],
            'total' => $cartDetails['total'],
            'providers' => Config::get('payments.providers', []),
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'cartQuantity' => $this->cart->totalQuantity(),
        ]);
    }

    public function process(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'address' => 'required|min:6',
            'city' => 'required|min:2',
            'country' => 'required|min:2',
            'payment_method' => 'required',
            'accept_terms' => 'required',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/checkout');
        }

        $cartDetails = $this->detailedCart();
        if (empty($cartDetails['items'])) {
            $this->flash('error', 'Your cart is empty.');
            return $this->redirect('/cart');
        }

        $deliveryInputs = array_filter([
            'player_id' => $data['player_id'] ?? null,
            'nickname' => $data['nickname'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');

        $provider = (string) ($data['payment_method'] ?? 'manual');

        try {
            $user = $this->auth->user();
            $order = $this->orders->createOrder($user ? (int) $user['id'] : null, $this->cart->items(), [
                'currency' => 'USD',
                'provider' => $provider,
                'billing' => $data,
                'delivery_inputs' => $deliveryInputs,
            ]);
        } catch (\Throwable $throwable) {
            $this->flash('error', 'Unable to create order: ' . $throwable->getMessage());
            return $this->redirect('/checkout');
        }

        try {
            $gateway = $this->payments->gateway($provider);
            $_SESSION['payment_payload'] = $gateway->createPayment([
                'id' => $order['id'],
                'reference' => $order['reference'],
                'total' => $order['total'],
                'currency' => $order['currency'],
                'email' => $data['email'],
            ], [
                'ip' => $request->ip(),
                'email' => $data['email'],
                'name' => $data['name'],
                'billing' => $data,
            ]);
        } catch (\Throwable $throwable) {
            $this->flash('error', 'Payment initialization failed: ' . $throwable->getMessage());
            return $this->redirect('/checkout');
        }

        $this->cart->clear();
        $_SESSION['last_order_reference'] = $order['reference'];

        return $this->redirect('/order/' . $order['reference'] . '/confirmation');
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: float}
     */
    private function detailedCart(): array
    {
        $items = $this->cart->items();
        $detailed = [];
        $total = 0.0;

        foreach ($items as $item) {
            $product = $this->catalog->findProduct($item['product_id']);
            if (!$product) {
                continue;
            }

            $variant = null;
            $price = (float) $product['price'];
            if ($item['variant_id']) {
                $variant = $this->catalog->findVariant($item['variant_id']);
                if ($variant && (int) $variant['product_id'] === (int) $product['id']) {
                    $price = (float) $variant['price'];
                } else {
                    continue;
                }
            }

            $lineTotal = $price * $item['quantity'];
            $total += $lineTotal;

            $detailed[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $item['quantity'],
                'price' => $price,
                'line_total' => $lineTotal,
            ];
        }

        return ['items' => $detailed, 'total' => $total];
    }
}
