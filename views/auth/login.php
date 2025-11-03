<?php
$title = $title ?? 'Login';
$error = $error ?? null;
$success = $success ?? null;
$recaptcha = $recaptcha ?? ['enabled' => false];
ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title mb-4 text-center"><?= escape($title); ?></h2>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="status"><?= escape($success); ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= route('login.submit'); ?>" class="row g-3">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                    </div>
                    <div class="col-12">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="col-12">
                        <label for="two_factor_code" class="form-label">2FA code <small class="text-muted">(if enabled)</small></label>
                        <input type="text" name="two_factor_code" id="two_factor_code" class="form-control" pattern="\d{6}" inputmode="numeric">
                    </div>
                    <?php include __DIR__ . '/../partials/recaptcha.php'; ?>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <a href="<?= route('password.forgot'); ?>" class="d-block">Forgot password?</a>
                    <a href="<?= route('register'); ?>" class="d-block">Need an account? Register</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
