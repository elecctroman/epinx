<?php
$title = $title ?? 'Admin Dashboard';
$user = $user ?? [];
$overview = $overview ?? ['totals' => ['orders' => 0, 'revenue' => 0], 'chart' => []];
$recentOrders = $recentOrders ?? [];
$lowStock = $lowStock ?? [];
$chartLabels = array_map(static fn ($row) => $row['date'], $overview['chart']);
$chartValues = array_map(static fn ($row) => (float) $row['total'], $overview['chart']);
ob_start();
?>
<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-1">Welcome back, <?= escape($user['name'] ?? 'Administrator'); ?></h1>
                <p class="text-muted mb-0">Monitor sales, stock health, and fulfillment from this control panel.</p>
            </div>
            <div class="text-end">
                <div class="fw-semibold">Total revenue (14d)</div>
                <div class="display-6 fw-bold">$<?= number_format((float) ($overview['totals']['revenue'] ?? 0), 2); ?></div>
                <div class="text-muted small">Orders: <?= (int) ($overview['totals']['orders'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Sales trend (14 days)</h2>
                    <span class="badge bg-secondary-subtle text-secondary">Live</span>
                </div>
                <canvas id="salesChart" height="160" role="img" aria-label="Sales chart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6">Recent orders</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentOrders as $orderRow): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">#<?= escape($orderRow['reference']); ?></div>
                                <div class="text-muted small"><?= escape(ucfirst($orderRow['status'])); ?> · <?= escape(date('M j, H:i', strtotime($orderRow['created_at']))); ?></div>
                            </div>
                            <span class="fw-semibold">$<?= number_format((float) $orderRow['total'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <li class="list-group-item text-muted">No orders yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Low stock variants</h2>
                <ul class="list-group list-group-flush">
                    <?php foreach ($lowStock as $variant): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold"><?= escape($variant['product_name']); ?></div>
                                <div class="text-muted small"><?= escape($variant['variant_name']); ?></div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger"><?= (int) $variant['available']; ?> left</span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($lowStock)): ?>
                        <li class="list-group-item text-muted">All variants healthy.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart');
        if (!ctx || typeof window.Chart === 'undefined') {
            const fallback = document.createElement('div');
            fallback.className = 'alert alert-warning mt-3';
            fallback.textContent = 'Chart.js is not available. Displaying textual summary instead.';
            ctx.replaceWith(fallback);
            return;
        }
        const chart = new window.Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels, JSON_THROW_ON_ERROR); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($chartValues, JSON_THROW_ON_ERROR); ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                plugins: {legend: {display: false}},
                scales: {
                    y: {beginAtZero: true}
                }
            }
        });
    });
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
