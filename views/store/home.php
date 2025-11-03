<?php
$title = $title ?? 'Welcome';
$categories = $categories ?? [];
$featured = $featured ?? [];
$banners = $banners ?? [];
$cartQuantity = $cartQuantity ?? 0;
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= escape($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
<?php endif; ?>
<section class="hero bg-gradient p-5 rounded-4 mb-4" aria-labelledby="heroTitle">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-4">
        <div class="flex-fill">
            <h1 class="display-5 fw-bold text-body-emphasis" id="heroTitle">Discover your next digital good</h1>
            <p class="lead">From instant game codes to premium gift cards, explore curated selections with trusted delivery.</p>
            <form class="row g-2" action="<?= route('store.search'); ?>" method="GET" role="search" aria-label="Search products">
                <div class="col-md-8">
                    <label class="visually-hidden" for="searchInput">Search products</label>
                    <input type="search" name="q" id="searchInput" class="form-control form-control-lg" placeholder="Search for products" required>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Search store</button>
                </div>
            </form>
        </div>
        <div class="text-center">
            <img src="<?= asset_url('assets/img/storefront.svg'); ?>" alt="Digital storefront illustration" class="img-fluid" style="max-width: 260px;" loading="lazy">
        </div>
    </div>
</section>

<?php if ($banners): ?>
<section class="mb-5" aria-label="Promotions">
    <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner rounded-4 shadow-sm">
            <?php foreach ($banners as $index => $banner): ?>
                <div class="carousel-item<?= $index === 0 ? ' active' : ''; ?>">
                    <img src="<?= escape($banner['image_path']); ?>" class="d-block w-100" alt="<?= escape($banner['title']); ?>" loading="lazy">
                    <?php if (!empty($banner['title'])): ?>
                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded-3">
                            <h2 class="h5 mb-0"><?= escape($banner['title']); ?></h2>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev" aria-label="Previous promotion">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next" aria-label="Next promotion">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
    </div>
</section>
<?php endif; ?>

<section class="mb-5" aria-labelledby="categoryHeading">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0" id="categoryHeading">Browse categories</h2>
        <?php if (!empty($categories)): ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= route('store.category', ['slug' => $categories[0]['slug']]); ?>">View all</a>
        <?php endif; ?>
    </div>
    <div class="row g-3">
        <?php foreach ($categories as $category): ?>
            <div class="col-sm-6 col-lg-3">
                <a class="card h-100 shadow-sm text-decoration-none" href="<?= route('store.category', ['slug' => $category['slug']]); ?>">
                    <div class="card-body">
                        <h3 class="h5 text-body-emphasis"><?= escape($category['name']); ?></h3>
                        <p class="text-muted mb-0 small"><?= escape((string) ($category['product_count'] ?? 0)); ?> items available</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-5" aria-labelledby="featuredHeading">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0" id="featuredHeading">Featured products</h2>
        <a class="btn btn-outline-primary btn-sm" href="<?= route('store.search'); ?>?sort=newest">See more</a>
    </div>
    <div class="row g-4">
        <?php foreach ($featured as $product): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h3 class="h5 flex-grow-1"><a class="text-decoration-none" href="<?= route('store.product', ['slug' => $product['slug']]); ?>"><?= escape($product['name']); ?></a></h3>
                        <p class="text-muted small mb-2">Category: <?= escape($product['category_name'] ?? ''); ?></p>
                        <p class="fw-semibold mb-4">Starting at $<?= number_format((float) ($product['min_variant_price'] ?? $product['price']), 2); ?></p>
                        <a class="btn btn-primary mt-auto" href="<?= route('store.product', ['slug' => $product['slug']]); ?>">View details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$featured): ?>
            <div class="col-12">
                <div class="alert alert-info">No featured products are currently available. Check back soon!</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="rounded-4 p-4 bg-body-secondary">
    <div class="row align-items-center g-3">
        <div class="col-md-8">
            <h2 class="h4">Prefer dark mode?</h2>
            <p class="mb-0">Use the toggle in the navigation bar to switch between light and dark themes for comfortable browsing at any time of day.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a class="btn btn-outline-dark" href="#" onclick="document.getElementById('themeToggle').click(); return false;">Toggle theme</a>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
