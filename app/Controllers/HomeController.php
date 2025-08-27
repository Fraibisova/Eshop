<?php

namespace App\Controllers;

use App\Services\ProductService;
use App\Services\CartService;
use App\Services\TemplateService;
use App\Services\AnalyticsService;
use App\Interfaces\ProductServiceInterface;
use App\Interfaces\CartServiceInterface;

class HomeController extends BaseController
{
    private ProductServiceInterface $productService;
    private CartServiceInterface $cartService;
    private TemplateService $templateService;
    private AnalyticsService $analyticsService;

    public function __construct(
        ProductServiceInterface $productService = null,
        CartServiceInterface $cartService = null,
        TemplateService $templateService = null,
        AnalyticsService $analyticsService = null
    ) {
        parent::__construct();
        $this->productService = $productService ?? new ProductService();
        $this->cartService = $cartService ?? new CartService();
        $this->templateService = $templateService ?? new TemplateService();
        $this->analyticsService = $analyticsService ?? new AnalyticsService();
    }

    public function index(): void
    {
        ob_start();
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        unset($_SESSION['email_send']);
        
        $title = "Nejoblíbenější produkty";
        $items = $this->productService->getRandomProducts(5);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
            $this->handleAjaxAddToCart();
            return;
        }

        $aggregated_cart = $this->cartService->aggregateCart();
        $totalPrice = $this->cartService->calculateCartPrice($aggregated_cart);
        $freeShippingData = $this->cartService->calculateFreeShippingProgress($totalPrice);
        $endprice = $freeShippingData['remaining'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
            $this->handleFormAddToCart();
            return;
        }

        $this->renderProducts($aggregated_cart, $title, $endprice);
        
        ob_end_flush();
    }

    private function handleAjaxAddToCart(): void
    {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'] ?? 1;
        
        $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id");
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($product) {
            $this->cartService->addToCart($product, $quantity);
            
            echo "<script>
            if (localStorage.getItem('cookie_consent') === 'accepted') {
                trackAddToCart(" . json_encode($product) . ", " . $quantity . ");
            }
            </script>";
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode([
                    'success' => true,
                    'track_data' => [
                        'product' => $product,
                        'quantity' => $quantity,
                        'action' => 'add_to_cart'
                    ]
                ]);
                exit;
            }
        }
    }

    private function handleFormAddToCart(): void
    {
        $name = $_POST['item_name']; 
        $item_id = $_POST['item_id'];
        
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $item_id]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($product) {
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]['quantity'] += 1;
            } else {
                $_SESSION['cart'][$item_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => 1,
                ];
            }
            $_SESSION['product_added'] = true;
            $_SESSION['added_item_name'] = $name;
        }

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    private function renderProducts($aggregated_cart, $title, $endprice): void
    {
        header_html($aggregated_cart, "/");
        print('<div class="container">');

        try {
            $sql = "SELECT * FROM items WHERE visible = 1";
            
            $cat = isset($_GET['cat']) ? $_GET['cat'] : '';
            $sea = isset($_GET['sea']) ? $_GET['sea'] : '';
            
            $conditions = [];
            $params = [];

            if ($cat) {
                $conditions[] = "category = :cat";
                $params['cat'] = $cat;
                $title = category($cat);
            }

            if ($sea) {
                $conditions[] = "season = :sea";
                $params['sea'] = $sea;
                $title = category($sea);
            }
            
            if (!$cat && !$sea) {
                echo get_section_content('slider');
                echo get_section_content('intro_text');
                echo get_section_content('categories');
            }    

            if ($conditions) {
                $sql .= " AND " . implode(' AND ', $conditions);
            }

            $filter_data = $this->templateService->getFilterConditions();
            if (!empty($filter_data['conditions'])) {
                $sql .= " AND " . implode(' AND ', $filter_data['conditions']);
                if (!empty($filter_data['params'])) {
                    $params = array_merge($params, $filter_data['params']);
                }
            }

            $sql .= " " . $this->templateService->getSortOrder();

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

        $location = "/";
        if ($cat || $sea) {
            $location .= "?";
            $location .= $cat ? "cat=" . urlencode($cat) . "&" : "";
            $location .= $sea ? "sea=" . urlencode($sea) . "&" : "";
            $location = rtrim($location, '&');
        }
        
        $name = isset($_SESSION['added_item_name']) ? $_SESSION['added_item_name'] : '';

        echo '<section class="products-section">';
        
        if($cat){
            $breadcrumbs = [
                ['title' => 'Domů', 'url' => '/'],
                ['title' => htmlspecialchars($title), 'url' => '/?cat=' . urlencode($cat)]
            ];
            echo '<div class="pathb">' . renderBreadcrumb($breadcrumbs) . '</div>';
        }
        
        left_menu_html();
        echo '<h2 class="margin-h2">' . $title . '</h2>';
        
        $filter_info = ['count' => count($items)];
        echo $this->templateService->renderProductFilters($location, $filter_info);
        
        echo '<div class="products">';
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $this->renderProductCard($item);
            }
        } else {
            $this->renderNoResults($cat, $sea);
        }
        
        big_popup($items, $endprice, $name);
        
        if (isset($_SESSION['product_added']) && $_SESSION['product_added']) {
            $this->renderAddToCartScript();
            unset($_SESSION['product_added']);
        }
        
        echo '</div>
              </section>';
        
        before_footer_html();
        echo '</div>';
        footer_html();
        echo '<script src="/js/main.js"></script>
              <script src="/js/mobile.js"></script>
              </body></html>';
    }

    private function renderProductCard($item): void
    {
        echo '<div class="product">
                <a href="/shop/product?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                    <img src="/uploads/' . htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8') . '/1.jpg" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                </a>
                <a href="/shop/product?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                    <h3>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</h3>
                </a>
                <div class="info-price-stock">
                    <p class="price">' . htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>
                    <p class="stock ' . $this->getStockClass($item['stock']) . '">' . htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8') . '</p>
                </div>';
        
        $text = htmlspecialchars($item['description_main'], ENT_QUOTES, 'UTF-8');
        $words = explode(' ', $text);
        $limited = implode(' ', array_slice($words, 0, 20));
        $limited .= (count($words) > 20) ? '...' : '';
        
        echo '<div class="description"><p>' . $limited . '</p></div>';
        
        if ($item['stock'] == 'Není skladem') {
            echo '<div class="btn btn-unavailable">
                    <p class="add-to-cart">Zboží není dostupné</p>
                  </div>';
        } elseif ($item['stock'] == 'Předobjednat') {
            echo '<form class="add-to-cart-form" method="post" action="">
                    <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                    <button type="submit" class="btn btn-preorder">
                        <p class="add-to-cart">Předobjednat</p>
                    </button>
                    <div class="preorder-note">*Dodání může trvat až měsíc</div>
                  </form>';
        } else {
            echo '<form class="add-to-cart-form" method="post" action="">
                    <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                    <button type="submit" class="btn">
                        <p class="add-to-cart">Přidat do košíku</p>
                    </button>
                  </form>';
        }
        
        echo '</div>';
    }

    private function getStockClass($stock): string
    {
        if ($stock == 'Není skladem') {
            return 'stock-out-of-stock';
        } elseif ($stock == 'Předobjednat') {
            return 'stock-preorder';
        }
        return '';
    }

    private function renderNoResults($cat, $sea): void
    {
        $has_filters = (isset($_GET['price']) && $_GET['price'] !== '') || 
                      (isset($_GET['stock']) && $_GET['stock'] !== '') ||
                      (isset($_GET['sort']) && $_GET['sort'] !== '');
        
        if ($has_filters) {
            echo '<div class="no-results">
                    <p class="no-results-message">V této kategorii nejsou žádné produkty odpovídající vašim filtrům.</p>
                    <p class="no-results-suggestion">Zkuste změnit filtry nebo <a href="' . 
                    ($cat ? '/?cat=' . urlencode($cat) : ($sea ? '/?sea=' . urlencode($sea) : '/')) . 
                    '">zobrazit všechny produkty</a>.</p>
                  </div>';
        } else {
            echo '<p class="soon">Již brzy.. 😃</p>';
        }
    }

    private function renderAddToCartScript(): void
    {
        echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    const popup = document.getElementById("cart-popup-big");
                    const closeBtn = document.querySelector(".close-btn-big");
                    const continueShoppingBtn = document.getElementById("continue-shopping"); 

                    if (popup) {
                        popup.style.display = "flex";

                        if (closeBtn) {
                            closeBtn.addEventListener("click", function () {
                                popup.style.display = "none";
                            });
                        }

                        if (continueShoppingBtn) {
                            continueShoppingBtn.addEventListener("click", function () {
                                popup.style.display = "none";
                            });
                        }

                        setTimeout(function () {
                            popup.style.display = "none";
                        }, 30000);
                    }

                    window.addEventListener("click", function (event) {
                        if (event.target === popup) {
                            popup.style.display = "none";
                        }
                    });
                });
                
                document.addEventListener("DOMContentLoaded", function () {
                    let forms = document.querySelectorAll(".add-to-cart-form");
                    forms.forEach(function (form) {
                        form.addEventListener("submit", function () {
                            sessionStorage.setItem("scrollPosition", window.scrollY);
                        });
                    });

                    if (sessionStorage.getItem("scrollPosition") !== null) {
                        window.scrollTo(0, sessionStorage.getItem("scrollPosition"));
                        sessionStorage.removeItem("scrollPosition");  
                    }
                });
              </script>';
    }
}