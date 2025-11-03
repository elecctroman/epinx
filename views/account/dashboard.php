<?php
$title = $title ?? 'My Account';
$user = $user ?? [];
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h3 mb-3">Welcome back, <?= escape($user['name'] ?? 'Customer'); ?>!</h1>
                <p class="mb-0">Review recent activity, manage your personal details, and keep your account secure.</p>
            </div>
        </div>
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5">Quick Links</h2>
                <ul class="list-unstyled mb-0">
                    <li><a class="link-primary" href="<?= route('account.security'); ?>">Account security &amp; two-factor authentication</a></li>
                    <li><a class="link-primary" href="<?= route('store.home'); ?>">Browse the latest products</a></li>
                    <li><a class="link-primary" href="<?= route('cart.view'); ?>">View your cart</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <?php if ($success): ?>
            <div class="alert alert-success" role="status"><?= escape($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted">Account Overview</h2>
                <dl class="row mb-0">
                    <dt class="col-6">Email</dt>
                    <dd class="col-6 text-break"><?= escape($user['email'] ?? ''); ?></dd>
                    <dt class="col-6">Role</dt>
                    <dd class="col-6 text-break"><?= escape($user['roles'] ?? 'customer'); ?></dd>
                    <dt class="col-6">2FA</dt>
                    <dd class="col-6 text-break">
                        <?= !empty($user['two_factor_secret']) ? 'Enabled' : 'Disabled'; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
