<?php
define('APP_ACCESS', true);

include '../config.php';
include '../template/template.php';
include '../lib/function.php';
include '../lib/function_action.php';

session_start();
$errors = [];
$stav = false;

$name = '';
$surname = '';
$email = '';
$newsletter = '';
$terms = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name_register'], ENT_QUOTES, 'UTF-8');
    $surname = htmlspecialchars($_POST['surname_register'], ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email_register']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password_register'];
    $password_confirm = $_POST['password_confirm'];
    $newsletter = isset($_POST['newsletter_register']) ? $_POST['newsletter_register'] : 'no';
    $terms = isset($_POST['terms']) ? $_POST['terms'] : '';

    if(isset($newsletter) and $newsletter == 'yes'){
        if(isset($email)){
            addToNewsletterSubscribers($email);
        }
    }
    // reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $secretKey = 'recaptcha-secret-key'; 

    if (empty($recaptchaResponse)) {
        $errors[] = "Musíte potvrdit, že nejste robot.";
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}&remoteip=" . $_SERVER['REMOTE_ADDR']);
        $captchaSuccess = json_decode($verify);

        if (!$captchaSuccess->success) {
            $errors[] = "Ověření reCAPTCHA selhalo. Zkuste to znovu.";
        }
    }

    $errors = validateRegistrationData($name, $surname, $email, $password, $password_confirm, $terms, $pdo);

    if (empty($errors)) {
        $result = createUser($pdo, $name, $surname, $email, $password, $newsletter, $terms);
        if ($result === true) {
            $stav = true;
        } else {
            $errors[] = $result;
        }
    }

    setSessionErrors($errors);
}
$aggregated_cart = aggregateCart();
$endprice = calculateCartPrice($aggregated_cart, $db);
header_html($aggregated_cart, "login.php");
?>

<div class="container">
    <section class="form-section">
        <div class="background_box border-box">
            <h2 class="register">Registrace</h2>
            <p>Vyplněním tohoto formuláře vytvoříte Váš účet</p>
            <form method="post" action="">
                <h3>Osobní údaje</h3>
                <input type="text" id="name_register" class="log-input" name="name_register" placeholder="Jméno*" autocomplete="off" required value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" id="surname_register" class="log-input" name="surname_register" placeholder="Příjmení*" autocomplete="off" required value="<?php echo htmlspecialchars($surname, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="text" id="email_register" class="log-input" name="email_register" placeholder="Email*" autocomplete="off" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                <h3>Heslo</h3>
                <input type="password" id="password_register" class="log-input" name="password_register" placeholder="Heslo*" autocomplete="off" required>
                <input type="password" id="password_confirm" class="log-input" name="password_confirm" placeholder="Potvrzení hesla*" autocomplete="off" required>
                <h3>Potvrzení</h3>
                <div class="background_box box1">
                    <div class="toggle_div">
                        <label class="toggle_box check1">
                            <input type="checkbox" id="newsletter_register" name="newsletter_register" value="yes" <?php echo $newsletter === 'yes' ? 'checked' : ''; ?>>
                            <div class="circle circle1"></div>
                        </label>
                        <label for="newsletter_register">Chci dostávat informace o novinkách</label>
                    </div>
                </div>
                <div class="background_box box2">
                    <div class="toggle_div">
                        <label class="toggle_box check2">
                            <input type="checkbox" id="terms" name="terms" value="yes" required <?php echo $terms === 'yes' ? 'checked' : ''; ?>>
                            <div class="circle circle2"></div>
                        </label>
                        <label for="terms">Souhlasím se zpracováním osobních údajů*</label>
                    </div>
                </div>

                <!-- reCAPTCHA -->
                <div class="g-recaptcha" data-sitekey="site-key"></div>

                <input type="submit" value="Registrovat" class="submit-register"> 
                <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php displayErrorMessages(); ?>
                </div>
                <?php endif; ?>
                <div>
                    <?php 
                        if ($stav) {
                            echo "<span class='green'>Úspěšně jste se zaregistrovali! Teď se můžete přihlásit <a href='login.php'>zde</a></span>.";
                        }
                    ?>
                </div>
            </form>
        </div>
    </section>
    <?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script src="../js/mobile.js"></script>
<script src="../js/toggle_button.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var zasilkovnaRadio = document.getElementById('zasilkovna');
    var totalPriceElement = document.getElementById('totalPrice');
    
    var basePrice = <?php echo $endprice; ?>;
    var shippingCost = 79;

    function updatePrice() {
        if (zasilkovnaRadio && zasilkovnaRadio.checked) {
            totalPriceElement.innerHTML = 'Celková cena: ' + (basePrice + shippingCost) + ' Kč';
        } else {
            totalPriceElement.innerHTML = 'Celková cena: ' + basePrice + ' Kč';
        }
    }

    document.querySelectorAll('input[name="shipping"]').forEach(function(radio) {
        radio.addEventListener('change', updatePrice);
    });
});
</script>

</body>
</html>
