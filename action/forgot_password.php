<?php
define('APP_ACCESS', true);

include '../config.php';
include '../template/template.php';
include '../lib/function.php';
session_start();


$aggregated_cart = aggregateCart();
$endprice = calculateCartPrice($aggregated_cart, $db);
header_html($aggregated_cart, "login.php");
$token = 'token';
$token_hash = hash("sha256", $token);
$sql = "SELECT * FROM users WHERE reset_token_hash = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$token_hash]);
$user = $stmt->fetch();
if (!$user) {
    var_dump("Neplatný nebo již použitý token.");
}else{
    var_dump($user);
}
?>
<div class="container">
    <section class="form-section">
        <div class="background_box border-box">
            <h2 class="register">Reset hesla</h2>

            <form method="post" action="send_password_reset.php">
                
                <input type="email" class="log-input" name="email_send" id="email_send" placeholder="Zadejte email">

                <input type="submit" value="Odeslat" class="submit-register">
                <?php
                if(isset($_SESSION['email'])){
                    print('<p>'.$_SESSION['email'].'</p>');
                    unset($_SESSION['email']);
                }
            ?>
            </form>
        </div>
    </section>
    <?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>