<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Services\AnalyticsService;
use App\Services\CartService;
use App\Services\TemplateService;
use App\Interfaces\ProductServiceInterface;
use App\Interfaces\CartServiceInterface;

class SearchController extends BaseController
{
    private ProductServiceInterface $productService;
    private AnalyticsService $analyticsService;
    private CartServiceInterface $cartService;
    private TemplateService $templateService;

    public function __construct(
        ProductServiceInterface $productService = null,
        AnalyticsService $analyticsService = null,
        CartServiceInterface $cartService = null,
        TemplateService $templateService = null
    ) {
        parent::__construct();
        $this->productService = $productService ?? new ProductService();
        $this->analyticsService = $analyticsService ?? new AnalyticsService();
        $this->cartService = $cartService ?? new CartService();
        $this->templateService = $templateService ?? new TemplateService();
    }

    public function search(): void
    {
        $query = $_GET['query'] ?? '';

        if (empty($query)) {
            $this->renderEmptyQuery();
            return;
        }

        $this->analyticsService->trackSearch($query);
        $results = $this->productService->searchProducts($query);
        $aggregatedCart = $this->cartService->aggregateCart();

        $this->renderSearchResults($query, $results, $aggregatedCart);
    }

    private function renderEmptyQuery(): void
    {
        echo 'Zadejte klíčové slovo.';
    }

    private function renderSearchResults(string $query, array $results, array $aggregatedCart): void
    {
        $this->templateService->renderHeader($aggregatedCart, "search.php", 0);
        
        echo '<div class="container">
            <section class="products-section top">';

        $breadcrumbs = [
            ['title' => 'Domů', 'url' => '/'],
            ['title' => 'Vyhledávání']
        ];
        echo $this->templateService->renderBreadcrumb($breadcrumbs);

        if ($results) {
            $this->templateService->renderLeftMenu();
            echo '<h2 class="search-h2">Výsledky hledání "' . htmlspecialchars($query) . '"</h2>';
            echo '<div class="products">';
            
            foreach ($results as $item) {
                echo $this->templateService->renderProductCard($item);
            }
            
            echo '</div>';
        } else {
            $this->templateService->renderLeftMenuSpecial();
            echo '<h2 class="search-h2">Výsledky hledání "' . htmlspecialchars($query) . '"</h2>';
            echo '<p class="unlucky">Nebyly nalezeny žádné produkty.</p>';
        }

        echo '</section>';
        
        $this->templateService->renderBeforeFooter();
        echo '</div>';
        $this->templateService->renderFooter();
        
        echo '<script src="/js/mobile.js"></script>
            <script src="/js/loading.js"></script>
            </body>
            </html>';
    }
}