<?php
define('APP_ACCESS', true);

include "config.php";
include 'template/template.php';
include 'lib/function.php';
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$items = getRandomProducts($pdo, 4);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_id'])) {
    removeFromCart($_POST['remove_item_id']);
    header("Location: cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity_id'])) {
    updateCartQuantity($_POST['update_quantity_id'], $_POST['action']);
    header("Location: cart.php");
    exit();
}
$aggregated_cart = aggregateCart();
$totalPrice = calculateCartPrice($aggregated_cart, $pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];

    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += 1;
    } else {
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $pdo->prepare($sql);
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

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
$freeShippingLimit = 1500;
$freeShippingData = calculateFreeShippingProgress($totalPrice);
$percentage = $freeShippingData['percentage'];
$remaining = $freeShippingData['remaining'];

function prepareCartData($pdo, $cartItems) {
    $cartData = [];
    $totalPrice = 0;

    foreach ($cartItems as $cartItem) {
        $id = $cartItem['id'];
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        if ($item) {
            $itemPrice = $item['price'] * $cartItem['quantity'];
            $totalPrice += $itemPrice;

            $cartData[] = [
                'name' => $item['name'],
                'quantity' => $cartItem['quantity'],
                'total_price' => $itemPrice
            ];
        }
    }

    return [
        'items' => $cartData,
        'total_price' => $totalPrice
    ];
}

$cartJson = json_encode(prepareCartData($pdo, $aggregated_cart));
$_SESSION['cartjson'] = $cartJson;
header_html_search($aggregated_cart, "cart.php", 0);
?>

<div class="container">
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
        </div>

        <?php if (!empty($aggregated_cart)): ?>
        <div class="cart-table">
        <table>
            <tbody>
                <?php
                foreach ($aggregated_cart as $cartItem):
                    $id = $cartItem['id'];
                    $sql = "SELECT * FROM items WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['id' => $id]);
                    $item = $stmt->fetch();
                    if ($item): 
                ?>
                <tr class="cart-table-tr">
                    <td>
                        <a href="product.php?id=<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </a>
                    </td>
                    <td>
                        <a class="cart-a" href="product.php?id=<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </td>
                    
                    <?php 
                        if($item['stock'] == 'Předobjednat'){
                            print('<td class="stock-preorder green-cart">'.htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8').'</td>'); 
                        }else{
                            print('<td class="green-cart">'.htmlspecialchars($item['stock'], ENT_QUOTES, 'UTF-8').'</td>');
                        }
                    ?>
                    <td class="quantity-td">
                        <form method="post" action="" class="quantity-form">
                            <input type="hidden" name="update_quantity_id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="quantity-container cart-quantiti">
                                <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                                <input type="number" class="quantity" id="quantity_<?php echo $id; ?>" class="quantity" name="quantity" value="<?php echo htmlspecialchars($cartItem['quantity'], ENT_QUOTES, 'UTF-8'); ?>" min="1" readonly>
                                <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
                            </div>
                        </form>
                    </td>
                    <td class="price-one"><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?> Kč/ks</td>
                    <td class="end-price" id="end_price_<?php echo $id; ?>"><?php echo htmlspecialchars($item['price'] * $cartItem['quantity'], ENT_QUOTES, 'UTF-8'); ?> Kč</td>
                    <td>
                        <form class="quantity-margin" method="post" action="" class="form-cart">
                            <input type="hidden" name="remove_item_id" value="<?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="trash-cart">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                    <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
            <p class="empty-basket">Váš košík je prázdný.</p>
            <p class="empty-basket-a"><a href="index.php">Zpět na hlavní stránku</a></p>
        <?php endif; ?>
        <?php
        if($_SESSION['cart']){
            print('<h2 class="totalprice">Celkem: <span>'. $totalPrice. '</span> Kč</h2>');
        }?>
        <div class="transition-free">
            <div class="color-transition-free" style="width: <?php echo $percentage; ?>%;"></div>
            
            <div class="transition-text">
                <span>
                    <?php if ($totalPrice >= $freeShippingLimit): $_SESSION['freeshipping'] = true;?>
                        Doprava zdarma!
                    <?php else: ?>
                        Doprava zdarma při nákupu nad <?php echo $freeShippingLimit; ?> Kč.
                        Chybí vám ještě <?php echo $remaining; ?> Kč.
                    <?php endif; ?>
                </span>
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
                <a href="index.php">Pokračovat v nákupu</a>
            </div>
            <?php
            if($_SESSION['cart']){
                print('<div>
                    <a href="action/shipping_and_payment.php">Doprava a platba</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                    </svg>
                </div>');
            }
            ?>
        </div>
        <h2 class="margin-recomended">Mohlo by se vám líbit</h2>
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
            <?php endif; ?>
        </div>
    </section>
    <?php before_footer_html(); ?>
</div>

<?php footer_html(); ?>
<script src="js/mobile.js"></script>
<script src="js/loading.js"></script>

<script>
    document.getElementById('search-remove')?.remove();
    document.getElementById('left-menu')?.remove();

</script>
</body>
</html>
