<?php
$title = $title ?? 'Order detail';
$order = $order ?? [];
ob_start();
?>
<section class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Order #<?= escape($order['reference'] ?? ''); ?></h1>
        <p class="text-muted mb-0">Status: <?= escape($order['status'] ?? 'pending'); ?></p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="<?= route('admin.orders.resend', ['reference' => $order['reference']]); ?>" onsubmit="return confirm('Resend delivery for this order?');">
            <?= csrf_field(); ?>
            <button type="submit" class="btn btn-outline-primary">Resend delivery</button>
        </form>
    </div>
</section>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Items</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach (($order['items'] ?? []) as $item): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-semibold"><?= escape($item['name'] ?? 'Product'); ?></div>
                                    <div class="text-muted small"><?= escape($item['variant_name'] ?? 'Standard'); ?> · Qty <?= (int) ($item['quantity'] ?? 0); ?></div>
                                </div>
                                <span>$<?= number_format((float) ($item['price'] ?? 0), 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase"><?= escape($item['delivery_status'] ?? 'pending'); ?></span>
                                <?php if (($item['fulfillment_type'] ?? '') === 'topup'): ?>
                                    <form method="post" class="d-flex align-items-center gap-2" action="<?= route('admin.orders.topup', ['reference' => $order['reference']]); ?>">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id']; ?>">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="completed">Completed</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Update</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['delivery_json']['masked_codes'])): ?>
                                <pre class="mt-3 bg-dark text-white p-3 small"><?= escape(implode("\n", $item['delivery_json']['masked_codes'])); ?></pre>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($order['items'])): ?>
                        <li class="list-group-item text-muted">No items recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6">Billing</h2>
                <dl class="row small mb-0">
                    <dt class="col-5">Customer</dt>
                    <dd class="col-7"><?= $order['user_id'] ? 'User #' . (int) $order['user_id'] : 'Guest'; ?></dd>
                    <dt class="col-5">Total</dt>
                    <dd class="col-7">$<?= number_format((float) ($order['total'] ?? 0), 2); ?></dd>
                    <dt class="col-5">Status</dt>
                    <dd class="col-7"><?= escape($order['status'] ?? 'pending'); ?></dd>
                    <dt class="col-5">Created</dt>
                    <dd class="col-7"><?= escape(date('M j, Y H:i', strtotime((string) $order['created_at'] ?? 'now'))); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
