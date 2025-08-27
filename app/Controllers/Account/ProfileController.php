<?php

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Services\UserService;
use App\Services\AccountValidationService;
use App\Services\TemplateService;
use App\Services\CartService;
use App\Interfaces\UserServiceInterface;
use App\Interfaces\CartServiceInterface;
use App\Utils\MessageHelper;
use Exception;

class ProfileController extends BaseController
{
    private UserServiceInterface $userService;
    private AccountValidationService $validationService;
    private TemplateService $templateService;
    private CartServiceInterface $cartService;

    public function __construct(
        UserServiceInterface $userService = null,
        AccountValidationService $validationService = null,
        TemplateService $templateService = null,
        CartServiceInterface $cartService = null
    ) {
        parent::__construct();
        $this->userService = $userService ?? new UserService();
        $this->validationService = $validationService ?? new AccountValidationService();
        $this->templateService = $templateService ?? new TemplateService();
        $this->cartService = $cartService ?? new CartService();
    }

    public function myAccount(): void
    {
        $this->userService->checkUserAuthentication();
        
        $success = '';
        $errors = [];
        $name = '';
        $surname = '';
        $email = '';
        $phone = '*Telefon';
        
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            header('Location: /auth/login');
            exit();
        }
        $user = getUserData($id);
        
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
            $errors = array_merge($errors, validateEmail($email, $GLOBALS['pdo'], $old_email));
            $errors = array_merge($errors, validatePhone($phone, $country_code));

            if (empty($errors)) {
                $sql = "UPDATE users SET name = :name, surname = :surname, email = :email, country_code = :country_code, phone = :phone WHERE id = :id";
                $stmt = $GLOBALS['pdo']->prepare($sql);

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
        
        $aggregated_cart = $this->cartService->aggregateCart();
        $endprice = $this->cartService->calculateCartPrice($aggregated_cart);
        
        $this->renderMyAccountPage($aggregated_cart, $errors, $success);
    }

    public function changePassword(): void
    {
        $this->userService->checkUserAuthentication();
        $errors = [];
        $success = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            $email = $_SESSION['email'];

            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $GLOBALS['pdo']->prepare($sql);
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
                    $update_stmt = $GLOBALS['pdo']->prepare($update_sql);
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

        $aggregated_cart = $this->cartService->aggregateCart();
        $endprice = $this->cartService->calculateCartPrice($aggregated_cart);

        $this->renderPasswordPage($aggregated_cart, $errors, $success);
    }

    public function billingAddress(): void
    {
        $this->userService->checkUserAuthentication();
        
        $success = '';
        $errors = [];
        $company = '';
        $zipcode = '';
        $street = '';
        $number = '';
        $city = '';
        $country = '';

        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            header('Location: /auth/login');
            exit();
        }
        $user = getUserData($id);

        if ($user) {
            $street = $user['street'];
            $city = $user['city'];
            $number = ($user['house_number'] == 0) ? "" : $user['house_number'];
            $zipcode = ($user['zipcode'] == 0) ? "" : $user['zipcode'];
            $country = $user['country'] ?? 'Česká republika';
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
                $stmt = $GLOBALS['pdo']->prepare($sql);

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

        $aggregated_cart = $this->cartService->aggregateCart();
        $endprice = $this->cartService->calculateCartPrice($aggregated_cart);
        
        $this->renderBillingPage($aggregated_cart, $errors, $success);
    }

    private function renderAccountPage($user, array $errors, bool $success): void
    {
        $aggregatedCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $this->templateService->renderHeader($aggregatedCart, "/account/profile");

        echo '<div class="container">
                <section class="account-section">
                    <h1>Můj účet</h1>

                    <nav class="account-nav">
                        <a href="/account/profile" class="active">Profil</a>
                        <a href="/account/password">Změnit heslo</a>
                        <a href="/account/billing">Fakturační adresa</a>
                        <a href="/auth/logout">Odhlásit se</a>
                    </nav>';

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p class="error">' . htmlspecialchars($error) . '</p>';
            }
        }

        if ($success) {
            echo '<p class="success">Profil byl úspěšně aktualizován!</p>';
        }

        echo '<form method="post" class="profile-form">
                <div class="form-group">
                    <label for="name">Jméno:</label>
                    <input type="text" id="name" name="name" value="' . htmlspecialchars($user->getName()) . '" required>
                </div>
                
                <div class="form-group">
                    <label for="surname">Příjmení:</label>
                    <input type="text" id="surname" name="surname" value="' . htmlspecialchars($user->getSurname()) . '" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="' . htmlspecialchars($user->getEmail()) . '" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefon:</label>
                    <input type="tel" id="phone" name="phone" value="' . htmlspecialchars($user->getPhone()) . '">
                </div>
                
                <button type="submit" class="btn-update">Aktualizovat profil</button>
              </form>
              </section>
              </div>';

        $this->templateService->renderFooter();
    }

    private function renderChangePasswordPage(array $errors, bool $success): void
    {
        $aggregatedCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $this->templateService->renderHeader($aggregatedCart, "/account/password");

        echo '<div class="container">
                <section class="account-section">
                    <h1>Změnit heslo</h1>

                    <nav class="account-nav">
                        <a href="/account/profile">Profil</a>
                        <a href="/account/password" class="active">Změnit heslo</a>
                        <a href="/account/billing">Fakturační adresa</a>
                        <a href="/auth/logout">Odhlásit se</a>
                    </nav>';

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p class="error">' . htmlspecialchars($error) . '</p>';
            }
        }

        if ($success) {
            echo '<p class="success">Heslo bylo úspěšně změněno!</p>';
        }

        echo '<form method="post" class="password-form">
                <div class="form-group">
                    <label for="current_password">Současné heslo:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nové heslo:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Potvrzení nového hesla:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn-update">Změnit heslo</button>
              </form>
              </section>
              </div>';

        $this->templateService->renderFooter();
    }

    private function renderBillingAddressPage($address, array $errors, bool $success): void
    {
        $aggregatedCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $this->templateService->renderHeader($aggregatedCart, "/account/billing");

        echo '<div class="container">
                <section class="account-section">
                    <h1>Fakturační adresa</h1>

                    <nav class="account-nav">
                        <a href="/account/profile">Profil</a>
                        <a href="/account/password">Změnit heslo</a>
                        <a href="/account/billing" class="active">Fakturační adresa</a>
                        <a href="/auth/logout">Odhlásit se</a>
                    </nav>';

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p class="error">' . htmlspecialchars($error) . '</p>';
            }
        }

        if ($success) {
            echo '<p class="success">Fakturační adresa byla aktualizována!</p>';
        }

        echo '<form method="post" class="address-form">
                <div class="form-group">
                    <label for="company_name">Název společnosti:</label>
                    <input type="text" id="company_name" name="company_name" value="' . htmlspecialchars($address['company_name'] ?? '') . '">
                </div>
                
                <div class="form-group">
                    <label for="street">Ulice:</label>
                    <input type="text" id="street" name="street" value="' . htmlspecialchars($address['street'] ?? '') . '" required>
                </div>
                
                <div class="form-group">
                    <label for="house_number">Číslo popisné:</label>
                    <input type="text" id="house_number" name="house_number" value="' . htmlspecialchars($address['house_number'] ?? '') . '" required>
                </div>
                
                <div class="form-group">
                    <label for="city">Město:</label>
                    <input type="text" id="city" name="city" value="' . htmlspecialchars($address['city'] ?? '') . '" required>
                </div>
                
                <div class="form-group">
                    <label for="zipcode">PSČ:</label>
                    <input type="text" id="zipcode" name="zipcode" value="' . htmlspecialchars($address['zipcode'] ?? '') . '" required>
                </div>
                
                <div class="form-group">
                    <label for="country">Země:</label>
                    <input type="text" id="country" name="country" value="' . htmlspecialchars($address['country'] ?? 'Česká republika') . '" required>
                </div>
                
                <div class="form-group">
                    <label for="ico">IČO:</label>
                    <input type="text" id="ico" name="ico" value="' . htmlspecialchars($address['ico'] ?? '') . '">
                </div>
                
                <div class="form-group">
                    <label for="dic">DIČ:</label>
                    <input type="text" id="dic" name="dic" value="' . htmlspecialchars($address['dic'] ?? '') . '">
                </div>
                
                <button type="submit" class="btn-update">Uložit adresu</button>
              </form>
              </section>
              </div>';

        $this->templateService->renderFooter();
    }

    private function renderMyAccountPage($aggregated_cart, array $errors, string $success): void
    {
        header_html($aggregated_cart, "/account/profile");
        
        echo '<div class="container">
        <section class="account-section">
            <div class="account-section-div">
                <h2>Informace pro Vás</h2>';
        
        echo renderAccountNavigation();
        
        echo '<div class="box border-box">
                    <h3>Osobní údaje</h3>
                    <form method="POST" action="/account/profile" class="change-pass-form">
                        <input type="text" name="name_new" placeholder="Jméno*" class="log-input" value="' . htmlspecialchars($_SESSION['name_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <input type="text" name="surname_new" placeholder="Příjmení*" class="log-input" value="' . htmlspecialchars($_SESSION['surname_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <input type="text" name="email_new" placeholder="Email*" class="log-input" value="' . htmlspecialchars($_SESSION['email_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <div class="search-container log-input">
                            <select name="country_code" class="search-button country" required>
                                <option value="+420"' . ((isset($_SESSION['country_code']) && $_SESSION['country_code'] == '+420') ? ' selected' : '') . '>+420</option>
                            </select>
                            <input type="tel" name="phone_new" placeholder="123456789" pattern="[0-9]+" class="search-input" value="' . htmlspecialchars($_SESSION['phone_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        </div>
                        <p class="recomend">*Telefon bez mezer</p>
                        <input type="submit" value="Uložit změny" class="submit-register">';
        
        if (!empty($errors)) {
            echo '<div class="errors">';
            MessageHelper::displayErrorMessages();
            echo '</div>';
        }
        
        echo '<div>';
        MessageHelper::displaySuccessMessage();
        echo '</div>
                    </form>
                </div>
            </div>
        </section>';
        
        before_footer_html();
        echo '</div>';
        footer_html();
        echo '<script src="/js/mobile.js"></script>
        </body>
        </html>';
    }

    private function renderBillingPage($aggregated_cart, array $errors, string $success): void
    {
        header_html($aggregated_cart, "/account/billing");
        
        echo '<div class="container">
        <section class="account-section">
            <div class="account-section-div">
                <h2>Informace pro Vás</h2>';
        
        echo renderAccountNavigation();
        
        echo '<div class="box border-box">
                    <h3>Nastavení fakturační adresy</h3>
                    <form method="POST" action="/account/billing" class="change-pass-form">
                        <input type="text" name="street_new" placeholder="Ulice*" class="log-input" value="' . htmlspecialchars($_SESSION['street_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <input type="text" name="housenumber_new" placeholder="Číslo popisné*" class="log-input" value="' . htmlspecialchars($_SESSION['housenumber_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <input type="text" name="city_new" placeholder="Město*" class="log-input" value="' . htmlspecialchars($_SESSION['city_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <input type="text" name="zipcode_new" placeholder="PSČ*" class="log-input" value="' . htmlspecialchars($_SESSION['zipcode_new'] ?? '', ENT_QUOTES, 'UTF-8') . '" required>
                        <select name="country" class="log-input" required>
                            <option value="Česká republika"' . ((isset($_SESSION['country']) && $_SESSION['country'] == 'Česká republika') ? ' selected' : '') . '>Česká republika</option>
                        </select>
                        <input type="submit" value="Uložit změny" class="submit-register">';
        
        if (!empty($errors)) {
            echo '<div class="errors">';
            MessageHelper::displayErrorMessages();
            echo '</div>';
        }
        
        echo '<div>';
        MessageHelper::displaySuccessMessage();
        echo '</div>
                    </form>
                </div>
            </div>
        </section>';
        
        before_footer_html();
        echo '</div>';
        footer_html();
        echo '<script src="/js/toggle_button.js"></script>
        <script src="/js/mobile.js"></script>
        </body>
        </html>';
    }

    private function renderPasswordPage($aggregated_cart, array $errors, string $success): void
    {
        header_html($aggregated_cart, "/account/password");
        
        echo '<div class="container">
        <section class="account-section">
            <div class="account-section-div">
                <h2>Informace pro Vás</h2>';
        
        echo renderAccountNavigation();
        
        echo '<div class="box border-box">
                    <h3>Reset hesla</h3>
                    <form method="POST" action="/account/password" class="change-pass-form">
                        <input type="password" name="current_password" placeholder="Současné heslo*" class="log-input" required>
                        <input type="password" name="new_password" placeholder="Nové heslo*" class="log-input" required>
                        <input type="password" name="confirm_password" placeholder="Potvrzení nového hesla*" class="log-input" required>
                        <input type="submit" value="Změnit heslo" class="submit-register">';
        
        if (!empty($errors)) {
            echo '<div class="errors">';
            MessageHelper::displayErrorMessages();
            echo '</div>';
        }
        
        echo '<div>';
        MessageHelper::displaySuccessMessage();
        echo '</div>
                    </form>
                </div>
            </div>
        </section>';
        
        before_footer_html();
        echo '</div>';
        footer_html();
        echo '<script src="/js/mobile.js"></script>
        </body>
        </html>';
    }
}