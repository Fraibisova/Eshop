<?php
define('APP_ACCESS', true);

include "config.php";
include 'template/template.php';
include 'lib/function.php';
session_start();
ob_start();

$id = isset($_GET['id']) ? $_GET['id'] : '';
$quantity = 1;
$name = '';
$category = ''; 
$original_category = '';
if (isset($id) && is_numeric($id)) {
    $sql = "SELECT items.*, items_description.* FROM items INNER JOIN items_description ON items.id = items_description.id_item
        WHERE visible = 1 AND items.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $key => $value) {
        $original_category = $value['category'];
        $category = $value['category'];
        $name = $value['name'];
    }
    $category = category($category);
} else {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
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

$random = getRandomProducts($pdo, 4);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];
    $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $quantity;
    } else {
        foreach ($items as $item) {
            if ($item['id'] == $item_id) {
                $_SESSION['cart'][$item_id] = [
                    'id' => $item['id'],
                    'price' => $item['price'],
                    'quantity' => $quantity,
                ];
            }
        }
    }
    $_SESSION['product_added'] = true;
    header("Location: product.php?id=" . urlencode($id));
    exit();
}

$aggregated_cart = aggregateCart();

$totalPrice = calculateCartPrice($aggregated_cart, $pdo);
$freeShippingData = calculateFreeShippingProgress($totalPrice);
$endprice = $freeShippingData['remaining'];
header_html($aggregated_cart, "product.php?id=$id");
ob_end_flush();

?>
<div class="container">
    <section class="products-section">
    <div class="path">
        <svg xmlns="http://www.w3.org/2000/svg" class="path-margin" width="16" height="16" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
        </svg>
        <div class="other-path-margin">
            <a href="index.php">Domů</a>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right" viewBox="0 0 16 16">
            <path d="M6 12.796V3.204L11.481 8zm.659.753 5.48-4.796a1 1 0 0 0 0-1.506L6.66 2.451C6.011 1.885 5 2.345 5 3.204v9.592a1 1 0 0 0 1.659.753"/>
        </svg>
        <div class="other-path-margin">
            <a href="index.php?cat=<?php echo urlencode($original_category); ?>"><?php echo htmlspecialchars($category); ?></a>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right" viewBox="0 0 16 16">
            <path d="M6 12.796V3.204L11.481 8zm.659.753 5.48-4.796a1 1 0 0 0 0-1.506L6.66 2.451C6.011 1.885 5 2.345 5 3.204v9.592a1 1 0 0 0 1.659.753"/>
        </svg>
        <div class="other-path-margin-p">
            <p class="other-path-p"><?php print($name); ?></p>
        </div>
    </div>
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
            <div class="product-specific">
                <div class="product-gallery">
                    <div class="product-main-image zoom-container">
                        <img id="mainImage" src="uploads/<?php echo htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8'); ?>/1.jpg" alt="Produkt" onclick="openFullscreen(this.src)">
                    </div>
                    <div class="product-thumbnails">
                    <?php
                        $folder = 'uploads/' . htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8');
                        $files = glob($folder . '/*.jpg'); 

                        foreach ($files as $filePath) {
                            $filename = basename($filePath);
                            echo '<img class="thumbnail" src="' . htmlspecialchars($folder . '/' . $filename) . '" alt="Produkt" onclick="changeImage(\'' . htmlspecialchars($folder . '/' . $filename) . '\')">';
                        }
                    ?>
                    </div>

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
                    <h2><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="product-code product-text-p">Kód produktu: <span><?php echo htmlspecialchars($item['product_code'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                    <p class="product-paragraph product-text-p main-description">
                    <?php echo htmlspecialchars($item['description_main'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                    <a href="#link">Více informací zde</a>
                    <div class="product-info">
                        <h3 class="bei">Cena</h3>
                        <h3 class="bou">Dostupnost</h3>
                    </div>
                    <div class="product-info">
                        <p class="bei product-paragraph product-text-p"><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?> Kč</p>
                        <p class="stock product-paragraph product-text-p <?php 
                            if ($item['stock'] == 'Není skladem') {
                                echo 'stock-out-of-stock';
                            } elseif ($item['stock'] == 'Předobjednat') {
                                echo 'stock-preorder';
                            } elseif ($item['stock'] == 'Skladem'){
                                echo 'green';
                            }
                        ?>"><?php echo htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="product-info">
                        <p class="bei product-paragraph product-text-p dph">*Nejsme plátci DPH</p>
                    </div>
                    <div class="product-info bozo">
                    <?php if ($item['stock'] == 'Skladem'): ?>
                        <form method="post" action="">
                            <input type="hidden" name="update_quantity_id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="quantity-container cart-quantiti">
                                <button type="button" id="decrease" class="quantity-btn">-</button>
                                <input type="number" class="quantity" id="quantity" name="quantity" value="<?php echo $quantity; ?>" min="1">
                                <button type="button" id="increase" class="quantity-btn">+</button>
                            </div>
                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id_item'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="add-to-cart-btn btn" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi cart-product-icon" viewBox="0 0 16 16">
                                    <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                                </svg>
                                <p class="cart-text">PŘIDAT DO KOŠÍKU</p>
                            </button>
                        </form>
                    <?php elseif ($item['stock'] == 'Předobjednat'): ?>
                        <form method="post" action="">
                            <input type="hidden" name="update_quantity_id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="quantity-container cart-quantiti">
                                <button type="button" id="decrease" class="quantity-btn">-</button>
                                <input type="number" class="quantity" id="quantity" name="quantity" value="<?php echo $quantity; ?>" min="1">
                                <button type="button" id="increase" class="quantity-btn">+</button>
                            </div>
                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id_item'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="add-to-cart-btn btn" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi cart-product-icon" viewBox="0 0 16 16">
                                    <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                                </svg>
                                <p class="cart-text">Předobjednat</p>
                            </button>
                        </form>
                        <div class="preorder-note left-align">
                            *Dodání může trvat až měsíc
                        </div>
                    <?php endif; ?>


                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const decreaseBtn = document.getElementById('decrease');
                        const increaseBtn = document.getElementById('increase');
                        const quantityInput = document.getElementById('quantity');

                        decreaseBtn.addEventListener('click', function() {
                            let currentValue = parseInt(quantityInput.value);
                            if (currentValue > 1) {
                                quantityInput.value = currentValue - 1;
                            }
                        });

                        increaseBtn.addEventListener('click', function() {
                            let currentValue = parseInt(quantityInput.value);
                            quantityInput.value = currentValue + 1;
                        });
                    });
                    </script>

                    <!-- <form method="post" action="">
                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button class="add-to-cart-btn btn" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi cart-product-icon" viewBox="0 0 16 16">
                                    <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                                </svg>
                                <p class="cart-text">PŘIDAT DO KOŠÍKU</p>
                            </button>
                        </form> -->
                    </div>
                </div>
                <div class="just-border" id="link">
                    <div class="product-description">
                        <h3>Popis produktu</h3>
                        <p><?php echo htmlspecialchars($item['paragraph1'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars($item['paragraph2'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars($item['paragraph3'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars($item['paragraph4'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars($item['paragraph5'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars($item['paragraph6'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="product-parameters">
                        <div class="border-right">
                            <h3>Parametry produktu</h3>
                            <p class="mass">Hmostnost: <span><?php echo htmlspecialchars($item['mass'], ENT_QUOTES, 'UTF-8'); ?> g</span></p>
                            <h4>Kategorie</h4>
                            <a href=""><?php echo category($item['category']); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-products">Tyto produkty nemáme.</p>
    <?php endif; ?>
    <?php big_popup($random, $endprice, $name); ?>
        <?php if (isset($_SESSION['product_added']) && $_SESSION['product_added']): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('cart-popup-big');
            const closeBtn = document.querySelector('.close-btn-big'); // Tlačítko pro zavření
            const continueShoppingBtn = document.getElementById('continue-shopping'); // Tlačítko "Pokračovat v nákupu"

            if (popup) {
                popup.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                <?php unset($_SESSION['product_added']); ?>

                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        popup.style.display = 'none';
                    });
                }

                if (continueShoppingBtn) {
                    continueShoppingBtn.addEventListener('click', function () {
                        popup.style.display = 'none';
                    });
                }

                    setTimeout(function () {
                        popup.style.display = 'none';
                    }, 30000);
                }

                window.addEventListener('click', function (event) {
                    if (event.target === popup) {
                        popup.style.display = 'none';
                    }
                });
            });

        </script>
        <?php endif; ?>
    </section>
    <?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>
<script src="js/add_to_cart.js"></script>
<script src="js/mobile.js"></script>
</body>
</html>
