<?php
/** @var string $title */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= escape($title ?? 'Epinx'); ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/css/app.css'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body data-bs-theme="light">
<a class="visually-hidden-focusable" href="#mainContent">Skip to main content</a>
<?php include __DIR__ . '/../partials/navbar.php'; ?>
<main class="container py-4" id="mainContent">
    <?= $content ?? ''; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" integrity="sha384-fId0Z78mVSXve+APQGgkDYevht3nqJ3C0cWPHQ66d0xYwjZZdqd6pnW9ELpsnkCn" crossorigin="anonymous"></script>
<script src="<?= asset_url('assets/js/app.js'); ?>"></script>
</body>
</html>
