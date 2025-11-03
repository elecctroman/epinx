<?php
declare(strict_types=1);

namespace App\Services;

class CartService
{
    private const SESSION_KEY = '_cart_items';

    /**
     * @return array<string, array{product_id:int, variant_id:int|null, quantity:int}>
     */
    public function items(): array
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function add(int $productId, ?int $variantId, int $quantity): void
    {
        $quantity = max(1, $quantity);
        $items = $this->items();
        $key = $this->key($productId, $variantId);

        if (isset($items[$key])) {
            $items[$key]['quantity'] += $quantity;
        } else {
            $items[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        $_SESSION[self::SESSION_KEY] = $items;
    }

    public function update(int $productId, ?int $variantId, int $quantity): void
    {
        $items = $this->items();
        $key = $this->key($productId, $variantId);

        if (!isset($items[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['quantity'] = $quantity;
        }

        $_SESSION[self::SESSION_KEY] = $items;
    }

    public function remove(int $productId, ?int $variantId): void
    {
        $items = $this->items();
        $key = $this->key($productId, $variantId);
        unset($items[$key]);

        $_SESSION[self::SESSION_KEY] = $items;
    }

    public function clear(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
    }

    public function totalQuantity(): int
    {
        return array_sum(array_column($this->items(), 'quantity'));
    }

    private function key(int $productId, ?int $variantId): string
    {
        return $productId . ':' . ($variantId ?? 'base');
    }
}
