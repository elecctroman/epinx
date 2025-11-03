<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;
use App\Services\CartService;
use App\Services\CatalogService;

class CartController extends ControllerBase
{
    private CartService $cart;
    private CatalogService $catalog;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->cart = $container->get(CartService::class);
        $this->catalog = $container->get(CatalogService::class);
    }

    public function show(Request $request): Response
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
                if ($variant) {
                    $price = (float) $variant['price'];
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

        return $this->view('store/cart', [
            'title' => 'Your cart',
            'items' => $detailed,
            'total' => $total,
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
            'cartQuantity' => $this->cart->totalQuantity(),
        ]);
    }

    public function add(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $errors = Validator::make($data, [
            'product_id' => 'required|numeric',
            'quantity' => 'required|numeric|min:1',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/cart');
        }

        $product = $this->catalog->findProduct((int) $data['product_id']);
        if (!$product) {
            $this->flash('error', 'Product not found.');
            return $this->redirect('/cart');
        }

        $variantId = isset($data['variant_id']) && $data['variant_id'] !== '' ? (int) $data['variant_id'] : null;
        if ($variantId !== null) {
            $variant = $this->catalog->findVariant($variantId);
            if (!$variant || (int) $variant['product_id'] !== (int) $product['id']) {
                $this->flash('error', 'Variant not found.');
                return $this->redirect('/cart');
            }
        }

        $this->cart->add((int) $data['product_id'], $variantId, (int) $data['quantity']);
        $this->flash('success', 'Item added to cart.');

        return $this->redirect('/cart');
    }

    public function update(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $errors = Validator::make($data, [
            'product_id' => 'required|numeric',
            'quantity' => 'required|numeric',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/cart');
        }

        $variantId = isset($data['variant_id']) && $data['variant_id'] !== '' ? (int) $data['variant_id'] : null;
        if ($variantId !== null) {
            $variant = $this->catalog->findVariant($variantId);
            if (!$variant || (int) $variant['product_id'] !== (int) $data['product_id']) {
                $this->flash('error', 'Variant not found.');
                return $this->redirect('/cart');
            }
        }
        $this->cart->update((int) $data['product_id'], $variantId, (int) $data['quantity']);
        $this->flash('success', 'Cart updated.');

        return $this->redirect('/cart');
    }

    public function remove(Request $request): Response
    {
        $this->validateCsrf($request);
        $productId = $request->input('product_id');
        if ($productId === null) {
            $this->flash('error', 'Invalid cart item.');
            return $this->redirect('/cart');
        }

        $variantId = $request->input('variant_id');
        $this->cart->remove((int) $productId, $variantId !== null && $variantId !== '' ? (int) $variantId : null);
        $this->flash('success', 'Item removed.');

        return $this->redirect('/cart');
    }
}
