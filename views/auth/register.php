<?php
$title = $title ?? 'Register';
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
                <form method="POST" action="<?= route('register.submit'); ?>" class="row g-3">
                    <?= csrf_field(); ?>
                    <div class="col-12">
                        <label for="name" class="form-label">Full name</label>
                        <input type="text" name="name" id="name" class="form-control" required autocomplete="name">
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required autocomplete="email">
                    </div>
                    <div class="col-12">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>
                    <?php include __DIR__ . '/../partials/recaptcha.php'; ?>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary">Create account</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <a href="<?= route('login'); ?>">Already have an account? Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
