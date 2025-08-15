<?php
define('APP_ACCESS', true);

include "../config.php";
include '../template/template.php';
include '../lib/function.php';
include '../lib/function_action.php';
session_start();
if(!validateSessionUserInfo() OR !validateSessionCart()){
    header('location: ../cart.php');
    exit();
}
$aggregated_cart = aggregateCart();
$orderNumber = 0;
$totalPrice = calculateTotalPrice($aggregated_cart, $pdo);
$bought_items = processBoughtItems($aggregated_cart, $pdo);

if(isset($_SESSION['freeshipping']) and $_SESSION['freeshipping'] == true){
    $totalPrice = $totalPrice + 0;
}else{
    $totalPrice = $totalPrice + 79;
}
if(isset($_SESSION['user_info'])){
   $orderNumber = $_SESSION['user_info']['order_number'];
}

header_html($aggregated_cart, "summary.php", 0);
?>
<div class="container">
    <section class="products-section summary-section">
        <h1 class="my-cart">Dokončení objednávky</h1>
        <div class="info-cart">
            <div>
                <p class="p-cart">1</p>
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
                <p class="p-cart p-cart-color">4</p>
                <p class="p-cart-text">Dokončení objednávky</p>
            </div>
        </div>
        <div class="payment-info">
            <p class="payment-info-hint">Něco se nepovedlo, zkuste provést platbu znovu kliknutím na tlačítko níže.</p>
            <a class="payment-info-button" href='checkout.php'>Kliknutím zaplatíte objednávku</a>
            <div class="payment-info-status">
                <h2>Platba neproběhla</h2>
                <p>Zopakujte prosím platbu.</p>
                <p>Pokud máte jakékoliv dotazy, obraťte se prosím na <span class="payment-info-status-color">info@touchthemagic.cz</span></p>
            </div>
        </div>
        <div class="methods-info">
            <div class="methods-info-status">
                <h2>Platba a doručení</h2>
                <p class="methods-name"><span class="methods-title">Zvolená platba: </span><span class="methods-add">On-line</span></p>
                <p class="methods-name"><span class="methods-title">Zvolená doprava: </span><span class="methods-add">Zásilkovna</span></p>
                <p class="methods-name"><span class="methods-title">Celková částka k úhradě: </span><span class="methods-add"><?php print($totalPrice); ?> Kč</span></p>
            </div>
        </div>
        <div class="methods-info">
            <div class="methods-info-status">
                <h2>Přehled objednávky</h2>
                <?php 
                    foreach ($bought_items as $key => $value) {
                        print('<p class="methods-name"><span class="methods-title">'.$value['name'].'</span><span class="methods-add">'.$value['price'].' Kč</span></p>');
                    }
                    if(isset($_SESSION['freeshipping']) and $_SESSION['freeshipping'] == true){
                        print('<p class="methods-name"><span class="methods-title">Zásilkovna</span><span class="methods-add">0 Kč</span></p>');
                    }else{
                        print('<p class="methods-name"><span class="methods-title">Zásilkovna</span><span class="methods-add">79 Kč</span></p>');

                    }
                ?>
            </div>
        </div>
    </section>
</div>
<?php footer_html(); ?>
<script src="../js/mobile_without_footer.js"></script>
<script>
    window.onload = function() {
        localStorage.clear();
    };
</script>
</body>
</html>
