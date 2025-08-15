<?php
define('APP_ACCESS', true);

include 'config.php';
include 'template/template.php';
include 'lib/function.php';
ob_start();
session_start();
// Initialize the shopping cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
unset($_SESSION['email_send']);
$tittle = "Nejoblíbenější produkty";
$items = getRandomProducts($pdo, 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        addToCart($product, $quantity);
        
        // Sledování přidání do košíku - POUZE s povolenými cookies
        echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            trackAddToCart(" . json_encode($product) . ", " . $quantity . ");
        }
        </script>";
        
        // Alternativa pro AJAX - vrátit jako JSON response
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
$aggregated_cart = aggregateCart();
$totalPrice = calculateCartPrice($aggregated_cart, $pdo);
$freeShippingData = calculateFreeShippingProgress($totalPrice);
$endprice = $freeShippingData['remaining'];
header_html($aggregated_cart, "index.php");
print('<div class="container">');

try {
    // Base query to load only visible items
    $sql = "SELECT * FROM items WHERE visible = 1";
    
    // Get the category and season from the GET parameters, if they exist
    $cat = isset($_GET['cat']) ? $_GET['cat'] : '';
    $sea = isset($_GET['sea']) ? $_GET['sea'] : '';
    
    // Prepare conditions for filtering items
    $conditions = [];
    $params = [];

    if ($cat) {
        $conditions[] = "category = :cat";
        $params['cat'] = $cat;
        $tittle = category($cat);
    }

    if ($sea) {
        $conditions[] = "season = :sea";
        $params['sea'] = $sea;
        $tittle = category($sea);
    }
    if (!$cat && !$sea) {
        echo get_section_content('slider');
        echo get_section_content('intro_text');
        echo get_section_content('categories');
    }    

    if ($conditions) {
        $sql .= " AND " . implode(' AND ', $conditions);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$location = "?";
$location .= $cat ? "cat=" . urlencode($cat) . "&" : "";
$location .= $sea ? "sea=" . urlencode($sea) : "";
$name = ' ';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $name = $_POST['item_name']; 
    $item_id = $_POST['item_id'];
    
    // Get product data from database
    $sql = "SELECT * FROM items WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $item_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
$name = isset($_SESSION['added_item_name']) ? $_SESSION['added_item_name'] : '';

?>

<style>

</style>

<section class="products-section">
    <?php
    if($cat){
        $breadcrumbs = [
            ['title' => 'Domů', 'url' => 'index.php'],
            ['title' => htmlspecialchars($tittle), 'url' => 'index.php?cat=' . urlencode($cat)]
        ];
        echo '<div class="pathb">' . renderBreadcrumb($breadcrumbs) . '</div>';
    }
    ?>
    <?php left_menu_html(); ?>
    <h2 class="margin-h2"><?php print("$tittle"); ?></h2>
    <?php // V místě kde chcete zobrazit filtry (po kontrole $location)
        if ($cat || $sea) {
            // Spočítejte počet produktů pro zobrazení
            $filter_info = ['count' => count($items)];
            echo render_product_filters($location, $filter_info);
        }
        $filter_data = get_filter_conditions();
        if (!empty($filter_data['conditions'])) {
            $sql .= " AND " . implode(' AND ', $filter_data['conditions']);
        }

        // Přidejte řazení
        $sql .= " " . get_sort_order();
    ?>
    <div class="products">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <div class="product">
                    <a href="product.php?id=<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>"><img src="uploads/<?php echo htmlspecialchars($item['image_folder'], ENT_QUOTES, 'UTF-8'); ?>/1.jpg" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"></a>
                    <a href="product.php?id=<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>"><h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3></a>
                    <div class="info-price-stock">
                        <p class="price"><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?> Kč</p>
                        <p class="stock <?php 
                            if ($item['stock'] == 'Není skladem') {
                                echo 'stock-out-of-stock';
                            } elseif ($item['stock'] == 'Předobjednat') {
                                echo 'stock-preorder';
                            }
                        ?>"><?php echo htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <?php 
                        $text = htmlspecialchars($item['description_main'], ENT_QUOTES, 'UTF-8');
                        $words = explode(' ', $text);
                        $limited = implode(' ', array_slice($words, 0, 20));
                        $limited .= (count($words) > 20) ? '...' : '';
                    ?>
                    <div class="description"><p><?php echo $limited; ?></p></div>
                    
                    <?php if ($item['stock'] == 'Není skladem'): ?>
                        <div class="btn btn-unavailable">
                            <p class="add-to-cart">Brzy skladem</p>
                        </div>
                    <?php elseif ($item['stock'] == 'Předobjednat'): ?>
                        <form class="add-to-cart-form" method="post" action="">
                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-preorder">
                                <p class="add-to-cart">Předobjednat</p>
                            </button>
                            <div class="preorder-note">
                                *Dodání může trvat až měsíc
                            </div>
                        </form>
                    <?php else: ?>
                        <form class="add-to-cart-form" method="post" action="">
                            <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn">
                                <p class="add-to-cart">Přidat do košíku</p>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="soon">Již brzy.. 😃</p>
        <?php endif; ?>
        <?php big_popup($items, $endprice, $name); ?>
        <?php if (isset($_SESSION['product_added']) && $_SESSION['product_added']): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('cart-popup-big');
            const closeBtn = document.querySelector('.close-btn-big');
            const continueShoppingBtn = document.getElementById('continue-shopping'); 

            if (popup) {
                popup.style.display = 'flex';

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
            document.addEventListener('DOMContentLoaded', function () {
                let forms = document.querySelectorAll('.add-to-cart-form');
                forms.forEach(function (form) {
                    form.addEventListener('submit', function () {
                        sessionStorage.setItem('scrollPosition', window.scrollY);
                    });
                });

                if (sessionStorage.getItem('scrollPosition') !== null) {
                    window.scrollTo(0, sessionStorage.getItem('scrollPosition'));
                    sessionStorage.removeItem('scrollPosition');  
                }
            });


        </script>
    <?php endif; ?>
    </div>
</section>
<?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>
<script src="js/main.js"></script>
<script src="js/mobile.js"></script>

</body>
</html>