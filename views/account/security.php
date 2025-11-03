<?php
$title = $title ?? 'Account Security';
$user = $user ?? [];
$pending = $pending ?? null;
$success = $success ?? null;
$error = $error ?? null;
$hasTwoFactor = !empty($user['two_factor_secret']);
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h1 class="h3 mb-3">Secure your account</h1>
                <p class="mb-0">Enable two-factor authentication (2FA) to add an extra verification step whenever you sign in.</p>
            </div>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success" role="status"><?= escape($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
        <?php endif; ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Two-factor authentication</h2>
                <?php if ($hasTwoFactor): ?>
                    <p>Two-factor authentication is currently <strong>enabled</strong> on your account. Use your authenticator app to generate codes.</p>
                    <form method="POST" action="<?= route('account.security.disable'); ?>" class="row g-3">
                        <?= csrf_field(); ?>
                        <div class="col-md-6">
                            <label for="disable_two_factor_code" class="form-label">Enter current 2FA code</label>
                            <input type="text" name="two_factor_code" id="disable_two_factor_code" class="form-control" pattern="\d{6}" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger">Disable 2FA</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="mb-3">Your account does not yet use two-factor authentication.</p>
                    <?php if ($pending): ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <p class="mb-2">Scan this QR code with your preferred authenticator app (Google Authenticator, Authy, etc.). If you cannot scan the code, manually enter the secret below.</p>
                            <div class="text-center mb-3">
                                <img src="<?= escape($pending['qr']); ?>" alt="Two-factor QR code" width="200" height="200">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manual secret</label>
                                <input type="text" class="form-control" readonly value="<?= escape($pending['secret']); ?>" aria-describedby="twoFactorHelp">
                                <div id="twoFactorHelp" class="form-text">Enter this secret in your authenticator if you are unable to scan the QR code.</div>
                            </div>
                            <form method="POST" action="<?= route('account.security.confirm'); ?>" class="row g-3">
                                <?= csrf_field(); ?>
                                <div class="col-md-6">
                                    <label for="confirm_two_factor_code" class="form-label">Authenticator code</label>
                                    <input type="text" name="two_factor_code" id="confirm_two_factor_code" class="form-control" pattern="\d{6}" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Confirm &amp; enable 2FA</button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?= route('account.security.begin'); ?>">
                            <?= csrf_field(); ?>
                            <button type="submit" class="btn btn-primary">Set up two-factor authentication</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
