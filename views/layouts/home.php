<?php
$title = $title ?? 'Welcome';
ob_start();
?>
<div class="p-5 mb-4 bg-light rounded-3">
    <div class="container-fluid py-5">
        <h1 class="display-5 fw-bold"><?= escape($title); ?></h1>
        <p class="col-md-8 fs-4">Build and manage your digital goods storefront with confidence.</p>
        <a class="btn btn-primary btn-lg" href="/store">Browse Store</a>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/app.php';
