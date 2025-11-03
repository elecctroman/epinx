<?php
$title = $title ?? 'Category';
$category = $category ?? null;
/** @var \App\Core\Support\Pagination $pagination */
$pagination = $pagination ?? new \App\Core\Support\Pagination([], 0, 12, 1);
$filters = $filters ?? [];
$categorySlug = $categorySlug ?? '';
ob_start();
?>
<div class="row g-4">
    <div class="col-lg-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Filter products</h2>
                <form action="<?= route('store.category', ['slug' => $categorySlug]); ?>" method="GET" class="row g-3" aria-label="Category filters">
                    <div class="col-12">
                        <label for="min_price" class="form-label">Min price</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="min_price" name="min_price" value="<?= escape($filters['min_price'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="max_price" class="form-label">Max price</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="max_price" name="max_price" value="<?= escape($filters['max_price'] ?? ''); ?>">
                    </div>
                    <div class="col-12 form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="in_stock" name="in_stock" <?= !empty($filters['in_stock']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="in_stock">In stock only</label>
                    </div>
                    <div class="col-12">
                        <label for="sort" class="form-label">Sort by</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="">Name (A-Z)</option>
                            <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : ''; ?>>Price: Low to high</option>
                            <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : ''; ?>>Price: High to low</option>
                            <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                        </select>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0"><?= escape($category['name'] ?? 'Products'); ?></h1>
            <span class="text-muted">Total <?= $pagination->total(); ?> items</span>
        </div>
        <div class="row g-4">
            <?php foreach ($pagination->items() as $product): ?>
                <?php $updatedAt = isset($product['updated_at']) ? strtotime((string) $product['updated_at']) : false; ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 flex-grow-1"><a class="text-decoration-none" href="<?= route('store.product', ['slug' => $product['slug']]); ?>"><?= escape($product['name']); ?></a></h2>
                            <p class="text-muted small mb-2">Updated <?= $updatedAt ? escape(date('M j, Y', $updatedAt)) : 'Recently'; ?></p>
                            <p class="fw-semibold mb-4">$<?= number_format((float) $product['price'], 2); ?></p>
                            <a class="btn btn-outline-primary mt-auto" href="<?= route('store.product', ['slug' => $product['slug']]); ?>">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$pagination->items()): ?>
                <div class="col-12">
                    <div class="alert alert-info">No products matched your filters.</div>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($pagination->lastPage() > 1): ?>
            <nav class="mt-4" aria-label="Pagination">
                <ul class="pagination">
                    <?php for ($page = 1; $page <= $pagination->lastPage(); $page++): ?>
                        <?php $query = array_merge($filters, ['page' => $page]); ?>
                        <li class="page-item<?= $page === $pagination->currentPage() ? ' active' : ''; ?>">
                            <a class="page-link" href="<?= route('store.category', ['slug' => $categorySlug]); ?>?<?= http_build_query($query); ?>"><?= $page; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
