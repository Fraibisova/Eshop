<?php
define('APP_ACCESS', true);

session_start();
require "../config.php";
include '../template/template.php';
include '../lib/function.php';
include '../lib/function_action.php';

if (!isset($_GET['token'])) {
    die("Neplatný požadavek.");
}
$aggregated_cart = aggregateCart();
$token = $_GET['token'];
$tokenValidation = validateResetToken($pdo, $token);
if (!$tokenValidation['valid']) {
    die($tokenValidation['message']);
}
$user = $tokenValidation['user'];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['token'], $_POST['password'], $_POST['password_confirmation'])) {
        die("Neplatný požadavek.");
    }

    $token = $_POST['token'];
    $password = $_POST['password'];
    $password_confirmation = $_POST['password_confirmation'];

    $passwordErrors = validateResetPassword($password, $password_confirmation);
    if (!empty($passwordErrors)) {
        $_SESSION['info'] = implode(' ', $passwordErrors);
    } else {
        if (updatePasswordWithToken($pdo, $token, $password)) {
            $_SESSION['info'] = "Heslo bylo úspěšně změněno.";
        } else {
            $_SESSION['info'] = "Chyba při změně hesla.";
        }
    }
}
header_html($aggregated_cart, "login.php");
?>
<div class="container">
    <section class="form-section">
        <div class="background_box border-box">
            <h2 class="register">Resetování hesla</h2>

            <form method="post" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <input type="password" id="password" placeholder="Nové heslo" name="password" class="log-input" required minlength="8">

                <input type="password" id="password_confirmation" placeholder="Potvrzení hesla" class="log-input" name="password_confirmation" required>

                <input type="submit" class="submit-register" value="Resetovat heslo">
                <?php
                    if(isset($_SESSION['info'])){
                        print('<p>'.$_SESSION['info'].'</p>');
                        unset($_SESSION['info']);
                    }
                ?>
            </form>
        </div>
    </section>
    <?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>

