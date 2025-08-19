<?php
define('APP_ACCESS', true);

include "../config.php";
include '../template/template.php';
include '../lib/function.php';
include '../lib/function_action.php';
require "../mailer.php";

session_start();
if(!validateSessionUserInfo() OR !validateSessionCart()){
    header('location: ../cart.php');
    exit();
}

$aggregated_cart = aggregateCart();

$orderNumber = 0;
$totalPrice = 0;

$bought_items = processBoughtItems($aggregated_cart, $pdo);
$_SESSION['bought_items'] = $bought_items;

$totalPrice = calculateTotalPrice($aggregated_cart, $pdo);
$totalPrice += getShippingCost(isset($_SESSION['freeshipping']) && $_SESSION['freeshipping']);

if(isset($_SESSION['user_info'])){
    $orderNumber = $_SESSION['user_info']['order_number'];
    if($_SESSION['user_info']['payment_method'] == "Dobírka"){
        $totalPrice = $totalPrice + 45;
    }
}


header_html($aggregated_cart, "summary.php", 0);
if($_SESSION['user_info']['payment_method'] == "Dobírka"){
    header('location: thankyou.php');
    exit();
}else{
    print('<script>
setTimeout(function(){
    window.location.href = "../redirect.php";
}, 10000);
</script>');
}
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
            <p class="payment-info-hint">Pro dokončení online platby pokračujte kliknutím na tlačítko níže nebo budete přesměrováni za 5 vteřin.</p>
            <a class="payment-info-button" href='../redirect.php'>Kliknutím zaplatíte objednávku</a>
            <div class="payment-info-status">
                <h2>Vaše objednávka čeká na zaplacení</h2>
                <p>Rekapitulaci Vám zašleme i e-mailem.</p>
                <p>Číslo vaší objednávky <span class="payment-info-status-color"><?php print($orderNumber); ?></span></p>
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

<?php 


$emailsSent = [];

if (isset($_SESSION['user_info']['email']) && !empty($_SESSION['user_info']['email'])) {
    $emailBody = prepareOrderEmail($_SESSION['user_info'], $bought_items, $totalPrice, $orderNumber);
    $subject = "Potvrzení objednávky č. " . $orderNumber . " - Touch The Magic";
    
    $emailResult = sendEmail($_SESSION['user_info']['email'], $subject, $emailBody);
    $emailsSent['customer'] = $emailResult;
    
    $_SESSION['email_sent'] = $emailResult['success'];
    $_SESSION['email_message'] = $emailResult['message'];
}

$adminEmail = "fraibisovab@gmail.com"; 
if (!empty($adminEmail)) {
    $adminEmailBody = prepareAdminNotificationEmailFixed($_SESSION['user_info'], $bought_items, $totalPrice, $orderNumber);
    $adminSubject = "NOVÁ OBJEDNÁVKA č. " . $orderNumber . " - Touch The Magic";
    
    $adminEmailResult = sendEmail($adminEmail, $adminSubject, $adminEmailBody);
    $emailsSent['admin'] = $adminEmailResult;
    
    $_SESSION['admin_email_sent'] = $adminEmailResult['success'];
    $_SESSION['admin_email_message'] = $adminEmailResult['message'];
}


footer_html(); ?>
<script src="../js/mobile_without_footer.js"></script>
<script>
    window.onload = function() {
        localStorage.clear();
    };
</script>
</body>
</html>