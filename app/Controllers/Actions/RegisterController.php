<?php

namespace App\Controllers\Actions;

use App\Services\AuthService;
use App\Services\ValidationService;
use App\Services\TemplateService;
use App\Services\ConfigurationService;
use App\Utils\MessageHelper;
use Exception;

class RegisterController
{
    private AuthService $authService;
    private ValidationService $validationService;
    private TemplateService $templateService;
    private ConfigurationService $configService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->validationService = new ValidationService();
        $this->templateService = new TemplateService();
        $this->configService = ConfigurationService::getInstance();
    }

    public function register(): void
    {
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
            $secretKey = $this->configService->get('recaptcha.secret_key'); 

            if (empty($recaptchaResponse)) {
                $errors[] = "Musíte potvrdit, že nejste robot.";
            } else {
                $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$recaptchaResponse}&remoteip=" . $_SERVER['REMOTE_ADDR']);
                $captchaSuccess = json_decode($verify);

                if (!$captchaSuccess->success) {
                    $errors[] = "Ověření reCAPTCHA selhalo. Zkuste to znovu.";
                }
            }

            $pdo = \App\Services\DatabaseService::getInstance()->getConnection();
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
        $db = \App\Services\DatabaseService::getInstance()->getConnection();
        $endprice = calculateCartPrice($aggregated_cart, $db);
        
        $this->renderRegistrationForm($errors, $stav, $name, $surname, $email, $newsletter, $terms, $endprice);
    }

    private function renderRegistrationForm(array $errors, bool $stav, string $name, string $surname, string $email, string $newsletter, string $terms, float $endprice): void
    {
        $aggregated_cart = aggregateCart();
        header_html($aggregated_cart, "login.php");
        
        echo '<div class="container">
            <section class="form-section">
                <div class="background_box border-box">
                    <h2 class="register">Registrace</h2>
                    <p>Vyplněním tohoto formuláře vytvoříte Váš účet</p>
                    <form method="post" action="">
                        <h3>Osobní údaje</h3>
                        <input type="text" id="name_register" class="log-input" name="name_register" placeholder="Jméno*" autocomplete="off" required value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">
                        <input type="text" id="surname_register" class="log-input" name="surname_register" placeholder="Příjmení*" autocomplete="off" required value="' . htmlspecialchars($surname, ENT_QUOTES, 'UTF-8') . '">
                        <input type="text" id="email_register" class="log-input" name="email_register" placeholder="Email*" autocomplete="off" required value="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">
                        <h3>Heslo</h3>
                        <input type="password" id="password_register" class="log-input" name="password_register" placeholder="Heslo*" autocomplete="off" required>
                        <input type="password" id="password_confirm" class="log-input" name="password_confirm" placeholder="Potvrzení hesla*" autocomplete="off" required>
                        <h3>Potvrzení</h3>
                        <div class="background_box box1">
                            <div class="toggle_div">
                                <label class="toggle_box check1">
                                    <input type="checkbox" id="newsletter_register" name="newsletter_register" value="yes" ' . ($newsletter === 'yes' ? 'checked' : '') . '>
                                    <div class="circle circle1"></div>
                                </label>
                                <label for="newsletter_register">Chci dostávat informace o novinkách</label>
                            </div>
                        </div>
                        <div class="background_box box2">
                            <div class="toggle_div">
                                <label class="toggle_box check2">
                                    <input type="checkbox" id="terms" name="terms" value="yes" required ' . ($terms === 'yes' ? 'checked' : '') . '>
                                    <div class="circle circle2"></div>
                                </label>
                                <label for="terms">Souhlasím se zpracováním osobních údajů*</label>
                            </div>
                        </div>

                        <div class="g-recaptcha" data-sitekey="' . $this->configService->get('recaptcha.site_key') . '"></div>

                        <input type="submit" value="Registrovat" class="submit-register">';
        
        if (!empty($errors)) {
            echo '<div class="errors">';
            MessageHelper::displayErrorMessages();
            echo '</div>';
        }
        
        echo '<div>';
        if ($stav) {
            echo "<span class='green'>Úspěšně jste se zaregistrovali! Teď se můžete přihlásit <a href='login.php'>zde</a></span>.";
        }
        echo '</div>
                    </form>
                </div>
            </section>';
            
        before_footer_html();
        echo '</div>';
        footer_html();
        
        echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <script src="../js/mobile.js"></script>
        <script src="../js/toggle_button.js"></script>
        </body></html>';
    }
}