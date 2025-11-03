<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\CatalogService;
use App\Services\CartService;

class HomeController extends ControllerBase
{
    private CatalogService $catalog;
    private CartService $cart;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->catalog = $container->get(CatalogService::class);
        $this->cart = $container->has(CartService::class) ? $container->get(CartService::class) : new CartService();
    }

    public function index(Request $request): Response
    {
        return $this->view('store/home', [
            'title' => 'Welcome to Epinx',
            'categories' => $this->catalog->getCategories(),
            'featured' => $this->catalog->getFeaturedProducts(),
            'banners' => $this->catalog->getActiveBanners(),
            'cartQuantity' => $this->cart->totalQuantity(),
        ]);
    }
}
