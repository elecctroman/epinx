<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Support\Pagination;
use PDO;

class CatalogService
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(): array
    {
        $statement = $this->connection->query('SELECT c.*, (
            SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = "active"
        ) AS product_count FROM categories c ORDER BY c.name');

        return $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveBanners(): array
    {
        $statement = $this->connection->prepare('SELECT * FROM banners WHERE active = 1 ORDER BY updated_at DESC LIMIT 5');
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeaturedProducts(int $limit = 4): array
    {
        $statement = $this->connection->prepare('SELECT p.*, c.slug AS category_slug, c.name AS category_name,
            (SELECT MIN(price) FROM product_variants WHERE product_id = p.id) AS min_variant_price
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.status = "active"
        ORDER BY p.updated_at DESC
        LIMIT :limit');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductBySlug(string $slug): ?array
    {
        $statement = $this->connection->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p INNER JOIN categories c ON c.id = p.category_id
            WHERE p.slug = :slug AND p.status = "active" LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $product = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return null;
        }

        $variants = $this->connection->prepare('SELECT v.*, (
            SELECT COUNT(*) FROM stocks s WHERE s.product_variant_id = v.id AND s.status = "available"
        ) AS available_stock FROM product_variants v WHERE v.product_id = :product ORDER BY v.price');
        $variants->execute(['product' => $product['id']]);
        $product['variants'] = $variants->fetchAll(PDO::FETCH_ASSOC);

        $product['faqs'] = [];

        return $product;
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function getProductsByCategory(string $slug, array $filters, int $page = 1, int $perPage = 12): Pagination
    {
        $category = $this->getCategory($slug);
        if ($category === null) {
            return new Pagination([], 0, $perPage, $page);
        }

        [$conditions, $params] = $this->buildProductFilters($filters);
        $params['category_id'] = $category['id'];

        $where = $conditions ? ' AND ' . implode(' AND ', $conditions) : '';

        $count = $this->connection->prepare('SELECT COUNT(*) FROM products WHERE category_id = :category_id AND status = "active"' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $order = $this->resolveOrderBy($filters['sort'] ?? null);
        $query = $this->connection->prepare('SELECT * FROM products WHERE category_id = :category_id AND status = "active"' . $where . ' ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $query->bindValue(':' . $key, $value);
        }
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        $items = $query->fetchAll(PDO::FETCH_ASSOC);

        return new Pagination($items, $total, $perPage, $page);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function search(string $term, array $filters, int $page = 1, int $perPage = 12): Pagination
    {
        [$conditions, $params] = $this->buildProductFilters($filters);
        $params['term'] = '%' . $term . '%';

        $where = ' AND ('
            . 'name LIKE :term OR description LIKE :term'
            . ')';
        if ($conditions) {
            $where .= ' AND ' . implode(' AND ', $conditions);
        }

        $count = $this->connection->prepare('SELECT COUNT(*) FROM products WHERE status = "active"' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $order = $this->resolveOrderBy($filters['sort'] ?? null);
        $query = $this->connection->prepare('SELECT * FROM products WHERE status = "active"' . $where . ' ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) {
            $query->bindValue(':' . $key, $value);
        }
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        $items = $query->fetchAll(PDO::FETCH_ASSOC);

        return new Pagination($items, $total, $perPage, $page);
    }

    public function getCategory(string $slug): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $category = $statement->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }

    public function findProduct(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch(PDO::FETCH_ASSOC);

        return $product ?: null;
    }

    public function findVariant(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM product_variants WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $variant = $statement->fetch(PDO::FETCH_ASSOC);

        return $variant ?: null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function buildProductFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['min_price'])) {
            $conditions[] = 'price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $conditions[] = 'price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }

        if (!empty($filters['in_stock'])) {
            $conditions[] = 'EXISTS (SELECT 1 FROM product_variants pv INNER JOIN stocks s ON s.product_variant_id = pv.id WHERE pv.product_id = products.id AND s.status = "available")';
        }

        return [$conditions, $params];
    }

    private function resolveOrderBy(?string $sort): string
    {
        return match ($sort) {
            'price_asc' => 'price ASC',
            'price_desc' => 'price DESC',
            'newest' => 'updated_at DESC',
            default => 'name ASC',
        };
    }
}
