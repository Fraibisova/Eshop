<?php

namespace App\Controllers\Shop;

use App\Controllers\BaseController;
use App\Services\ProductService;
use App\Services\CartService;
use App\Services\VariantService;
use App\Interfaces\ProductServiceInterface;
use App\Interfaces\CartServiceInterface;
use Exception;

class CartController extends BaseController
{
    private ProductServiceInterface $productService;
    private CartServiceInterface $cartService;
    private VariantService $variantService;

    public function __construct(
        ProductServiceInterface $productService = null,
        CartServiceInterface $cartService = null,
        VariantService $variantService = null
    ) {
        parent::__construct();
        $this->productService = $productService ?? new ProductService();
        $this->cartService = $cartService ?? new CartService();
        $this->variantService = $variantService ?? new VariantService();
    }

    public function show(): void
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $items = $this->productService->getRandomProducts(4);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_id'])) {
            $remove_item_id = $_POST['remove_item_id'];
            
            if (isset($_POST['variant_id'])) {
                $this->variantService->removeVariantFromCart($_POST['variant_id']);
            } else {
                $this->cartService->removeFromCart($remove_item_id);
            }
            
            header("Location: /shop/cart");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity_id'])) {
            $update_item_id = $_POST['update_quantity_id'];
            $action = $_POST['action'];
            
            if (isset($_POST['variant_id'])) {
                try {
                    $variant_id = $_POST['variant_id'];
                    $cartKey = 'variant_' . $variant_id;
                    
                    if (isset($_SESSION['cart'][$cartKey])) {
                        $current_quantity = $_SESSION['cart'][$cartKey]['quantity'];
                        
                        if ($action === 'increase') {
                            $new_quantity = $current_quantity + 1;
                            $this->variantService->updateVariantInCart($variant_id, $new_quantity);
                        } elseif ($action === 'decrease') {
                            if ($current_quantity > 1) {
                                $new_quantity = $current_quantity - 1;
                                $this->variantService->updateVariantInCart($variant_id, $new_quantity);
                            } else {
                                $this->variantService->removeVariantFromCart($variant_id);
                            }
                        }
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            } else {
                $this->cartService->updateCartQuantity($update_item_id, $action);
            }
            
            header("Location: /shop/cart");
            exit();
        }

        $aggregated_cart = $this->cartService->aggregateCart();
        $totalPrice = $this->cartService->calculateCartPrice($aggregated_cart);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
            $item_id = $_POST['item_id'];

            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]['quantity'] += 1;
            } else {
                $sql = "SELECT * FROM items WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['id' => $item_id]);
                $item = $stmt->fetch();
                
                if ($item) {
                    $_SESSION['cart'][$item_id] = [
                        'id' => $item['id'],
                        'quantity' => 1,
                        'price' => $item['price']
                    ];
                }
            }

            header("Location: /shop/cart");
            exit();
        }

        $freeShippingLimit = 1500;
        $freeShippingData = $this->cartService->calculateFreeShippingProgress($totalPrice);
        $percentage = $freeShippingData['percentage'];
        $remaining = $freeShippingData['remaining'];

        $cartJson = json_encode($this->prepareCartData($this->db, $aggregated_cart));
        $_SESSION['cartjson'] = $cartJson;
        
        $this->renderCartPage($aggregated_cart, $items, $totalPrice, $percentage, $remaining, $freeShippingLimit);
    }

    private function prepareCartData($pdo, $cartItems)
    {
        $cartData = [];
        $totalPrice = 0;

        foreach ($cartItems as $cartItem) {
            $id = $cartItem['id'];
            $is_variant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
            
            if ($is_variant) {
                $itemPrice = $cartItem['price'] * $cartItem['quantity'];
                $totalPrice += $itemPrice;
                
                $display_name = $cartItem['name'];
                if (isset($cartItem['variant_code']) && $cartItem['variant_code']) {
                    $display_name .= ' - ' . $cartItem['variant_code'];
                    if (isset($cartItem['variant_name']) && $cartItem['variant_name']) {
                        $display_name .= ' - ' . $cartItem['variant_name'];
                    }
                }
                if (isset($cartItem['stock_status']) && $cartItem['stock_status'] === 'Předobjednat') {
                    $display_name .= ' (Předobjednané)';
                }
                
                $cartData[] = [
                    'name' => $display_name,
                    'quantity' => $cartItem['quantity'],
                    'total_price' => $itemPrice,
                    'variant_code' => $cartItem['variant_code'] ?? '',
                    'is_variant' => true
                ];
            } else {
                $sql = "SELECT * FROM items WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $id]);
                $item = $stmt->fetch();

                if ($item) {
                    $item_price = $item['price'];
                    $sale_percentage = (int)($item['sale'] ?? 0);
                    if ($sale_percentage > 0) {
                        $item_price = $item_price * (1 - $sale_percentage / 100);
                    }
                    
                    $itemPrice = $item_price * $cartItem['quantity'];
                    $totalPrice += $itemPrice;

                    $display_name = $item['name'];
                    if ($item['stock'] === 'Předobjednat') {
                        $display_name .= ' (Předobjednané)';
                    }

                    $cartData[] = [
                        'name' => $display_name,
                        'quantity' => $cartItem['quantity'],
                        'total_price' => $itemPrice,
                        'variant_code' => '',
                        'is_variant' => false
                    ];
                }
            }
        }

        return [
            'items' => $cartData,
            'total_price' => $totalPrice
        ];
    }

    private function renderCartPage($aggregated_cart, $items, $totalPrice, $percentage, $remaining, $freeShippingLimit): void
    {
        header_html_search($aggregated_cart, "/shop/cart", 0);
        
        echo '<div class="container">
    <section class="products-section cart-section">
        <h1 class="my-cart">Můj košík</h1>
        <div class="info-cart">
        <div>
                <p class="p-cart p-cart-color">1</p>
                <p class="p-cart-text">Nákupní košík</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right arrow-delete" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
            <div>
                <p class="p-cart">2</p>
                <p class="p-cart-text">Doprava a platba</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right arrow-delete" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
            <div>
                <p class="p-cart">3</p>
                <p class="p-cart-text">Dodací údaje</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right arrow-delete" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
            <div>
                <p class="p-cart">4</p>
                <p class="p-cart-text">Dokončení objednávky</p>
            </div>
        </div>';

        if (!empty($aggregated_cart)) {
            echo '<div class="cart-table">
        <table>
            <tbody>';
            
            foreach ($aggregated_cart as $cartKey => $cartItem) {
                $id = $cartItem['id'];
                $is_variant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
                
                if ($is_variant) {
                    $variant_id = $cartItem['variant_id'];
                    $variant = $this->variantService->getVariantById($variant_id);
                    $sql = "SELECT * FROM items WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(['id' => $id]);
                    $item = $stmt->fetch();
                    $display_name = $cartItem['name'];
                    $item_price = $cartItem['price'];
                    $variant_code = $cartItem['variant_code'] ?? '';
                    $stock_status = $cartItem['stock_status'] ?? $item['stock'];
                } else {
                    $sql = "SELECT * FROM items WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(['id' => $id]);
                    $item = $stmt->fetch();
                    $display_name = $item['name'];
                    $item_price = $item['price'];
                    $variant_code = '';
                    $variant_id = null;
                    $stock_status = $item['stock'];
                    
                    $sale_percentage = (int)($item['sale'] ?? 0);
                    if ($sale_percentage > 0) {
                        $item_price = $item_price * (1 - $sale_percentage / 100);
                    }
                }
                
                if ($item) {
                    echo '<tr class="cart-table-tr">
                    <td>
                        <a href="/shop/product?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                            <img src="' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                        </a>
                    </td>
                    <td>
                        <a class="cart-a" href="/shop/product?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                            ' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '
                        </a>';
                    if ($variant_code) {
                        echo '<br><small class="variant-info">Varianta: ' . htmlspecialchars($variant_code, ENT_QUOTES, 'UTF-8') . '</small>';
                    }
                    echo '</td>';
                    
                    if ($stock_status == 'Předobjednat') {
                        echo '<td class="stock-preorder green-cart">' . htmlspecialchars($stock_status, ENT_QUOTES, 'UTF-8') . '</td>';
                    } else {
                        echo '<td class="green-cart">' . htmlspecialchars($stock_status, ENT_QUOTES, 'UTF-8') . '</td>';
                    }
                    
                    echo '<td class="quantity-td">
                        <form method="post" action="" class="quantity-form">
                            <input type="hidden" name="update_quantity_id" value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">';
                    if ($variant_id) {
                        echo '<input type="hidden" name="variant_id" value="' . htmlspecialchars($variant_id, ENT_QUOTES, 'UTF-8') . '">';
                    }
                    echo '<div class="quantity-container cart-quantiti">
                                <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                                <input type="number" class="quantity" id="quantity_' . ($variant_id ? 'variant_'.$variant_id : $id) . '" class="quantity" name="quantity" value="' . htmlspecialchars($cartItem['quantity'], ENT_QUOTES, 'UTF-8') . '" min="1" readonly>
                                <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
                            </div>
                        </form>
                    </td>
                    <td class="price-one">' . number_format($item_price, 0, ',', ' ') . ' Kč/ks</td>
                    <td class="end-price" id="end_price_' . ($variant_id ? 'variant_'.$variant_id : $id) . '">
                        ' . number_format($item_price * $cartItem['quantity'], 0, ',', ' ') . ' Kč';
                    if ($is_variant && isset($cartItem['original_price']) && $cartItem['original_price'] != $item_price) {
                        echo '<br><small class="original-price-small">' . number_format($cartItem['original_price'] * $cartItem['quantity'], 0, ',', ' ') . ' Kč</small>';
                    }
                    echo '</td>
                    <td>
                        <form class="quantity-margin" method="post" action="" class="form-cart">
                            <input type="hidden" name="remove_item_id" value="' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">';
                    if ($variant_id) {
                        echo '<input type="hidden" name="variant_id" value="' . htmlspecialchars($variant_id, ENT_QUOTES, 'UTF-8') . '">';
                    }
                    echo '<button type="submit" class="trash-cart">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                    <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>';
                }
            }
            
            echo '</tbody>
        </table>
        </div>';
        } else {
            echo '<p class="empty-basket">Váš košík je prázdný.</p>
            <p class="empty-basket-a"><a href="/">Zpět na hlavní stránku</a></p>';
        }
        
        if ($_SESSION['cart']) {
            echo '<h2 class="totalprice">Celkem: <span>' . $totalPrice . '</span> Kč</h2>';
        }
        
        echo '<div class="transition-free">
            <div class="color-transition-free" style="width: ' . $percentage . '%;"></div>
            
            <div class="transition-text">
                <span>';
        if ($totalPrice >= $freeShippingLimit) {
            $_SESSION['freeshipping'] = true;
            echo 'Doprava zdarma!';
        } else {
            echo 'Doprava zdarma při nákupu nad ' . $freeShippingLimit . ' Kč.
                        Chybí vám ještě ' . $remaining . ' Kč.';
        }
        echo '</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
                    <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                </svg>
            </div>
        </div>
        <div class="cart-navigation">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg>
                <a href="/">Pokračovat v nákupu</a>
            </div>';
        if ($_SESSION['cart']) {
            echo '<div>
                    <a href="/action/shipping-and-payment">Doprava a platba</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                    </svg>
                </div>';
        }
        echo '</div>
        <h2 class="margin-recomended">Mohlo by se vám líbit</h2>
        <div class="products">';
        
        if (!empty($items)) {
            foreach ($items as $item) {
                echo '<div class="product">
                        <a href="/shop/product?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '"><img src="uploads/' . htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8') . '/1.jpg" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '"></a>
                        <a href="/shop/product?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '"><h3>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</h3></a>
                        <div class="info-price-stock">
                            <p class="price">' . htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>
                            <p class="stock ';
                if ($item['stock'] == 'Není skladem') {
                    echo 'stock-out-of-stock';
                } elseif ($item['stock'] == 'Předobjednat') {
                    echo 'stock-preorder';
                }
                echo '">' . htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8') . '</p>
                        </div>';
                
                $text = htmlspecialchars($item['description_main'], ENT_QUOTES, 'UTF-8');
                $words = explode(' ', $text);
                $limited = implode(' ', array_slice($words, 0, 20));
                $limited .= (count($words) > 20) ? '...' : '';
                
                echo '<div class="description"><p>' . $limited . '</p></div>';
                
                if ($item['stock'] == 'Není skladem') {
                    echo '<div class="btn btn-unavailable">
                                <p class="add-to-cart">Brzy skladem</p>
                            </div>';
                } elseif ($item['stock'] == 'Předobjednat') {
                    echo '<form class="add-to-cart-form" method="post" action="">
                                <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                                <button type="submit" class="btn btn-preorder">
                                    <p class="add-to-cart">Předobjednat</p>
                                </button>
                                <div class="preorder-note">
                                    *Dodání může trvat až měsíc
                                </div>
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
        }
        
        echo '</div>
    </section>';
    
        before_footer_html();
        echo '</div>';
        footer_html();
        echo '<script src="/js/mobile.js"></script>
<script src="/js/loading.js"></script>

<script>
    document.getElementById("search-remove")?.remove();
    document.getElementById("left-menu")?.remove();
</script>
</body>
</html>';
    }
}