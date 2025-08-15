<?php

define('APP_ACCESS', true);

include "../config.php";
include '../template/template.php';
include '../lib/function.php';
include '../lib/function_action.php';

session_start();
$name_new = "";
$surname_new = "";
$email_new = "";
$phone_new = "";
$street_new = "";
$housenumber_new = "";
$city_new = "";
$zipcode_new = "";
$newsletter = "";
$county_new = '';
$cartData = '';

if (isset($_SESSION['cartjson'])) {
    $cartData = json_decode($_SESSION['cartjson'], true);
} else {
    header("location: ../index.php");
    exit();
}
if (!validateSessionCart() || !validateSessionMethodData()) {
    header('location: ../cart.php');
    exit();
}

$aggregated_cart = aggregateCart();
$totalPrice = calculateTotalPrice($aggregated_cart, $pdo);
$freeShippingLimit = 1500; 

$percentage = min(100, ($totalPrice / $freeShippingLimit) * 100);
$remaining = $freeShippingLimit - $totalPrice;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name_new'])) { 
    $errors = [];

    $name_new = validate_input($_POST['name_new']);
    $surname_new = validate_input($_POST['surname_new']);
    $email_new = filter_var(trim($_POST['email_new']), FILTER_VALIDATE_EMAIL);
    $phone_new = preg_match('/^[0-9]{9,15}$/', $_POST['phone_new']) ? $_POST['phone_new'] : false;
    $street_new = validate_input($_POST['street_new']);
    $housenumber_new = validate_input($_POST['housenumber_new']);
    $city_new = validate_input($_POST['city_new']);
    $zipcode_new = validate_input($_POST['zipcode_new']);
    $country_new = validate_input($_POST['country']);
    $terms = isset($_POST['conditions']) ? 'yes' : 'no';
    $newsletter = isset($_POST['newsletter_register']) ? 'no' : 'yes';

    if(isset($newsletter) and $newsletter == 'yes'){
        if(isset($email_new)){
            addToNewsletterSubscribers($email_new);
        }
    }

    if (isset($_POST['company']) && $_POST['company'] === 'yes') {
        $ico_new = validate_hidden_input($_POST['ico_new']);
        $dic_new = validate_hidden_input($_POST['dic_new']);
        $companyname_new = validate_hidden_input($_POST['companyname_new']);
        if (!$ico_new || !$dic_new || !$companyname_new) {
            $errors[] = "Údaje o firmě jsou neplatné.";
        }
    }

    if (isset($_POST['another_address']) && $_POST['another_address'] === 'yes') {
        $street_other = validate_hidden_input($_POST['street_other']);
        $housenumber_other = validate_hidden_input($_POST['housenumber_other']);
        $city_other = validate_hidden_input($_POST['city_other']);
        $zipcode_other = validate_hidden_input($_POST['zipcode_other']);
        if (!$street_other || !$housenumber_other || !$city_other || !$zipcode_other) {
            $errors[] = "Doručovací adresa je neplatná.";
        }
    }

    if (!$name_new) $errors[] = "Jméno obsahuje neplatné znaky.";
    if (!$surname_new) $errors[] = "Příjmení obsahuje neplatné znaky.";
    if (!$email_new) $errors[] = "Email není platný.";
    if (!$phone_new) $errors[] = "Telefonní číslo musí obsahovat pouze číslice a mít délku 9 až 15 znaků.";
    if (!$street_new) $errors[] = "Ulice obsahuje neplatné znaky.";
    if (!$housenumber_new) $errors[] = "Číslo popisné obsahuje neplatné znaky.";
    if (!$city_new) $errors[] = "Město obsahuje neplatné znaky.";
    if (!$zipcode_new) $errors[] = "PSČ obsahuje neplatné znaky.";

    if ($terms !== 'yes') {
        $errors[] = "Musíte souhlasit s podmínkami.";
    }

    if (empty($errors)) {
        $_SESSION['name_new'] = $name_new;
        $_SESSION['surname_new'] = $surname_new;
        $_SESSION['email_new'] = $email_new;
        $_SESSION['phone_new'] = $phone_new;
        $_SESSION['street_new'] = $street_new;
        $_SESSION['housenumber_new'] = $housenumber_new;
        $_SESSION['city_new'] = $city_new;
        $_SESSION['zipcode_new'] = $zipcode_new;
        $_SESSION['terms'] = $terms;
        $_SESSION['orderNumber'] = generateOrderNumber($db);

        if (isset($ico_new)) $_SESSION['ico_new'] = $ico_new;
        if (isset($dic_new)) $_SESSION['dic_new'] = $dic_new;
        if (isset($companyname_new)) $_SESSION['companyname_new'] = $companyname_new;

        $user_info = [
            'order_number' => $_SESSION['orderNumber'],
            'name' => $_SESSION['name_new'],
            'surname' => $_SESSION['surname_new'],
            'email' => $_SESSION['email_new'],
            'phone' => $_SESSION['phone_new'],
            'street' => $_SESSION['street_new'],
            'housenumber' => $_SESSION['housenumber_new'],
            'city' => $_SESSION['city_new'],
            'zipcode' => $_SESSION['zipcode_new'],
            'shipping_method' => $_SESSION['methodData']['shipping']['name'],
            'shipping_price' => $_SESSION['methodData']['shipping']['price'],
            'payment_method' => $_SESSION['methodData']['payment']['name'],
            'payment_shortcode' => $_SESSION['methodData']['payment']['shortcode'],
            'payment_price' => $_SESSION['methodData']['payment']['price'],
            'total_price' => $totalPrice,
            'zasilkovna_name' =>$_SESSION['methodData']['shipping']['branch_address'],
            'zasilkovna_branch' => $_SESSION['zasilkovna_branch'],
            'terms' => $_SESSION['terms'],
            'newsletter' => $newsletter,
            'ico' => $_SESSION['ico_new'] ?? null,
            'dic' => $_SESSION['dic_new'] ?? null,
            'companyname' => $_SESSION['companyname_new'] ?? null,
            'country' => $country_new,
            'timestamp' => date('Y-m-d H:i:s')
        ]; 

        $_SESSION['user_info'] = $user_info;
        $_SESSION["success"] = "Všechny údaje byly úspěšně uloženy.";    
        if(isset($_SESSION['methodData']['payment']['shortcode']) and $_SESSION['methodData']['payment']['shortcode'] == 'cod'){
            header('Location: ../redirect.php'); 
            die();
        }else{
            header('Location: summary.php'); 
            die();
        }    
    } else {
        $_SESSION['errors'] = $errors;
    }
}

$bought_items = processBoughtItems($aggregated_cart, $pdo);
$_SESSION['bought_items'] = $bought_items;
header_html($aggregated_cart, "billing_and_address.php", 0);
?>
<div class="container">
    <section class="products-section">
    <h1 class="my-cart">Dodací údaje</h1>
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
                <p class="p-cart p-cart-color">3</p>
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
        <div class="bux border-box">
            <form method="POST" action="" class="info-form">
                <h3>Osobní údaje</h3>
                <input type="text" name="name_new" placeholder="Jméno*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['name_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="surname_new" placeholder="Příjmení*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['surname_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="email" name="email_new" placeholder="Email*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['email_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="search-container log-input">
                    <select name="country_code" class="search-button country" required>
                        <option value="+420" <?php echo (isset($_SESSION['country_code']) && $_SESSION['country_code'] == '+420') ? 'selected' : ''; ?>>+420</option>
                        <!--<option value="+1" <?php echo (isset($_SESSION['country_code']) && $_SESSION['country_code'] == '+1') ? 'selected' : ''; ?>>+1</option>-->
                    </select>
                    <input type="tel" name="phone_new" placeholder="123456789" pattern="[0-9]{9,15}" class="search-input" value="<?php echo htmlspecialchars($_SESSION['phone_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required> <!-- Aktualizovaný pattern -->
                </div>
                <p>*Telefon bez mezer</p>
                <h3>Fakturační adresa</h3>
                <input type="text" name="street_new" placeholder="Ulice*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['street_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="housenumber_new" placeholder="Číslo popisné*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['housenumber_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="city_new" placeholder="Město*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['city_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="zipcode_new" placeholder="PSČ*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['zipcode_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <select name="country" class="log-input" required>
                    <option value="Česká republika" <?php echo (isset($_SESSION['country']) && $_SESSION['country'] == 'Česká republika') ? 'selected' : ''; ?>>Česká republika</option>
                </select>
                <div id="additionalFormSection" style="display: none;">
                    <input type="number" name="ico_new" placeholder="IČO*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['ico_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    <input type="text" name="dic_new" placeholder="DIČ*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['dic_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    <input type="text" name="companyname_new" placeholder="Název společnosti*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['companyname_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="boxes">
                    <div class="background_box box3">
                        <div class="toggle_div">
                            <label class="toggle_box check3">
                                <input type="checkbox" id="company" name="company" value="yes" <?php echo (isset($_POST['company']) && $_POST['company'] === 'yes') ? 'checked' : ''; ?> onclick="toggleFormSection()">
                                <div class="circle circle3"></div>
                            </label>
                            <label for="company">Nakupuji na firmu</label>
                        </div>
                    </div>
                    <div class="background_box box1">
                        <div class="toggle_div">
                            <label class="toggle_box check1">
                                <input type="checkbox" id="newsletter_register" name="newsletter_register">                                
                                <div class="circle circle1"></div>
                            </label>
                            <label for="newsletter_register">Nepřeji si odebírat newsletter</label>
                        </div>
                    </div>
                    <div class="background_box box2">
                        <div class="toggle_div">
                            <label class="toggle_box check2">
                                <input type="checkbox" id="conditions" name="conditions" value="no" <?php echo (isset($conditions) && $conditions === 'yes') ? 'checked' : ''; ?>>
                                <div class="circle circle2"></div>
                            </label>
                            <label for="conditions">Souhlasím s podmínkama</label>
                        </div>
                    </div>
                </div>
                <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php
                    if (isset($_SESSION['errors'])) {
                        foreach ($_SESSION['errors'] as $value) {
                            print("<p class='red'>".$value."</p>");
                        }
                        unset($_SESSION['errors']);
                    }
                    ?>
                </div>
                <?php endif; ?>
                <div>
                    <?php 
                    if (isset($_SESSION["success"])) {
                        print($_SESSION["success"]);
                        unset($_SESSION["success"]);
                    }
                    ?>
                </div>
                <div class="cart-navigation">
                    <div class="cart-navigation-div">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                        </svg>
                        <a href="shipping_and_payment.php">Doprava a platba</a>
                    </div>
                    <div class="cart-navigation-div">
                        <button class="submit-shipping" type="submit">Objednat s povinností platby</button>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                        </svg>
                    </div>
                </div>
            </form>
        </div>
        <div>
            <h2 class="shipping-h2-bil">Souhrn objednávky</h2>
            <div class="section-items-bil">
                <?php
                
                $total_price = 0;
                foreach ($cartData['items'] as $item) {
                    print('<div class="item">');
                    echo '<p>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<p>' . htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') . 'ks</p>';
                    echo '<p>' . htmlspecialchars($item['total_price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>';
                    print('</div>');
                    $total_price += $item['total_price']; 
                }
                print('<div class="item">');
                echo '<p>' . htmlspecialchars($_SESSION['methodData']['shipping']['name'], ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<p></p>';
                echo '<p>' . htmlspecialchars($_SESSION['methodData']['shipping']['price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>';
                print('</div>');
                print('<div class="item">');
                echo '<p>' . htmlspecialchars($_SESSION['methodData']['payment']['name'], ENT_QUOTES, 'UTF-8') . '</p>';
                echo '<p></p>';
                echo '<p>' . htmlspecialchars($_SESSION['methodData']['payment']['price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>';
                print('</div>');
                $total_price += $_SESSION['methodData']['shipping']['price']; 
                $total_price += $_SESSION['methodData']['payment']['price']; 
                print('<h3 class="total">Celkem: '.$total_price.' Kč</h3>');
            print('</div>
        </div>');?>
        <div class="transition-free-bil">
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
    </section>
</div>
<?php footer_html(); ?>
<script src="../js/cart.js"></script>
<script src="../js/toggleboxes.js"></script>
<script src="../js/mobile_without_footer.js"></script>
<script>
    document.getElementById("search-remove")?.remove();
    document.getElementById("left-menu")?.remove();

</script>
</body>
</html>
