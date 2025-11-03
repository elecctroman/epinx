<?php
$title = $title ?? 'Reset password';
$error = $error ?? null;
$success = $success ?? null;
$token = $token ?? '';
$email = $email ?? '';
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
                <form method="POST" action="<?= route('password.update'); ?>" class="row g-3">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="token" value="<?= escape($token); ?>">
                    <div class="col-12">
                        <label for="resetEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="resetEmail" name="email" required value="<?= escape($email); ?>" autocomplete="email">
                    </div>
                    <div class="col-12">
                        <label for="newPassword" class="form-label">New password</label>
                        <input type="password" class="form-control" id="newPassword" name="password" required autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <label for="confirmPassword" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary">Reset password</button>
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
