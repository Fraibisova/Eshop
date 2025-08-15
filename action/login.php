<?php
define('APP_ACCESS', true);

include '../config.php';
include '../lib/function.php';
include '../lib/function_action.php';
session_start();
include '../template/template.php';

$errors = [];
$email = "";
$stav = false;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "<p class='red'>Vyplňte prosím celý formulář (Email a heslo).</p>";
    } else {
        $lockout = is_locked_out($pdo, $email);
        if ($lockout['locked']) {
            $errors[] = "<p class='red'>Příliš mnoho pokusů. Zkuste to znovu v " . $lockout['unlock_time'] . "</p>";
        } else {
            $user = authenticateUser($pdo, $email, $password);
            if ($user) {
                log_attempt($pdo, $email, true);
                $stav = true;
                redirectBasedOnRole($user['role_level']);
            } else {
                log_attempt($pdo, $email, false);
                $errors[] = "<p class='red'>Špatný email nebo heslo.</p><p class='red'>Nemáte účet? Zaregistrujte se <a href='register.php'>zde</a></p>";
            }
        }
    }
    setSessionErrors($errors);
}

cleanup_expired_attempts($pdo);

$aggregated_cart = aggregateCart();
$endprice = calculateCartPrice($aggregated_cart, $db);
header_html($aggregated_cart, "login.php");
?>
<div class="container">
    <section class="form-section">
        <div class="background_box border-box">
            <h2 class="register">Přihlášení</h2>
            <p>Vyplněním tohoto formuláře se přihlásíte</p>
            <form method="post" action="" class="login-page-form">
                <input type="text" id="email_login" class="log-input" name="email" placeholder="Email*" autocomplete="off" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="password" id="password_login" class="log-input" name="password" placeholder="Heslo*" autocomplete="off" required>
                <input type="submit" value="Přihlásit se" class="submit-register"> 
                <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php displayErrorMessages(); ?>
                </div>
                <?php endif; ?>
                <div>
                    <?php 
                        if ($stav) {
                            echo "<span class='green'>Úspěšně jste se přihlásili!<a></span>";
                        }
                    ?>
                </div>
            </form>
        </div>
    </section>
    <?php before_footer_html(); ?>
    </div>
</div>
<?php footer_html(); ?>
<script src="../js/mobile.js"></script>
</body>
</html>