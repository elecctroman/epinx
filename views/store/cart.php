<?php
$title = $title ?? 'Cart';
$items = $items ?? [];
$total = $total ?? 0.0;
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<section class="mb-4">
    <h1 class="h3 mb-3">Your cart</h1>
    <?php if ($success): ?>
        <div class="alert alert-success" role="status"><?= escape($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
    <?php endif; ?>
</section>

<?php if ($items): ?>
    <div class="table-responsive mb-4">
        <table class="table align-middle">
            <thead>
            <tr>
                <th scope="col">Product</th>
                <th scope="col">Variant</th>
                <th scope="col" class="text-end">Price</th>
                <th scope="col" class="text-end">Quantity</th>
                <th scope="col" class="text-end">Total</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <a class="text-decoration-none" href="<?= route('store.product', ['slug' => $item['product']['slug']]); ?>">
                            <?= escape($item['product']['name']); ?>
                        </a>
                    </td>
                    <td><?= escape($item['variant']['name'] ?? 'Standard'); ?></td>
                    <td class="text-end">$<?= number_format((float) $item['price'], 2); ?></td>
                    <td class="text-end">
                        <form action="<?= route('cart.update'); ?>" method="POST" class="d-inline-flex align-items-center gap-2">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?= (int) $item['product']['id']; ?>">
                            <input type="hidden" name="variant_id" value="<?= isset($item['variant']['id']) ? (int) $item['variant']['id'] : ''; ?>">
                            <label for="qty<?= (int) $item['product']['id']; ?>" class="visually-hidden">Quantity</label>
                            <input type="number" id="qty<?= (int) $item['product']['id']; ?>" name="quantity" value="<?= (int) $item['quantity']; ?>" min="0" class="form-control form-control-sm" style="width: 5rem;">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                        </form>
                    </td>
                    <td class="text-end">$<?= number_format((float) $item['line_total'], 2); ?></td>
                    <td class="text-end">
                        <form action="<?= route('cart.remove'); ?>" method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?= (int) $item['product']['id']; ?>">
                            <input type="hidden" name="variant_id" value="<?= isset($item['variant']['id']) ? (int) $item['variant']['id'] : ''; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <p class="mb-0">Need help? Visit our <a href="#">support center</a>.</p>
        </div>
        <div class="text-end">
            <p class="h5">Subtotal: $<?= number_format((float) $total, 2); ?></p>
            <a class="btn btn-primary btn-lg" href="<?= route('checkout.show'); ?>">Proceed to checkout</a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Your cart is empty. Browse the <a class="alert-link" href="<?= route('store.home'); ?>">store</a> to discover new products.</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
