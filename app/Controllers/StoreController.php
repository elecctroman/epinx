<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;
use App\Services\CatalogService;
use App\Services\CartService;

class StoreController extends ControllerBase
{
    private CatalogService $catalog;
    private CartService $cart;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->catalog = $container->get(CatalogService::class);
        $this->cart = $container->has(CartService::class) ? $container->get(CartService::class) : new CartService();
    }

    public function home(Request $request): Response
    {
        $categories = $this->catalog->getCategories();
        $featured = $this->catalog->getFeaturedProducts();
        $banners = $this->catalog->getActiveBanners();

        return $this->view('store/home', [
            'title' => 'Discover digital goods',
            'categories' => $categories,
            'featured' => $featured,
            'banners' => $banners,
            'cartQuantity' => $this->cart->totalQuantity(),
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
            'meta' => [
                'description' => 'Instant delivery for digital products, gift cards, and top-up credits with trusted suppliers.',
                'canonical' => app_url('/store'),
                'image' => asset_url('assets/img/storefront.svg'),
            ],
        ]);
    }

    public function category(Request $request): Response
    {
        $slug = (string) $request->route('slug', '');
        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'in_stock' => $request->boolean('in_stock'),
            'sort' => $request->query('sort'),
        ];

        $category = $this->catalog->getCategory($slug);
        if ($category === null) {
            $this->flash('error', 'Category not found.');

            return $this->redirect('/store');
        }

        $pagination = $this->catalog->getProductsByCategory($slug, $filters, $page);

        return $this->view('store/category', [
            'title' => 'Browse category',
            'pagination' => $pagination,
            'filters' => $filters,
            'categorySlug' => $slug,
            'category' => $category,
            'cartQuantity' => $this->cart->totalQuantity(),
            'meta' => [
                'title' => $category['name'] . ' | Epinx',
                'description' => trim((string) ($category['description'] ?? 'Discover offers in ' . $category['name'])),
                'canonical' => app_url('/kategori/' . $slug),
                'schema' => [$this->breadcrumbSchema([
                    ['name' => 'Store', 'item' => app_url('/store')],
                    ['name' => $category['name'], 'item' => app_url('/kategori/' . $slug)],
                ])],
            ],
        ]);
    }

    public function product(Request $request): Response
    {
        $slug = (string) $request->route('slug', '');
        $product = $this->catalog->getProductBySlug($slug);
        if ($product === null) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        return $this->view('store/product', [
            'title' => $product['name'] ?? 'Product detail',
            'product' => $product,
            'cartQuantity' => $this->cart->totalQuantity(),
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
            'meta' => [
                'title' => ($product['name'] ?? 'Product') . ' | Epinx',
                'description' => substr(strip_tags((string) ($product['description'] ?? '')), 0, 160),
                'canonical' => app_url('/urun/' . $slug),
                'image' => asset_url('assets/img/storefront.svg'),
                'schema' => [
                    $this->productSchema($product),
                    $this->breadcrumbSchema([
                        ['name' => 'Store', 'item' => app_url('/store')],
                        ['name' => $product['category_name'] ?? 'Category', 'item' => app_url('/kategori/' . ($product['category_slug'] ?? ''))],
                        ['name' => $product['name'] ?? 'Product', 'item' => app_url('/urun/' . $slug)],
                    ]),
                ],
            ],
        ]);
    }

    public function search(Request $request): Response
    {
        $data = $request->all();
        $errors = Validator::make($data, [
            'q' => 'required|min:2',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/store');
        }

        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'in_stock' => $request->boolean('in_stock'),
            'sort' => $request->query('sort'),
        ];

        $pagination = $this->catalog->search($data['q'], $filters, $page);

        return $this->view('store/search', [
            'title' => 'Search results',
            'query' => $data['q'],
            'pagination' => $pagination,
            'filters' => $filters,
            'cartQuantity' => $this->cart->totalQuantity(),
            'error' => $this->getFlash('error'),
            'meta' => [
                'title' => 'Search results for ' . $data['q'] . ' | Epinx',
                'description' => 'Find digital goods related to ' . $data['q'] . ' with instant delivery.',
                'canonical' => app_url('/arama?q=' . urlencode($data['q'])),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function productSchema(array $product): array
    {
        $variants = $product['variants'] ?? [];
        $availableStock = 0;
        if (is_array($variants)) {
            foreach ($variants as $variant) {
                $availableStock += (int) ($variant['available_stock'] ?? 0);
            }
        }

        $offers = [
            '@type' => 'Offer',
            'priceCurrency' => $product['currency'] ?? 'USD',
            'price' => number_format((float) ($product['price'] ?? 0), 2, '.', ''),
            'availability' => 'https://schema.org/' . ($availableStock > 0 ? 'InStock' : 'PreOrder'),
        ];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'] ?? '',
            'description' => strip_tags((string) ($product['description'] ?? '')),
            'category' => $product['category_name'] ?? null,
            'offers' => $offers,
        ];
    }

    /**
     * @param array<int, array{name:string,item:string}> $crumbs
     * @return array<string, mixed>
     */
    private function breadcrumbSchema(array $crumbs): array
    {
        $itemList = [];
        foreach ($crumbs as $index => $crumb) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['item'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }
}
