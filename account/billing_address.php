<?php
define('APP_ACCESS', true);
include '../config.php';
include '../template/template.php';
include '../lib/function.php';
session_start();

checkUserAuthentication();
$success = '';
$errors = [];
$company = '';
$zipcode = '';
$street = '';
$number = '';
$city = '';
$country = '';

$id = $_SESSION['user_id'];
$user = getUserData($pdo, $id);

if ($user) {
    $street = $user['street'];
    $city = $user['city'];
    $number = ($user['house_number'] == 0) ? "" : $user['house_number'];
    $zipcode = ($user['zipcode'] == 0) ? "" : $user['zipcode'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $street = htmlspecialchars(trim($_POST['street_new']), ENT_QUOTES, 'UTF-8');
    $number = htmlspecialchars(trim($_POST['housenumber_new']), ENT_QUOTES, 'UTF-8');
    $city = htmlspecialchars(trim($_POST['city_new']), ENT_QUOTES, 'UTF-8');
    $zipcode = htmlspecialchars(trim($_POST['zipcode_new']), ENT_QUOTES, 'UTF-8');
    $country = htmlspecialchars(trim($_POST['country']), ENT_QUOTES, 'UTF-8');

    $errors = validateAddress($street, $number, $city, $zipcode, $country);

    if (empty($errors)) {
        $sql = "UPDATE users SET street = :street, house_number = :house_number, city = :city, zipcode = :zipcode, country = :country WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([
                'street' => $street,
                'house_number' => $number,
                'city' => $city,
                'zipcode' => $zipcode,
                'country' => $country,
                'id' => $id
            ]);
            $success = "<span class='green'>Změny byly úspěšně uloženy!</span>";
        } catch (PDOException $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }

    $_SESSION["errors"] = $errors;
    $_SESSION["success"] = $success;
}

$_SESSION["street_new"] = $street;
$_SESSION["housenumber_new"] = $number;
$_SESSION["city_new"] = $city;
$_SESSION["zipcode_new"] = $zipcode;
$_SESSION["country"] = $country;

$aggregated_cart = aggregateCart();
$endprice = calculateCartPrice($aggregated_cart, $db);
header_html($aggregated_cart, "billing_address.php");

?>

<div class="container">
<section class="account-section">
    <div class="account-section-div">
        <h2>Informace pro Vás</h2>
        <?php echo renderAccountNavigation(); ?>
        <div class="box border-box">
            <h3>Nastavení fakturační adresy</h3>
            <form method="POST" action="billing_address.php" class="change-pass-form">
                <input type="text" name="street_new" placeholder="Ulice*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['street_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="housenumber_new" placeholder="Číslo popisné*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['housenumber_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="city_new" placeholder="Město*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['city_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <input type="text" name="zipcode_new" placeholder="PSČ*" class="log-input" value="<?php echo htmlspecialchars($_SESSION['zipcode_new'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <select name="country" class="log-input" required>
                    <option value="Česká republika" <?php echo (isset($_SESSION['country']) && $_SESSION['country'] == 'Česká republika') ? 'selected' : ''; ?>>Česká republika</option>
                </select>
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
<script src="../js/toggle_button.js"></script>
<script src="../js/mobile.js"></script>
</body>
</html>
