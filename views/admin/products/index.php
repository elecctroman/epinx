<?php
$title = $title ?? 'Products';
$products = $products ?? [];
$categories = $categories ?? [];
ob_start();
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Products</h1>
                <p class="text-muted mb-0">Manage catalog availability, pricing, and fulfillment rules.</p>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Price</th>
                            <th scope="col">Fulfillment</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="fw-semibold"><?= escape($product['name']); ?></td>
                                <td><?= escape($product['category'] ?? ''); ?></td>
                                <td>$<?= number_format((float) $product['price'], 2); ?></td>
                                <td><?= escape(ucfirst($product['fulfillment_type'] ?? 'epin')); ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary text-uppercase small"><?= escape($product['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Add new product</h2>
                <form method="post" action="<?= route('admin.products.store'); ?>" class="vstack gap-3">
                    <?= csrf_field(); ?>
                    <div>
                        <label for="product_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="product_name" name="name" required>
                    </div>
                    <div>
                        <label for="product_slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="product_slug" name="slug" required>
                    </div>
                    <div>
                        <label for="product_category" class="form-label">Category</label>
                        <select class="form-select" id="product_category" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id']; ?>"><?= escape($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="product_price" class="form-label">Price</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="product_price" name="price" required>
                    </div>
                    <div>
                        <label for="product_fulfillment" class="form-label">Fulfillment type</label>
                        <select class="form-select" id="product_fulfillment" name="fulfillment_type">
                            <option value="epin">EPIN</option>
                            <option value="topup">Top-up</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div>
                        <label for="product_status" class="form-label">Status</label>
                        <select class="form-select" id="product_status" name="status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label for="product_description" class="form-label">Description</label>
                        <textarea class="form-control" id="product_description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Create product</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
