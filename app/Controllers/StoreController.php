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
        ]);
    }
}
