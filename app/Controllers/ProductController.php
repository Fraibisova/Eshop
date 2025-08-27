<?php

namespace App\Controllers;

use Exception;
use PDO;
use App\Services\ProductService;
use App\Services\CartService;
use App\Services\VariantService;
use App\Services\CategoryService;
use App\Interfaces\ProductServiceInterface;
use App\Interfaces\CartServiceInterface;

class ProductController extends BaseController
{
    private ProductServiceInterface $productService;
    private CartServiceInterface $cartService;
    private VariantService $variantService;
    private CategoryService $categoryService;

    public function __construct(
        ProductServiceInterface $productService = null,
        CartServiceInterface $cartService = null,
        VariantService $variantService = null,
        CategoryService $categoryService = null
    ) {
        parent::__construct();
        $this->productService = $productService ?? new ProductService();
        $this->cartService = $cartService ?? new CartService();
        $this->variantService = $variantService ?? new VariantService();
        $this->categoryService = $categoryService ?? new CategoryService();
    }

    public function show(): void
    {
        ob_start();

        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $quantity = 1;
        $name = '';
        $category = ''; 
        $original_category = '';

        if (isset($id) && is_numeric($id)) {
            $sql = "SELECT items.*, items_description.* FROM items INNER JOIN items_description ON items.id = items_description.id_item
                WHERE visible = 1 AND items.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $key => $value) {
                $original_category = $value['category'];
                $category = $value['category'];
                $name = $value['name'];
            }
            $category = $this->categoryService->translateCategory($category);
        } else {
            header("Location: /");
            exit();
        }

        if (isset($_GET['id'])) {
            $product_id = $_GET['id'];
            $stmt = $this->db->prepare("SELECT * FROM items WHERE id = :id");
            $stmt->execute(['id' => $product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                echo "<script>
                if (localStorage.getItem('cookie_consent') === 'accepted') {
                    trackProductView(" . json_encode($product) . ");
                }
                </script>";
            }
        }

        $random = $this->productService->getRandomProducts(4);

        $hasVariants = $this->variantService->hasVariants($id);
        $variants = [];
        $variantCount = 0;
        if ($hasVariants) {
            $variants = $this->variantService->getVariantsWithPricing($id);
            $variantCount = count($variants);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
            $item_id = $_POST['item_id'];
            
            if (isset($_POST['variant_id']) && !empty($_POST['variant_id'])) {
                $variant_id = $_POST['variant_id'];
                $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                
                try {
                    $this->variantService->addVariantToCart($variant_id, $quantity);
                    $_SESSION['product_added'] = true;
                } catch (Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            } else {
                $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? intval($_POST['quantity']) : 1;

                try {
                    $sql = "SELECT * FROM items WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(['id' => $item_id]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$product) {
                        throw new Exception("Produkt neexistuje");
                    }
                    

                    if (isset($_SESSION['cart'][$item_id])) {
                        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$item_id] = [
                            'id' => $product['id'],
                            'price' => $product['price'],
                            'quantity' => $quantity,
                        ];
                    }
                    $_SESSION['product_added'] = true;
                } catch (Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
            
            header("Location: /shop/product?id=" . urlencode($id));
            exit();
        }

        $aggregated_cart = $this->cartService->aggregateCart();
        $totalPrice = $this->cartService->calculateCartPrice($aggregated_cart);
        $freeShippingData = $this->cartService->calculateFreeShippingProgress($totalPrice);
        $endprice = $freeShippingData['remaining'];
        
        \header_html($aggregated_cart, "/shop/product?id=$id");
        ob_end_flush();

        echo '<div class="container">
    <section class="products-section">
    <div class="path">
        <svg xmlns="http://www.w3.org/2000/svg" class="path-margin" width="16" height="16" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5.5V7.207l5-5z"/>
        </svg>
        <div class="other-path-margin">
            <a href="/">Domů</a>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right" viewBox="0 0 16 16">
            <path d="M6 12.796V3.204L11.481 8zm.659.753 5.48-4.796a1 1 0 0 0 0-1.506L6.66 2.451C6.011 1.885 5 2.345 5 3.204v9.592a1 1 0 0 0 1.659.753"/>
        </svg>
        <div class="other-path-margin">
            <a href="/?cat=' . urlencode($original_category) . '">' . htmlspecialchars($category) . '</a>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right" viewBox="0 0 16 16">
            <path d="M6 12.796V3.204L11.481 8zm.659.753 5.48-4.796a1 1 0 0 0 0-1.506L6.66 2.451C6.011 1.885 5 2.345 5 3.204v9.592a1 1 0 0 0 1.659.753"/>
        </svg>
        <div class="other-path-margin-p">
            <p class="other-path-p">' . htmlspecialchars($name) . '</p>
        </div>
    </div>';

        if (!empty($items)) {
            foreach ($items as $item) {
                echo '<div class="product-specific">
                <div class="product-gallery">
                    <div class="product-main-image zoom-container">
                        <img id="mainImage" src="uploads/' . htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8') . '/1.jpg" alt="Produkt" onclick="openFullscreen(this.src)">
                    </div>
                    <div class="product-thumbnails">';
                
                $folder = 'uploads/' . htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8');
                $files = glob($folder . '/*.jpg'); 

                foreach ($files as $filePath) {
                    $filename = basename($filePath);
                    echo '<img class="thumbnail" src="' . htmlspecialchars($folder . '/' . $filename) . '" alt="Produkt" onclick="changeImage(\'' . htmlspecialchars($folder . '/' . $filename) . '\')">';
                }
                
                echo '</div>

                    <div id="fullscreenImage" class="fullscreen">
                        <span class="close_img" onclick="closeFullscreen()">&times;</span>
                        <img class="fullscreen-content" id="fullscreenImg">
                    </div>
                </div>
                <script>
                    function changeImage(imageSrc) {
                        var mainImage = document.getElementById("mainImage");
                        mainImage.src = imageSrc;
                    }

                    function openFullscreen(src) {
                        var fullscreenDiv = document.getElementById("fullscreenImage");
                        var fullscreenImg = document.getElementById("fullscreenImg");
                        fullscreenDiv.style.display = "block";
                        fullscreenImg.src = src;
                    }

                    function closeFullscreen() {
                        var fullscreenDiv = document.getElementById("fullscreenImage");
                        fullscreenDiv.style.display = "none";
                    }
                </script>

                <div class="product-text">
                    <h2>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</h2>
                    <p class="product-code product-text-p">Kód produktu: <span>' . htmlspecialchars($item['product_code'], ENT_QUOTES, 'UTF-8') . '</span></p>
                    <p class="product-paragraph product-text-p main-description">
                    ' . htmlspecialchars($item['description_main'], ENT_QUOTES, 'UTF-8') . '
                    </p>
                    <a href="#link">Více informací zde</a>
                    
                    <div class="product-info">
                        <h3 class="bei">Cena</h3>
                        <h3 class="bou">Dostupnost</h3>
                    </div>
                    <div class="product-info">';
                
                $sale_percentage = (int)($item['sale'] ?? 0);
                $original_price = (float)$item['price'];
                $discounted_price = $sale_percentage > 0 ? $original_price * (1 - $sale_percentage / 100) : $original_price;
                
                if ($hasVariants && !empty($variants)) {
                    $priceRange = $this->variantService->getVariantPriceRange($id);
                    if ($priceRange && $priceRange['min'] != $priceRange['max']) {
                        $displayPrice = "od " . number_format($priceRange['min'], 0, ',', ' ') . " do " . number_format($priceRange['max'], 0, ',', ' ') . " Kč";
                    } else {
                        $displayPrice = number_format($discounted_price, 0, ',', ' ') . " Kč";
                    }
                } else {
                    $displayPrice = number_format($discounted_price, 0, ',', ' ') . " Kč";
                }
                
                if ($sale_percentage > 0) {
                    echo '<span class="sale-price-product">' . $displayPrice . '</span>
                          <span style="" class=" bei product-paragraph product-text-p sale-badge">-' . $sale_percentage . '%</span>';
                } else {
                    echo '<p class="bei product-paragraph product-text-p">' . $displayPrice . '</p>';
                }
                
                echo '<p style="margin-left: 25px;" class="stock product-paragraph product-text-p ';
                if ($item['stock'] == 'Není skladem') {
                    echo 'stock-out-of-stock';
                } elseif ($item['stock'] == 'Předobjednat') {
                    echo 'stock-preorder';
                } elseif ($item['stock'] == 'Skladem'){
                    echo 'green';
                }
                echo '">';
                
                if ($hasVariants && !empty($variants)) {
                    $availableVariants = 0;
                    foreach ($variants as $variant) {
                        if ($variant['stock_status'] === 'Skladem' && $variant['stock_quantity'] > 0) {
                            $availableVariants++;
                        }
                    }
                    
                    if ($availableVariants > 0) {
                        echo "Skladem: 1ks";
                    } else {
                        echo "Není skladem";
                    }
                } else {
                    echo htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8');
                }
                echo '</p>
                    </div>
                    <div class="product-info">
                        <p class="bei product-paragraph product-text-p dph">*Nejsme plátci DPH</p>
                    </div>
                    
                    <div class="product-info bozo">';
                
                if ($item['stock'] == 'Skladem' || $hasVariants) {
                    echo '<form method="post" action="" id="product-form">
                            <input type="hidden" name="update_quantity_id" value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">';
                    
                    if ($hasVariants && !empty($variants)) {
                        echo '<select name="variant_id" id="variant-select" class="variant-select" required>
                                <option value="">Vyberte variantu...</option>';
                        foreach ($variants as $variant) {
                            $isAvailable = $variant['stock_status'] === 'Skladem' && $variant['stock_quantity'] > 0;
                            $optionText = 'Varianta ' . $variant['variant_code'];
                            if (!empty($variant['variant_name'])) {
                                $optionText .= ' - ' . $variant['variant_name'];
                            }
                            $optionText .= ' (' . number_format($variant['discounted_price'], 0, ',', ' ') . ' Kč)';
                            if (!$isAvailable) {
                                $optionText .= ' - Nedostupné';
                            }
                            
                            echo '<option value="' . $variant['id'] . '"' . (!$isAvailable ? ' disabled' : '') . '
                                    data-price="' . $variant['discounted_price'] . '"
                                    data-stock="' . $variant['stock_quantity'] . '"
                                    data-status="' . $variant['stock_status'] . '">
                                    ' . htmlspecialchars($optionText) . '
                                  </option>';
                        }
                        echo '</select>';
                    } else {
                        echo '<div class="quantity-container cart-quantiti">
                                <button type="button" id="decrease" class="quantity-btn">-</button>
                                <input type="number" class="quantity" id="quantity" name="quantity" value="' . $quantity . '" min="1">
                                <button type="button" id="increase" class="quantity-btn">+</button>
                              </div>';
                    }
                    
                    echo '<input type="hidden" name="item_id" value="' . htmlspecialchars($item['id_item'], ENT_QUOTES, 'UTF-8') . '">
                            <button class="add-to-cart-btn btn" type="submit" id="add-to-cart-button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi cart-product-icon" viewBox="0 0 16 16">
                                    <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                                </svg>';
                    
                    if ($hasVariants && $variantCount > 1) {
                        echo '<p class="cart-text">ZOBRAZIT VARIANTY</p>';
                    } else {
                        echo '<p class="cart-text">PŘIDAT DO KOŠÍKU</p>';
                    }
                    
                    echo '</button>
                        </form>';
                    
                    $showCartInfo = false;
                    $cartInfoHtml = '';
                    
                    if ($hasVariants && !empty($variants)) {
                        $inCartVariants = [];
                        foreach ($variants as $variant) {
                            if ($this->cartService->isProductInCart($id, $variant['id'])) {
                                $quantity = \getProductQuantityInCart($id, $variant['id']);
                                $inCartVariants[] = "Varianta " . $variant['variant_code'] . " ({$quantity}ks)";
                            }
                        }
                        if (!empty($inCartVariants)) {
                            $showCartInfo = true;
                            $cartInfoHtml = '<div class="product-in-cart-info">';
                            $cartInfoHtml .= '<p><strong>Toto zboží máte v košíku:</strong></p>';
                            foreach ($inCartVariants as $variantInfo) {
                                $cartInfoHtml .= '<p class="cart-variant-info">• ' . htmlspecialchars($variantInfo, ENT_QUOTES, 'UTF-8') . '</p>';
                            }
                            $cartInfoHtml .= '</div>';
                        }
                        
                        if (isset($_SESSION['error_message'])) {
                            echo '<div class="error-message">
                                    <p style="color: red; margin-top: 10px;">' . htmlspecialchars($_SESSION['error_message']) . '</p>
                                  </div>';
                            unset($_SESSION['error_message']);
                        }
                    } else {
                        if ($this->cartService->isProductInCart($id)) {
                            $quantity = \getProductQuantityInCart($id);
                            $showCartInfo = true;
                            $cartInfoHtml = '<div class="product-in-cart-info">';
                            $cartInfoHtml .= '<p><strong>Toto zboží máte v košíku (' . $quantity . 'ks)</strong></p>';
                            $cartInfoHtml .= '</div>';
                        }
                    }
                    
                    if ($showCartInfo) {
                        echo $cartInfoHtml;
                    }
                    
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="product-error-info">';
                        echo '<p><strong>' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
                        echo '</div>';
                        unset($_SESSION['error_message']);
                    }
                    
                } elseif ($item['stock'] == 'Předobjednat') {
                    echo '<form method="post" action="">
                            <input type="hidden" name="update_quantity_id" value="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">
                            <div class="quantity-container cart-quantiti">
                                <button type="button" id="decrease" class="quantity-btn">-</button>
                                <input type="number" class="quantity" id="quantity" name="quantity" value="' . $quantity . '" min="1">
                                <button type="button" id="increase" class="quantity-btn">+</button>
                            </div>
                            <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id_item'], ENT_QUOTES, 'UTF-8') . '">
                            <button class="add-to-cart-btn btn" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi cart-product-icon" viewBox="0 0 16 16">
                                    <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                                </svg>
                                <p class="cart-text">Předobjednat</p>
                            </button>
                        </form>
                        <div class="preorder-note left-align">
                            *Dodání může trvat až měsíc
                        </div>';
                    
                    if ($this->cartService->isProductInCart($id)) {
                        $quantity = \getProductQuantityInCart($id);
                        echo '<div class="product-in-cart-info">';
                        echo '<p><strong>Toto zboží máte v košíku (' . $quantity . 'ks)</strong></p>';
                        echo '</div>';
                    }
                    
                    if (isset($_SESSION['error_message'])) {
                        echo '<div class="product-error-info">';
                        echo '<p><strong>' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
                        echo '</div>';
                        unset($_SESSION['error_message']);
                    }
                }

                echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const decreaseBtn = document.getElementById("decrease");
                        const increaseBtn = document.getElementById("increase");
                        const quantityInput = document.getElementById("quantity");
                        const variantSelect = document.getElementById("variant-select");
                        const quantitySection = document.getElementById("quantity-section");
                        const addToCartButton = document.getElementById("add-to-cart-button");
                        
                        const variantCount = ' . $variantCount . ';

                        if (decreaseBtn) {
                            decreaseBtn.addEventListener("click", function() {
                                let currentValue = parseInt(quantityInput.value);
                                if (currentValue > 1) {
                                    quantityInput.value = currentValue - 1;
                                }
                            });
                        }

                        if (increaseBtn) {
                            increaseBtn.addEventListener("click", function() {
                                let currentValue = parseInt(quantityInput.value);
                                let maxStock = parseInt(quantityInput.getAttribute("max")) || 999;
                                if (currentValue < maxStock) {
                                    quantityInput.value = currentValue + 1;
                                }
                            });
                        }

                        if (variantSelect) {
                            variantSelect.addEventListener("change", function() {
                                const selectedOption = this.options[this.selectedIndex];
                                
                                if (this.value) {
                                    if (variantCount > 1 && quantitySection) {
                                        quantitySection.style.display = "flex";
                                    }
                                    
                                    const stock = parseInt(selectedOption.getAttribute("data-stock"));
                                    const status = selectedOption.getAttribute("data-status");
                                    
                                    if (quantityInput) {
                                        if (status === "Skladem") {
                                            quantityInput.setAttribute("max", stock);
                                            quantityInput.value = 1;
                                        } else {
                                            quantityInput.setAttribute("max", 999);
                                            quantityInput.value = 1;
                                        }
                                    }
                                    
                                    if (addToCartButton) {
                                        const cartText = addToCartButton.querySelector(".cart-text");
                                        if (status === "Předobjednat") {
                                            cartText.textContent = "PŘEDOBJEDNAT";
                                        } else {
                                            cartText.textContent = "PŘIDAT DO KOŠÍKU";
                                        }
                                    }
                                } else {
                                    if (variantCount > 1 && quantitySection) {
                                        quantitySection.style.display = "none";
                                    }
                                }
                            });
                            
                            if (variantCount === 1) {
                                const firstOption = variantSelect.options[1]; 
                                if (firstOption) {
                                    variantSelect.value = firstOption.value;
                                    variantSelect.dispatchEvent(new Event("change"));
                                }
                            }
                        }

                        const form = document.getElementById("product-form");
                        if (form) {
                            form.addEventListener("submit", function(e) {
                                if (variantSelect && !variantSelect.value) {
                                    e.preventDefault();
                                    alert("Prosím vyberte variantu před přidáním do košíku.");
                                    return false;
                                }
                            });
                        }
                    });
                    </script>
                    </div>
                </div>
                <div class="just-border" id="link">
                    <div class="product-description">
                        <h3>Popis produktu</h3>
                        <p>' . htmlspecialchars($item['paragraph1'], ENT_QUOTES, 'UTF-8') . '</p>
                        <p>' . htmlspecialchars($item['paragraph2'], ENT_QUOTES, 'UTF-8') . '</p>
                        <p>' . htmlspecialchars($item['paragraph3'], ENT_QUOTES, 'UTF-8') . '</p>
                        <p>' . htmlspecialchars($item['paragraph4'], ENT_QUOTES, 'UTF-8') . '</p>
                        <p>' . htmlspecialchars($item['paragraph5'], ENT_QUOTES, 'UTF-8') . '</p>
                        <p>' . htmlspecialchars($item['paragraph6'], ENT_QUOTES, 'UTF-8') . '</p>
                    </div>
                    <div class="product-parameters">
                        <div class="border-right">
                            <h3>Parametry produktu</h3>
                            <p class="mass">Hmostnost: <span>' . htmlspecialchars($item['mass'], ENT_QUOTES, 'UTF-8') . ' g</span></p>
                            <h4>Kategorie</h4>
                            <a href="">' . $this->categoryService->translateCategory($item['category']) . '</a>
                        </div>
                    </div>
                </div>
            </div>';
            }
        } else {
            echo '<p class="no-products">Tyto produkty nemáme.</p>';
        }
        
        \big_popup($random, $endprice, $name);
        
        if (isset($_SESSION['product_added']) && $_SESSION['product_added']) {
            echo '<script>
            document.addEventListener("DOMContentLoaded", function () {
            const popup = document.getElementById("cart-popup-big");
            const closeBtn = document.querySelector(".close-btn-big");
            const continueShoppingBtn = document.getElementById("continue-shopping"); 

            if (popup) {
                popup.style.display = "flex";
                document.body.style.overflow = "hidden";';
            unset($_SESSION['product_added']);
            echo '
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

        </script>';
        }
        
        echo '</section>';
        \before_footer_html();
        echo '</div>';
        \footer_html();
        
        echo '<style>
.product-in-cart-info {
    margin-top: 10px;
    padding: 10px;
    background-color: #e8f5e8;
    border: 1px solid #4CAF50;
    border-radius: 4px;
    font-size: 14px;
}

.product-in-cart-info p {
    margin: 0 0 5px 0;
    color: #2e7d32;
}

.cart-variant-info {
    margin-left: 10px;
    font-size: 13px;
}

.product-error-info {
    margin-top: 10px;
    padding: 10px;
    background-color: #ffebee;
    border: 1px solid #f44336;
    border-radius: 4px;
    font-size: 14px;
}

.product-error-info p {
    margin: 0;
    color: #c62828;
}
</style>

<script src="/js/add_to_cart.js"></script>
<script src="/js/mobile.js"></script>
</body>
</html>';
    }
}