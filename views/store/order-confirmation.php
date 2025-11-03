<?php
$title = $title ?? 'Order confirmation';
$order = $order ?? [];
$paymentPayload = $paymentPayload ?? null;
$canRefund = $canRefund ?? false;
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<section class="mb-4">
    <h1 class="h3 mb-3">Order #<?= escape($order['reference'] ?? ''); ?></h1>
    <p class="text-muted">Status: <strong><?= escape($order['status'] ?? 'pending'); ?></strong></p>
    <?php if ($success): ?>
        <div class="alert alert-success" role="status"><?= escape($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
    <?php endif; ?>
</section>
<section class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Items</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach (($order['items'] ?? []) as $item): ?>
                        <?php $lineTotal = (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0); ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold"><?= escape($item['name'] ?? ''); ?></div>
                                <div class="text-muted small"><?= escape($item['variant_name'] ?? 'Standard'); ?> × <?= (int) ($item['quantity'] ?? 0); ?></div>
                            </div>
                            <span>$<?= number_format($lineTotal, 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Next steps</h2>
                <p class="mb-3">Manage your payment and digital delivery from here.</p>
                <div class="d-grid gap-2 mb-3">
                    <a class="btn btn-primary" href="<?= route('order.codes', ['reference' => $order['reference']]); ?>">View delivery</a>
                    <a class="btn btn-success" href="<?= route('order.status', ['reference' => $order['reference']]); ?>?status=paid">Mark as paid (simulate)</a>
                    <a class="btn btn-outline-danger" href="<?= route('order.status', ['reference' => $order['reference']]); ?>?status=failed">Mark as failed</a>
                </div>
                <?php if ($canRefund): ?>
                    <form method="post" action="<?= route('order.refund', ['reference' => $order['reference']]); ?>" class="mb-3">
                        <?= csrf_field(); ?>
                        <button type="submit" class="btn btn-outline-secondary w-100">Request refund</button>
                    </form>
                <?php endif; ?>
                <?php if ($paymentPayload): ?>
                    <div class="border rounded p-3 bg-light">
                        <h3 class="h6">Payment payload</h3>
                        <pre class="small mb-0"><?= escape(json_encode($paymentPayload, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                <?php endif; ?>
                <hr>
                <dl class="row small mb-0">
                    <dt class="col-6">Total</dt>
                    <dd class="col-6 text-end">$<?= number_format((float) ($order['total'] ?? 0), 2); ?></dd>
                    <dt class="col-6">Currency</dt>
                    <dd class="col-6 text-end"><?= escape($order['currency'] ?? 'USD'); ?></dd>
                    <dt class="col-6">Created</dt>
                    <dd class="col-6 text-end"><?= escape(isset($order['created_at']) ? date('M j, Y H:i', strtotime((string) $order['created_at'])) : ''); ?></dd>
                </dl>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
