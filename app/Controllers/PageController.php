<?php

namespace App\Controllers;

use App\Services\PageService;
use App\Services\CartService;
use App\Services\TemplateService;

class PageController
{
    private PageService $pageService;
    private CartService $cartService;
    private TemplateService $templateService;

    public function __construct()
    {
        $this->pageService = new PageService();
        $this->cartService = new CartService();
        $this->templateService = new TemplateService();
    }

    public function show(): void
    {
        $slug = $_GET['page'] ?? '';
        
        if (empty($slug)) {
            $this->renderNotFound();
            return;
        }

        $page = $this->pageService->getPageBySlug($slug);
        
        if (!$page) {
            $this->renderNotFound();
            return;
        }

        $this->renderPage($page, $slug);
    }

    private function renderNotFound(): void
    {
        echo "Stránka nenalezena.";
        exit;
    }

    private function renderPage(array $page, string $slug): void
    {
        $aggregatedCart = $this->cartService->aggregateCart();
        $totalPrice = $this->cartService->calculateCartPrice($aggregatedCart);
        $freeShippingData = $this->cartService->calculateFreeShippingProgress($totalPrice);
        
        $this->templateService->renderHeader($aggregatedCart, "/");
        
        echo '<div class="container">';
        echo '<section class="products-section">';
        echo '<div class="page">';
        echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
        echo "<div class='content'>" . $page['content'] . "</div>";
        echo '</div>';

        if ($slug === 'reklamace-a-vraceni-zbozi') {
            echo '<div class="iframe">';
            echo '<iframe src="https://docs.google.com/forms/d/e/1FAIpQLSd603aDOx7nF1161Sx2J6CrCWuoBeyBkUlIt094iEtST5LmBQ/viewform?embedded=true" width="640" height="1550" frameborder="0" marginheight="0" marginwidth="0">Načítání…</iframe>';
            echo '</div>';
        }

        echo '</section>';
        
        $this->templateService->renderBeforeFooter();
        echo '</div>';
        echo '</div>';
        
        $this->templateService->renderFooter();
        
        echo '<script src="/js/cart.js"></script>';
        echo '<script src="/js/mobile.js"></script>';
        echo '</body></html>';
    }
}