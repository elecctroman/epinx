<?php
$title = $title ?? 'Orders';
$orders = $orders ?? [];
ob_start();
?>
<section class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Orders</h1>
        <p class="text-muted mb-0">Track order lifecycle and resend deliveries when required.</p>
    </div>
</section>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Reference</th>
                    <th scope="col">Status</th>
                    <th scope="col">Total</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Created</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="fw-semibold">#<?= escape($order['reference']); ?></td>
                        <td><span class="badge bg-secondary-subtle text-secondary text-uppercase small"><?= escape($order['status']); ?></span></td>
                        <td>$<?= number_format((float) $order['total'], 2); ?></td>
                        <td><?= $order['user_id'] ? 'User #' . (int) $order['user_id'] : 'Guest'; ?></td>
                        <td><?= escape(date('M j, Y H:i', strtotime((string) $order['created_at']))); ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/admin/orders/<?= escape($order['reference']); ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
