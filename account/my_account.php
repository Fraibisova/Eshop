<?php
    define('APP_ACCESS', true);
    include '../config.php';
    include '../template/template.php';
    include '../lib/function.php';
    session_start();
    
    checkUserAuthentication();
    $success = '';
    $errors = [];
    $name = '';
    $surname = '';
    $email = '';
    $phone = '*Telefon';
    
    $id = $_SESSION['user_id'];
    $user = getUserData($pdo, $id);
    
    if ($user) {
        $name = $user['name'];
        $surname = $user['surname'];
        $email = $user['email'];
        $phone = ($user['phone'] == 0) ? "" : $user['phone'];
        $old_email = $user['email'];
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = htmlspecialchars($_POST['name_new'], ENT_QUOTES, 'UTF-8');
        $surname = htmlspecialchars($_POST['surname_new'], ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email_new']), FILTER_SANITIZE_EMAIL);
        $country_code = trim($_POST['country_code']);
        $phone = trim($_POST['phone_new']);


        $errors = array_merge($errors, validateName($name, "Jméno"));
        $errors = array_merge($errors, validateName($surname, "Příjmení"));

        $errors = array_merge($errors, validateEmail($email, $pdo, $old_email));
        $errors = array_merge($errors, validatePhone($phone, $country_code));

        if (empty($errors)) {
            $sql = "UPDATE users SET name = :name, surname = :surname, email = :email, country_code = :country_code, phone = :phone WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            try {
                $stmt->execute([
                    'name' => $name,
                    'surname' => $surname,
                    'email' => $email,
                    'phone' => $phone, 
                    'country_code' => $country_code, 
                    'id' => $id
                ]);
                $success = "<span class='green'>Změny byly úspěšně uloženy!</span>";
            } catch (PDOException $e) {
                $errors[] = "Došlo k chybě: " . $e->getMessage();
            }
        }

        $_SESSION["errors"] = $errors;
        $_SESSION["success"] = $success;
    }
    $_SESSION["name_new"] = $name;
    $_SESSION["surname_new"] = $surname;
    $_SESSION["email_new"] = $email;
    $_SESSION["phone_new"] = $phone;
    
    $aggregated_cart = aggregateCart();
    $endprice = calculateCartPrice($aggregated_cart, $db);
    header_html($aggregated_cart, "my_account.php");
?>
<div class="container">
<section class="account-section">
    <div class="account-section-div">
        <h2>Informace pro Vás</h2>
        <?php echo renderAccountNavigation(); ?>
        <div class="box border-box">
            <h3>Osobní údaje</h3>
            <form method="POST" action="my_account.php" class="change-pass-form">
                <input type="text" name="name_new" placeholder="Jméno*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['name_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="surname_new" placeholder="Příjmení*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['surname_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="email_new" placeholder="Email*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['email_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <div class="search-container log-input">
                    <select name="country_code" class="search-button country" required>
                        <option value="+420" <?php echo (isset($_SESSION['country_code']) && $_SESSION['country_code'] == '+420') ? 'selected' : ''; ?>>+420</option>
                        <!--<option value="+1" <?php echo (isset($_SESSION['country_code']) && $_SESSION['country_code'] == '+1') ? 'selected' : ''; ?>>+1</option>-->
                        <!-- Add more country codes as needed -->
                    </select>
                    <input type="tel" name="phone_new" placeholder="123456789" pattern="[0-9]+" class="search-input" value="<?php echo htmlspecialchars($_SESSION['phone_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <p class='recomend'>*Telefon bez mezer</p>
                <input type="submit" value="Uložit změny" class="submit-register">
                <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php displayErrorMessages(); ?>
                </div>
                <?php endif; ?>
                <div>
                    <?php displaySuccessMessage(); ?>
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
