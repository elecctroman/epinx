<?php
$title = $title ?? 'Checkout';
$items = $items ?? [];
$total = $total ?? 0.0;
$providers = $providers ?? [];
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<div class="row g-5">
    <div class="col-lg-7">
        <h1 class="h3 mb-4">Checkout</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success" role="status"><?= escape($success); ?></div>
        <?php endif; ?>
        <form action="<?= route('checkout.process'); ?>" method="POST" class="row g-3 needs-validation" novalidate>
            <?= csrf_field(); ?>
            <div class="col-md-6">
                <label for="billingName" class="form-label">Full name</label>
                <input type="text" class="form-control" id="billingName" name="name" required>
            </div>
            <div class="col-md-6">
                <label for="billingEmail" class="form-label">Email</label>
                <input type="email" class="form-control" id="billingEmail" name="email" required>
            </div>
            <div class="col-12">
                <label for="billingAddress" class="form-label">Billing address</label>
                <input type="text" class="form-control" id="billingAddress" name="address" required>
            </div>
            <div class="col-md-6">
                <label for="billingCity" class="form-label">City</label>
                <input type="text" class="form-control" id="billingCity" name="city" required>
            </div>
            <div class="col-md-6">
                <label for="billingCountry" class="form-label">Country</label>
                <input type="text" class="form-control" id="billingCountry" name="country" required>
            </div>
            <div class="col-md-6">
                <label for="playerId" class="form-label">Game ID / Account</label>
                <input type="text" class="form-control" id="playerId" name="player_id" placeholder="Optional for top-up products">
            </div>
            <div class="col-md-6">
                <label for="nickname" class="form-label">Nickname</label>
                <input type="text" class="form-control" id="nickname" name="nickname" placeholder="Optional">
            </div>
            <div class="col-12">
                <label for="payment_method" class="form-label">Payment method</label>
                <select class="form-select" id="payment_method" name="payment_method" required>
                    <option value="" selected disabled>Choose a payment provider</option>
                    <?php foreach ($providers as $key => $provider): ?>
                        <option value="<?= escape($key); ?>"><?= escape($provider['name'] ?? ucfirst($key)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="accept_terms" name="accept_terms" required>
                    <label class="form-check-label" for="accept_terms">I agree to the Terms, KVKK policy, and distance sales contract.</label>
                </div>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="accept_marketing" name="accept_marketing">
                    <label class="form-check-label" for="accept_marketing">Keep me informed about new digital products.</label>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-lg">Place order</button>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h4">Order summary</h2>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold"><?= escape($item['product']['name']); ?></div>
                                <div class="text-muted small"><?= escape($item['variant']['name'] ?? 'Standard'); ?> × <?= (int) $item['quantity']; ?></div>
                            </div>
                            <span>$<?= number_format((float) $item['line_total'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold">Subtotal</span>
                    <span>$<?= number_format((float) $total, 2); ?></span>
                </div>
                <div class="d-flex justify-content-between text-muted small">
                    <span>Payment provider</span>
                    <span>Select at checkout</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between h5">
                    <span>Total due</span>
                    <span>$<?= number_format((float) $total, 2); ?></span>
                </div>
                <p class="text-muted small mt-3">After placing your order you'll receive payment instructions based on the selected provider.</p>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
