<?php
/** @var string $title */
/** @var string $content */
use App\Core\Env;

$meta = $meta ?? [];
$appName = (string) Env::get('APP_NAME', 'Epinx');
$metaTitle = $meta['title'] ?? ($title ?? $appName);
$metaDescription = $meta['description'] ?? 'Secure digital marketplace delivering EPIN and top-up codes instantly.';
$canonical = $meta['canonical'] ?? app_url(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$metaImage = $meta['image'] ?? asset_url('assets/img/storefront.svg');
$schemaSnippets = $meta['schema'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?= escape($metaTitle); ?></title>
    <meta name="description" content="<?= escape($metaDescription); ?>">
    <link rel="canonical" href="<?= escape($canonical); ?>">
    <meta property="og:title" content="<?= escape($metaTitle); ?>">
    <meta property="og:description" content="<?= escape($metaDescription); ?>">
    <meta property="og:url" content="<?= escape($canonical); ?>">
    <meta property="og:site_name" content="<?= escape($appName); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= escape($metaImage); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= escape($metaTitle); ?>">
    <meta name="twitter:description" content="<?= escape($metaDescription); ?>">
    <meta name="twitter:image" content="<?= escape($metaImage); ?>">
    <?php foreach ((array) $schemaSnippets as $schema): ?>
        <script type="application/ld+json">
            <?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
        </script>
    <?php endforeach; ?>
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
