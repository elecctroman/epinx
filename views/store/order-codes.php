<?php
$title = $title ?? 'Order codes';
$order = $order ?? [];
$revealed = $revealed ?? false;
$requiresTwoFactor = $requiresTwoFactor ?? false;
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<section class="mb-4">
    <a href="<?= route('order.confirmation', ['reference' => $order['reference'] ?? '']); ?>" class="btn btn-link ps-0">&larr; Back to order</a>
    <h1 class="h3">Digital delivery</h1>
    <p class="text-muted">Order #<?= escape($order['reference'] ?? ''); ?></p>
    <?php if ($success): ?>
        <div class="alert alert-success" role="status"><?= escape($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
    <?php endif; ?>
</section>
<section class="row g-4">
    <div class="col-lg-8">
        <?php if (empty($order['items'])): ?>
            <div class="alert alert-info" role="status">No items found for this order.</div>
        <?php endif; ?>
        <?php foreach (($order['items'] ?? []) as $item): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-1"><?= escape($item['name'] ?? 'Item'); ?></h2>
                    <p class="text-muted small mb-3">Delivery status: <?= escape($item['delivery_status'] ?? 'pending'); ?></p>
                    <?php if (($item['delivery_status'] ?? '') === 'delivered'): ?>
                        <?php $codes = $item['delivery_json']['codes'] ?? []; ?>
                        <?php $masked = $item['delivery_json']['masked_codes'] ?? []; ?>
                        <?php if (!empty($codes)): ?>
                            <div class="mb-3">
                                <h3 class="h6">Codes</h3>
                                <pre class="bg-dark text-white p-3 rounded" aria-live="polite"><?= escape(implode("\n", $revealed ? $codes : $masked)); ?></pre>
                                <?php if (!$revealed && $requiresTwoFactor): ?>
                                    <p class="small text-muted mb-0">Codes are masked until you confirm with your two-factor authentication code.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif (($item['delivery_status'] ?? '') === 'processing'): ?>
                        <p class="mb-0">Top-up is being processed with the supplier. We will notify you via email once completed.</p>
                    <?php else: ?>
                        <p class="mb-0">Delivery will be available once payment is confirmed.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Security</h2>
                <?php if ($revealed || !$requiresTwoFactor): ?>
                    <p class="mb-0">Codes are fully visible. Keep them private and store them securely.</p>
                <?php else: ?>
                    <form method="post" action="<?= route('order.codes.reveal', ['reference' => $order['reference'] ?? '']); ?>" class="vstack gap-3">
                        <?= csrf_field(); ?>
                        <div>
                            <label for="two_factor_code" class="form-label">Two-factor code</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control" id="two_factor_code" name="two_factor_code" required>
                            <div class="form-text">Enter the 6-digit code from your authenticator app.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Reveal codes</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
