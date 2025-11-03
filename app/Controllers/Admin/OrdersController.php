<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ControllerBase;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\DeliveryService;
use App\Services\OrderService;
use PDO;

class OrdersController extends ControllerBase
{
    private PDO $pdo;
    private OrderService $orders;
    private DeliveryService $delivery;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->pdo = $container->get(Connection::class);
        $this->orders = $container->get(OrderService::class);
        $this->delivery = $container->get(DeliveryService::class);
    }

    public function index(Request $request): Response
    {
        $this->guard();
        $this->authorize('admin', 'staff');
        $statement = $this->pdo->query('SELECT id, reference, user_id, status, total, created_at FROM orders ORDER BY created_at DESC LIMIT 25');

        return $this->view('admin/orders/index', [
            'title' => 'Orders',
            'orders' => $statement->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    public function show(Request $request): Response
    {
        $this->guard();
        $this->authorize('admin', 'staff');
        $reference = (string) $request->route('reference');
        $order = $this->orders->findByReference($reference);
        if (!$order) {
            return $this->json(['message' => 'Order not found'], 404);
        }

        return $this->view('admin/orders/show', [
            'title' => 'Order #' . $reference,
            'order' => $order,
        ]);
    }

    public function resend(Request $request): Response
    {
        $this->guard();
        $this->authorize('admin', 'staff');
        $this->validateCsrf($request);
        $reference = (string) $request->route('reference');
        try {
            $this->delivery->handlePaidOrder($reference);
            $this->flash('success', 'Delivery retriggered successfully.');
        } catch (\Throwable $throwable) {
            $this->flash('error', $throwable->getMessage());
        }

        return $this->redirect('/admin/orders/' . $reference);
    }

    public function finalizeTopup(Request $request): Response
    {
        $this->guard();
        $this->authorize('admin', 'staff');
        $this->validateCsrf($request);
        $itemId = (int) $request->input('item_id');
        $status = (string) $request->input('status', 'completed');
        try {
            $this->delivery->finalizeTopup($itemId, $status, ['admin' => $this->auth->user()['email'] ?? 'admin']);
            $this->flash('success', 'Top-up status updated.');
        } catch (\Throwable $throwable) {
            $this->flash('error', $throwable->getMessage());
        }

        $reference = (string) $request->route('reference');
        return $this->redirect('/admin/orders/' . $reference);
    }
}
