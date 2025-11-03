<?php
$title = $title ?? 'Forgot password';
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
                <p class="text-muted">Enter your email address and we'll send you a secure link to reset your password.</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="status"><?= escape($success); ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= route('password.email'); ?>" class="row g-3">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <label for="resetEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="resetEmail" name="email" required autocomplete="email">
                    </div>
                    <?php include __DIR__ . '/../partials/recaptcha.php'; ?>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary">Send reset link</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <a href="<?= route('login'); ?>">Back to login</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
