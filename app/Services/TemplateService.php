<?php

namespace App\Services;

class TemplateService
{
    private DatabaseService $dbService;

    public function __construct()
    {
        $this->dbService = DatabaseService::getInstance();
        
        if (!function_exists('header_html')) {
            global $db, $pdo;
            $db = $this->dbService->getConnection();
            $pdo = $db;
            require_once __DIR__ . '/../../template/template.php';
        }
    }

    public function renderCookieBanner(): string
    {
        return '
        <div id="cookie-banner" class="cookie-banner">
            <div class="cookie-content">
                <p>Tento web používá soubory cookies k poskytování služeb, personalizaci reklam a analýze návštěvnosti. Více informací naleznete na stránce <a href="/privacy-policy">Zásady ochrany osobních údajů</a>.</p>
                <div class="cookie-buttons">
                    <button id="accept-cookies" class="btn-accept">Přijmout vše</button>
                    <button id="reject-cookies" class="btn-reject">Odmítnout</button>
                    <button id="customize-cookies" class="btn-customize">Nastavit</button>
                </div>
            </div>
        </div>';
    }

    public function renderProductFilters(string $location, array $current_filters = []): string
    {
        $current_sort = isset($_GET['sort']) ? $_GET['sort'] : '';
        $current_price = isset($_GET['price']) ? $_GET['price'] : '';
        $current_stock = isset($_GET['stock']) ? $_GET['stock'] : '';
        
        $product_count = isset($current_filters['count']) ? $current_filters['count'] : 10;
        
        $html = '
        <div class="filter-section">
            <div class="filter-container">
                <div class="filter-left">
                    <span class="filter-label">FILTR:</span>
                    
                    <!-- Filtr ceny -->
                    <div class="filter-dropdown">
                        <select name="price" id="price-filter" class="filter-select">
                            <option value="">CENA</option>
                            <option value="0-500"' . ($current_price == '0-500' ? ' selected' : '') . '>0 - 500 Kč</option>
                            <option value="500-1000"' . ($current_price == '500-1000' ? ' selected' : '') . '>500 - 1000 Kč</option>
                            <option value="1000-2000"' . ($current_price == '1000-2000' ? ' selected' : '') . '>1000 - 2000 Kč</option>
                            <option value="2000-5000"' . ($current_price == '2000-5000' ? ' selected' : '') . '>2000 - 5000 Kč</option>
                            <option value="5000+"' . ($current_price == '5000+' ? ' selected' : '') . '>5000+ Kč</option>
                        </select>
                    </div>
                    
                    <!-- Filtr dostupnosti -->
                    <div class="filter-dropdown">
                        <select name="stock" id="stock-filter" class="filter-select">
                            <option value="">PODLE DOSTUPNOSTI</option>
                            <option value="Skladem"' . ($current_stock == 'Skladem' ? ' selected' : '') . '>Skladem</option>
                            <option value="Předobjednat"' . ($current_stock == 'Předobjednat' ? ' selected' : '') . '>K předobjednání</option>
                            <option value="Není skladem"' . ($current_stock == 'Není skladem' ? ' selected' : '') . '>Brzy skladem</option>
                        </select>
                    </div>
                </div>
                
                <!-- Řazení produktů -->
                <div class="filter-right">
                    <div class="sort-tabs">
                        <a href="' . $this->buildFilterUrl($location, ['sort' => 'recommended']) . '" class="sort-tab' . ($current_sort == 'recommended' || $current_sort == '' ? ' active' : '') . '">DOPORUČUJEME</a>
                        <a href="' . $this->buildFilterUrl($location, ['sort' => 'cheapest']) . '" class="sort-tab' . ($current_sort == 'cheapest' ? ' active' : '') . '">NEJLEVNĚJŠÍ</a>
                        <a href="' . $this->buildFilterUrl($location, ['sort' => 'expensive']) . '" class="sort-tab' . ($current_sort == 'expensive' ? ' active' : '') . '">NEJDRAŽŠÍ</a>
                        <a href="' . $this->buildFilterUrl($location, ['sort' => 'bestselling']) . '" class="sort-tab' . ($current_sort == 'bestselling' ? ' active' : '') . '">NEJPRODÁVANĚJŠÍ</a>
                        <a href="' . $this->buildFilterUrl($location, ['sort' => 'alphabetical']) . '" class="sort-tab' . ($current_sort == 'alphabetical' ? ' active' : '') . '">ABECEDNĚ</a>
                    </div>
                    
                    <div class="product-count">
                        <span>' . $product_count . ' položek celkem</span>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .filter-section {
            margin: 20px 0;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 20px;
        }
        
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .filter-dropdown {
            position: relative;
        }
        
        .filter-select {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 30px 8px 12px;
            font-size: 14px;
            color: #666;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'><path fill=\'%23666\' d=\'M8 12L3 7h10l-5 5z\'/></svg>");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 12px;
            min-width: 180px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .filter-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .sort-tabs {
            display: flex;
            gap: 0;
            background: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .sort-tab {
            padding: 8px 16px;
            text-decoration: none;
            color: #666;
            font-size: 13px;
            font-weight: 500;
            border-right: 1px solid #ddd;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .sort-tab:last-child {
            border-right: none;
        }
        
        .sort-tab:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .sort-tab.active {
            background: #2f244f;
            color: white;
        }
        
        .product-count {
            color: #999;
            font-size: 14px;
            white-space: nowrap;
        }
        
        /* Responzivní design */
        @media (max-width: 768px) {
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-left {
                justify-content: space-between;
            }
            
            .filter-select {
                min-width: 140px;
                font-size: 13px;
            }
            
            .sort-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .sort-tab {
                font-size: 12px;
                padding: 6px 12px;
            }
            
            .filter-right {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .product-count {
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .filter-left {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-select {
                width: 100%;
                min-width: auto;
            }
            
            .sort-tab {
                font-size: 11px;
                padding: 6px 8px;
            }
        }
        </style>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const priceFilter = document.getElementById("price-filter");
            if (priceFilter) {
                priceFilter.addEventListener("change", function() {
                    updateFilter("price", this.value);
                });
            }
            
            const stockFilter = document.getElementById("stock-filter");
            if (stockFilter) {
                stockFilter.addEventListener("change", function() {
                    updateFilter("stock", this.value);
                });
            }
            
            function updateFilter(filterType, value) {
                const url = new URL(window.location.href);
                
                if (value === "") {
                    url.searchParams.delete(filterType);
                } else {
                    url.searchParams.set(filterType, value);
                }
                
                window.location.href = url.toString();
            }
        });
        </script>';
        
        return $html;
    }

    public function buildFilterUrl(string $baseLocation, array $newParams = []): string
    {
        $existingParams = $_GET;
        $params = array_merge($existingParams, $newParams);
        
        $params = array_filter($params, function($value) {
            return $value !== '' && $value !== null;
        });
        
        $queryString = http_build_query($params);
        return $baseLocation . ($queryString ? '?' . $queryString : '');
    }

    public function getFilterConditions(): array
    {
        $conditions = [];
        $params = [];
        
        if (isset($_GET['price']) && $_GET['price'] !== '') {
            $priceRange = $_GET['price'];
            switch ($priceRange) {
                case '0-500':
                    $conditions[] = 'price <= 500';
                    break;
                case '500-1000':
                    $conditions[] = 'price > 500 AND price <= 1000';
                    break;
                case '1000-2000':
                    $conditions[] = 'price > 1000 AND price <= 2000';
                    break;
                case '2000+':
                    $conditions[] = 'price > 2000';
                    break;
            }
        }
        
        if (isset($_GET['stock']) && $_GET['stock'] !== '') {
            $conditions[] = 'stock = :stock_filter';
            $params['stock_filter'] = $_GET['stock'];
        }
        
        return [
            'conditions' => $conditions,
            'params' => $params
        ];
    }

    public function getSortOrder(): string
    {
        $sortOrder = 'ORDER BY id DESC'; 
        
        if (isset($_GET['sort']) && $_GET['sort'] !== '') {
            switch ($_GET['sort']) {
                case 'price_asc':
                    $sortOrder = 'ORDER BY price ASC';
                    break;
                case 'price_desc':
                    $sortOrder = 'ORDER BY price DESC';
                    break;
                case 'name_asc':
                    $sortOrder = 'ORDER BY name ASC';
                    break;
                case 'name_desc':
                    $sortOrder = 'ORDER BY name DESC';
                    break;
            }
        }
        
        return $sortOrder;
    }

    public function renderHeader(array $aggregatedCart, string $location, int $print = 1): void
    {
        if (function_exists('header_html')) {
            global $db, $pdo;
            $db = $this->dbService->getConnection();
            $pdo = $db;
            header_html($aggregatedCart, $location, $print);
        }
    }

    public function renderFooter(): void
    {
        if (function_exists('footer_html')) {
            global $db, $pdo;
            $db = $this->dbService->getConnection();
            $pdo = $db;
            footer_html();
        }
    }

    public function renderBeforeFooter(): void
    {
        if (function_exists('before_footer_html')) {
            global $db, $pdo;
            $db = $this->dbService->getConnection();
            $pdo = $db;
            before_footer_html();
        }
    }

    public function renderLeftMenu(): void
    {
        if (function_exists('left_menu_html')) {
            global $db, $pdo;
            $db = $this->dbService->getConnection();
            $pdo = $db;
            left_menu_html();
        }
    }

    public function renderLeftMenuSpecial(): void
    {
        if (function_exists('left_menu_html_special')) {
            left_menu_html_special();
        }
    }

    public function renderBreadcrumb(array $breadcrumbs): string
    {
        if (function_exists('renderBreadcrumb')) {
            return renderBreadcrumb($breadcrumbs);
        }
        return '';
    }

    public function renderProductCard(array $item): string
    {
        $productService = new ProductService();
        return $productService->renderProductCard($item);
    }
}