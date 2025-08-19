<?php
define('APP_ACCESS', true);

include "../config.php";
include '../template/template.php';
include '../lib/function.php';
include '../lib/function_action.php';
require "../mailer.php";
session_start();
if (!validateSessionCart() || !validateSessionMethodData()) {
    header('location: ../cart.php');
    exit();
}

$aggregated_cart = [];
$totalPrice = 0;
$bought_items = [];
$payment_status = 'pending'; 
$order_number = null;
$invoice_pdf_path = null; 

if (isset($_SESSION['user_info']['order_number'])) {
    $order_number = $_SESSION['user_info']['order_number'];
    $orderStatus = getOrderPaymentStatus($pdo, $order_number);
    $payment_status = $orderStatus['payment_status'];
    $invoice_pdf_path = $orderStatus['invoice_pdf_path'];
}

if (isset($_SESSION['cart'])) {
    $aggregated_cart = aggregateCart();
    $bought_items = processBoughtItems($aggregated_cart, $pdo);
    $totalPrice = calculateTotalPrice($aggregated_cart, $pdo);
}

$user_email = isset($_SESSION['user_info']['email']) ? $_SESSION['user_info']['email'] : '';
$payment_method = isset($_SESSION['user_info']['payment_method']) ? $_SESSION['user_info']['payment_method'] : '';
$freeshipping = isset($_SESSION['freeshipping']) ? $_SESSION['freeshipping'] : false;
$email_send_flag = isset($_SESSION['email_send']) ? $_SESSION['email_send'] : false;

$current_date = date('d.m.Y H:i');
$priceData = calculateFinalPrice($totalPrice, $freeshipping, $payment_method);
$totalPrice = $priceData['finalPrice'];
$shipping_cost = $priceData['shippingCost'];
$total_amount_for_email = $totalPrice;

if ($order_number && $payment_status == 'paid') {
    $total_value = $totalPrice - $shipping_cost;
    
    trackPurchase($order_number, $total_value, $bought_items, $shipping_cost);
}
if (!$email_send_flag && $user_email) {
    $emailSent = sendThankYouEmail($user_email, $payment_status, $order_number, $current_date, $total_amount_for_email, $payment_method, $invoice_pdf_path);
    
    if ($emailSent && isset($_SESSION['email_send'])) {
        $_SESSION['email_send'] = true;
    }
}

session_destroy();

header("Refresh:10; url=../index.php");

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
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
            <div>
                <p class="p-cart">2</p>
                <p class="p-cart-text">Doprava a platba</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
            <div>
                <p class="p-cart">3</p>
                <p class="p-cart-text">Dodací údaje</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
            </svg>
            <div>
                <p class="p-cart p-cart-color">4</p>
                <p class="p-cart-text">Dokončení objednávky</p>
            </div>
        </div>
        <div class="payment-info">
            <div class="payment-info-status">
                <?php renderPaymentStatus($payment_status, $order_number, $payment_method); ?>
            </div>
        </div>
        
        <?php
        if ($payment_status != 'canceled' && $payment_status != 'timeouted' && $payment_method) {
        ?>
        <div class="methods-info">
            <div class="methods-info-status">
                <h2>Platba a doručení</h2>
                <?php
                if($payment_method == "Dobírka"){
                    echo '<p class="methods-name"><span class="methods-title">Zvolená platba: </span><span class="methods-add">Dobírka</span></p>';
                } else {
                    echo '<p class="methods-name"><span class="methods-title">Zvolená platba: </span><span class="methods-add">On-line</span></p>';
                }
                ?>
                <p class="methods-name"><span class="methods-title">Zvolená doprava: </span><span class="methods-add">Zásilkovna</span></p>
                <p class="methods-name"><span class="methods-title">Celková částka k úhradě: </span><span class="methods-add"><?php echo $totalPrice; ?> Kč</span></p>
            </div>
        </div>
        <div class="methods-info">
            <div class="methods-info-status">
                <h2>Přehled objednávky</h2>
                <?php
                foreach ($bought_items as $key => $value) {
                    echo '<p class="methods-name"><span class="methods-title">'.$value['name'].'</span><span class="methods-add">'.$value['price'].' Kč</span></p>';
                }
                
                if($freeshipping){
                    echo '<p class="methods-name"><span class="methods-title">Zásilkovna</span><span class="methods-add">0 Kč</span></p>';
                } else {
                    echo '<p class="methods-name"><span class="methods-title">Zásilkovna</span><span class="methods-add">79 Kč</span></p>';
                }
                
                if($payment_method == "Dobírka"){
                    echo '<p class="methods-name"><span class="methods-title">Dobírka</span><span class="methods-add">45 Kč</span></p>';
                }
                ?>
            </div>
        </div>
        <?php
        } elseif ($payment_status == 'canceled' || $payment_status == 'timeouted') {
            echo '<div class="methods-info">';
            echo '<div class="methods-info-status">';
            echo '<h2>Co dělat dál?</h2>';
            echo '<p><a href="../cart.php">Zpět do košíku</a> - upravte objednávku a zkuste platbu znovu</p>';
            echo '<p><a href="../index.php">Pokračovat v nákupu</a></p>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </section>
</div>
<?php footer_html(); ?>
<script src="../js/mobile_without_footer.js"></script>

</body>
</html>