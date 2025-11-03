<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ControllerBase;
use App\Core\Database\Connection;
use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;

class ProductsController extends ControllerBase
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
        $products = $this->pdo->query('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.created_at DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
        $categories = $this->pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

        return $this->view('admin/products/index', [
            'title' => 'Products',
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->guard();
        $this->authorize('admin');
        $this->validateCsrf($request);
        $data = [
            'name' => trim((string) $request->input('name')),
            'slug' => trim((string) $request->input('slug')),
            'category_id' => (int) $request->input('category_id'),
            'price' => (float) $request->input('price'),
            'fulfillment_type' => (string) $request->input('fulfillment_type', 'epin'),
            'status' => (string) $request->input('status', 'draft'),
        ];
        $statement = $this->pdo->prepare('INSERT INTO products (category_id, name, slug, description, price, status, fulfillment_type, created_at, updated_at) VALUES (:category_id, :name, :slug, :description, :price, :status, :fulfillment_type, NOW(), NOW())');
        $statement->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $request->input('description', ''),
            'price' => $data['price'],
            'status' => $data['status'],
            'fulfillment_type' => $data['fulfillment_type'],
        ]);

        $this->flash('success', 'Product created successfully.');
        return $this->redirect('/admin/products');
    }
}
