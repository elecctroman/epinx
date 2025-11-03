<?php
$title = $title ?? 'Search results';
$query = $query ?? '';
/** @var \App\Core\Support\Pagination $pagination */
$pagination = $pagination ?? new \App\Core\Support\Pagination([], 0, 12, 1);
$filters = $filters ?? [];
$error = $error ?? null;
ob_start();
?>
<section class="mb-4">
    <h1 class="h3 mb-3">Search results for "<?= escape($query); ?>"</h1>
    <form class="row g-3" method="GET" action="<?= route('store.search'); ?>" aria-label="Search again">
        <div class="col-md-6">
            <label for="searchAgain" class="form-label">Search term</label>
            <input type="search" id="searchAgain" name="q" class="form-control" value="<?= escape($query); ?>" required>
        </div>
        <div class="col-md-3">
            <label for="searchMinPrice" class="form-label">Min price</label>
            <input type="number" class="form-control" min="0" step="0.01" id="searchMinPrice" name="min_price" value="<?= escape($filters['min_price'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
            <label for="searchMaxPrice" class="form-label">Max price</label>
            <input type="number" class="form-control" min="0" step="0.01" id="searchMaxPrice" name="max_price" value="<?= escape($filters['max_price'] ?? ''); ?>">
        </div>
        <div class="col-md-3 form-check align-self-end">
            <input class="form-check-input" type="checkbox" value="1" id="searchInStock" name="in_stock" <?= !empty($filters['in_stock']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="searchInStock">In stock</label>
        </div>
        <div class="col-md-3">
            <label for="searchSort" class="form-label">Sort by</label>
            <select class="form-select" id="searchSort" name="sort">
                <option value="">Name (A-Z)</option>
                <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : ''; ?>>Price: Low to high</option>
                <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : ''; ?>>Price: High to low</option>
                <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : ''; ?>>Newest first</option>
            </select>
        </div>
        <div class="col-md-3 d-grid align-self-end">
            <button type="submit" class="btn btn-primary">Update search</button>
        </div>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-3" role="status"><?= escape($error); ?></div>
    <?php endif; ?>
</section>

<section class="row g-4" aria-label="Search results list">
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
            <div class="alert alert-info">No products found. Try adjusting your filters.</div>
        </div>
    <?php endif; ?>
</section>

<?php if ($pagination->lastPage() > 1): ?>
<nav class="mt-4" aria-label="Pagination">
    <ul class="pagination">
        <?php for ($page = 1; $page <= $pagination->lastPage(); $page++): ?>
            <?php $queryParams = array_merge($filters, ['q' => $query, 'page' => $page]); ?>
            <li class="page-item<?= $page === $pagination->currentPage() ? ' active' : ''; ?>">
                <a class="page-link" href="<?= route('store.search'); ?>?<?= http_build_query($queryParams); ?>"><?= $page; ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
