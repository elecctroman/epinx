<?php
$title = $title ?? 'Product';
$product = $product ?? [];
$variants = $product['variants'] ?? [];
$success = $success ?? null;
$error = $error ?? null;
ob_start();
?>
<div class="row g-5">
    <div class="col-lg-7">
        <h1 class="display-6 mb-3"><?= escape($product['name'] ?? 'Product'); ?></h1>
        <p class="text-muted mb-4">Category: <?= escape($product['category_name'] ?? ''); ?></p>
        <div class="mb-4">
            <h2 class="h5">Description</h2>
            <p><?= nl2br(escape($product['description'] ?? 'No description provided.')); ?></p>
        </div>
        <div class="mb-4">
            <h2 class="h5">Why you'll love it</h2>
            <ul class="list-unstyled ms-3">
                <li>✔ Instant digital delivery with secure storage</li>
                <li>✔ Trusted suppliers with audit trails</li>
                <li>✔ Flexible variants for every need</li>
            </ul>
        </div>
        <section aria-labelledby="faqHeading">
            <h2 class="h5" id="faqHeading">Frequently asked questions</h2>
            <div class="accordion" id="faqAccordion">
                <?php if (!empty($product['faqs'])): ?>
                    <?php foreach ($product['faqs'] as $index => $faq): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="faqHeading<?= $index; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= $index; ?>" aria-expanded="false" aria-controls="faqCollapse<?= $index; ?>">
                                    <?= escape($faq['question']); ?>
                                </button>
                            </h3>
                            <div id="faqCollapse<?= $index; ?>" class="accordion-collapse collapse" aria-labelledby="faqHeading<?= $index; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body"><?= nl2br(escape($faq['answer'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No FAQs yet. Reach out to support if you have questions.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm sticky-top" style="top: 5rem;">
            <div class="card-body">
                <h2 class="h4">Purchase options</h2>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="status"><?= escape($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="status"><?= escape($error); ?></div>
                <?php endif; ?>
                <form action="<?= route('cart.add'); ?>" method="POST" class="row g-3">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?= escape((string) ($product['id'] ?? '')); ?>">
                    <?php if ($variants): ?>
                        <div class="col-12">
                            <label class="form-label">Choose variant</label>
                            <?php foreach ($variants as $index => $variant): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="variant_id" id="variant<?= (int) $variant['id']; ?>" value="<?= (int) $variant['id']; ?>" <?= $index === 0 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="variant<?= (int) $variant['id']; ?>">
                                        <?= escape($variant['name']); ?> — $<?= number_format((float) $variant['price'], 2); ?>
                                        <span class="d-block text-muted small">Available codes: <?= (int) ($variant['available_stock'] ?? 0); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="fw-semibold h4">$<?= number_format((float) ($product['price'] ?? 0), 2); ?></p>
                    <?php endif; ?>
                    <div class="col-12">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" min="1" value="1" id="quantity" name="quantity" required>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Add to cart</button>
                    </div>
                </form>
                <hr class="my-4">
                <h3 class="h6">Delivery details</h3>
                <ul class="list-unstyled ms-3">
                    <li>⚡ Instant email delivery</li>
                    <li>🔒 Encrypted code vault</li>
                    <li>🎧 24/7 ticket-based support</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
