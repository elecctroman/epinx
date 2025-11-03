<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ControllerBase;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;

class DashboardController extends ControllerBase
{
    private PDO $pdo;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->pdo = $container->get(Connection::class);
    }

    public function index(Request $request): Response
    {
        $this->guard();
        $this->authorize('admin');

        $overview = $this->fetchOverview();
        $recentOrders = $this->fetchRecentOrders();
        $lowStock = $this->fetchLowStock();

        return $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'user' => $this->auth->user(),
            'overview' => $overview,
            'recentOrders' => $recentOrders,
            'lowStock' => $lowStock,
        ]);
    }

    private function fetchOverview(): array
    {
        $sales = $this->pdo->query('SELECT DATE(created_at) as date, SUM(total) as total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC')->fetchAll(PDO::FETCH_ASSOC);
        $totals = $this->pdo->query('SELECT COUNT(*) as orders, COALESCE(SUM(total),0) as revenue FROM orders WHERE status IN ("paid","processing","completed")')->fetch(PDO::FETCH_ASSOC) ?: ['orders' => 0, 'revenue' => 0];

        return [
            'chart' => $sales,
            'totals' => $totals,
        ];
    }

    private function fetchRecentOrders(): array
    {
        $statement = $this->pdo->query('SELECT reference, status, total, created_at FROM orders ORDER BY created_at DESC LIMIT 5');
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function fetchLowStock(): array
    {
        $statement = $this->pdo->query('SELECT pv.name AS variant_name, p.name AS product_name, COUNT(s.id) AS available FROM product_variants pv INNER JOIN products p ON p.id = pv.product_id LEFT JOIN stocks s ON s.product_variant_id = pv.id AND s.status = "available" GROUP BY pv.id, pv.name, p.name HAVING available < 5 ORDER BY available ASC LIMIT 5');
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
