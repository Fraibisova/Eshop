<?php
define('APP_ACCESS', true);
include '../config.php';
include '../template/template.php';
include '../lib/function.php';
session_start();

checkUserAuthentication();
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $email = $_SESSION['email'];

    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Původní heslo není správné.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Nové heslo a potvrzení hesla se neshodují.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_sql = "UPDATE users SET password = :password WHERE email = :email";
            $update_stmt = $pdo->prepare($update_sql);
            $update_result = $update_stmt->execute(['password' => $hashed_password, 'email' => $email]);

            if ($update_result) {
                $success = "Heslo bylo úspěšně změněno.";
            } else {
                $errors[] = "Chyba při změně hesla.";
            }
        }
    } else {
        $errors[] = "Uživatel nenalezen.";
    }
}

$_SESSION["errors"] = $errors;

$aggregated_cart = aggregateCart();
$endprice = calculateCartPrice($aggregated_cart, $db);

header_html($aggregated_cart, "change_password.php");
?>

<div class="container">
    <section class="account-section">
        <div class="account-section-div">
            <h2>Informace pro Vás</h2>
            <?php echo renderAccountNavigation(); ?>

            <div class="box border-box">
                <h3>Reset hesla</h3>
                <form method="POST" action="change_password.php" class="change-pass-form">
                    <input type="password" name="current_password" placeholder="Původní heslo*" class="log-input" required>
                    <input type="password" name="new_password" placeholder="Nové heslo*" class="log-input" required>
                    <input type="password" name="confirm_password" placeholder="Potvrzení hesla*" class="log-input" required>
                    <input type="submit" value="Změnit heslo" class="submit-register">

                    <?php if (!empty($errors)): ?>
                    <div class="errors">
                        <?php displayErrorMessages(); ?>
                    </div>
                    <?php endif; ?>

                    <div>
                        <?php 
                        if ($success) {
                            echo "<span class='green'>" . htmlspecialchars($success) . "</span>";
                        }
                        ?>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>
<script src="../js/mobile.js"></script>
</body>
</html>
