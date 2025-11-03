<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\CatalogService;

class SeoController extends ControllerBase
{
    public function __construct($container)
    {
        parent::__construct($container);
    }

    public function sitemap(Request $request): Response
    {
        /** @var CatalogService $catalog */
        $catalog = $this->container->get(CatalogService::class);
        $baseUrl = rtrim((string) app_url('/'), '/');

        $urls = [
            ['loc' => $baseUrl . '/', 'changefreq' => 'daily'],
            ['loc' => $baseUrl . '/store', 'changefreq' => 'daily'],
        ];

        foreach ($catalog->getCategories() as $category) {
            $urls[] = [
                'loc' => $baseUrl . '/kategori/' . $category['slug'],
                'changefreq' => 'weekly',
            ];
        }

        $products = $catalog->getFeaturedProducts(50);
        foreach ($products as $product) {
            $urls[] = [
                'loc' => $baseUrl . '/urun/' . $product['slug'],
                'changefreq' => 'daily',
            ];
        }

        $xml = $this->buildSitemap($urls);

        return Response::xml($xml);
    }

    public function robots(Request $request): Response
    {
        $content = "User-agent: *\n" .
            "Allow: /\n" .
            "Sitemap: " . rtrim((string) app_url('/'), '/') . "/sitemap.xml\n";

        return Response::text($content);
    }

    /**
     * @param array<int, array<string, string>> $entries
     */
    private function buildSitemap(array $entries): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($entries as $entry) {
            $lines[] = '    <url>';
            $lines[] = '        <loc>' . htmlspecialchars($entry['loc'], ENT_XML1) . '</loc>';
            if (!empty($entry['changefreq'])) {
                $lines[] = '        <changefreq>' . htmlspecialchars($entry['changefreq'], ENT_XML1) . '</changefreq>';
            }
            $lines[] = '    </url>';
        }
        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }
}
