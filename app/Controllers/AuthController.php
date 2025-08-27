<?php

namespace App\Controllers;

use App\Interfaces\AuthServiceInterface;
use App\Interfaces\EmailServiceInterface;
use App\Services\ValidationService;
use Exception;

class AuthController extends BaseController
{
    private AuthServiceInterface $authService;
    private EmailServiceInterface $emailService;

    public function __construct(AuthServiceInterface $authService, EmailServiceInterface $emailService)
    {
        parent::__construct();
        $this->authService = $authService;
        $this->emailService = $emailService;
    }

    public function login(): void
    {
        $errors = [];
        $email = "";
        $stav = false;

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
            $password = $_POST['password'];

            if (empty($email) || empty($password)) {
                $errors[] = "<p class='red'>Vyplňte prosím celý formulář (Email a heslo).</p>";
            } else {
                $lockout = is_locked_out(getPdo(), $email);
                if ($lockout['locked']) {
                    $errors[] = "<p class='red'>Příliš mnoho pokusů. Zkuste to znovu v " . $lockout['unlock_time'] . "</p>";
                } else {
                    $user = authenticateUser(getPdo(), $email, $password);
                    if ($user) {
                        log_attempt(getPdo(), $email, true);
                        $stav = true;
                        
                        $_SESSION['user_id'] = $user->getId();
                        $_SESSION['role'] = $user->getRole();
                        $_SESSION['user_name'] = $user->getName();
                        $_SESSION['user_email'] = $user->getEmail();
                        
                        redirectBasedOnRole($user->getRole());
                    } else {
                        log_attempt(getPdo(), $email, false);
                        $errors[] = "<p class='red'>Špatný email nebo heslo.</p><p class='red'>Nemáte účet? Zaregistrujte se <a href='/auth/register'>zde</a></p>";
                    }
                }
            }
            setSessionErrors($errors);
        }

        cleanup_expired_attempts(getPdo());

        $aggregated_cart = aggregateCart();
        $endprice = calculateCartPrice($aggregated_cart, getDb());
        
        $this->renderLoginPage($aggregated_cart, $errors, $email, $stav);
    }
    
    public function loginApi(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $email = $this->sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Vyplňte prosím celý formulář (Email a heslo).'];
        }

        try {
            $lockout = $this->authService->isLockedOut($email);
            if ($lockout['locked']) {
                return ['success' => false, 'message' => 'Příliš mnoho pokusů. Zkuste to znovu v ' . $lockout['unlock_time']];
            }

            $user = $this->authService->authenticateUser($email, $password);
            if ($user) {
                $this->authService->logAttempt($email, true);
                return [
                    'success' => true, 
                    'message' => 'Přihlášení úspěšné',
                    'redirect' => $this->getRedirectUrl($user['role_level'])
                ];
            } else {
                $this->authService->logAttempt($email, false);
                return ['success' => false, 'message' => 'Nesprávný email nebo heslo.'];
            }

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => 'Došlo k chybě při přihlašování.'];
        }
    }

    public function register(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $data = [
            'name' => $this->sanitizeInput($_POST['name'] ?? ''),
            'surname' => $this->sanitizeInput($_POST['surname'] ?? ''),
            'email' => $this->sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'newsletter' => isset($_POST['newsletter']) ? 1 : 0,
            'terms' => isset($_POST['terms']) ? 1 : 0
        ];

        try {
            $errors = $this->authService->validateRegistrationData(
                $data['name'], 
                $data['surname'], 
                $data['email'], 
                $data['password'], 
                $data['password_confirm'], 
                $data['terms']
            );

            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }

            $result = $this->authService->createUser(
                $data['name'],
                $data['surname'], 
                $data['email'], 
                $data['password'], 
                $data['newsletter'], 
                $data['terms']
            );

            if ($result === true) {
                return ['success' => true, 'message' => 'Registrace byla úspešná. Můžete se nyní přihlásit.'];
            } else {
                return ['success' => false, 'message' => $result];
            }

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => 'Došlo k chybě při registraci.'];
        }
    }

    public function forgotPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->showForgotPasswordForm();
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auth/forgot-password');
            return;
        }

        $email = $this->sanitizeInput($_POST['email'] ?? '');

        if (empty($email)) {
            $_SESSION['email'] = 'Zadejte prosím emailovou adresu.';
            $this->redirect('/auth/forgot-password');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['email'] = 'Zadejte prosím platnou emailovou adresu.';
            $this->redirect('/auth/forgot-password');
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user) {
                $_SESSION['email'] = 'Uživatel s touto emailovou adresou neexistuje.';
                $this->redirect('/auth/forgot-password');
                return;
            }

            $token = $this->authService->generateResetToken();
            $saved = $this->authService->saveResetToken($email, $token);

            if ($saved) {
                $subject = "Reset hesla - Touch The Magic";
                $body = $this->getPasswordResetEmail($token);
                
                $emailResult = $this->emailService->sendEmail($email, $subject, $body);
                
                if ($emailResult['success']) {
                    $_SESSION['email'] = 'Odkaz pro reset hesla byl odeslán na váš email.';
                } else {
                    $_SESSION['email'] = 'Chyba při odesílání emailu.';
                }
            } else {
                $_SESSION['email'] = 'Chyba při generování reset tokenu.';
            }

            $this->redirect('/auth/forgot-password');

        } catch (Exception $e) {
            $this->handleError($e);
            $_SESSION['email'] = 'Došlo k chybě při zpracování požadavku.';
            $this->redirect('/auth/forgot-password');
        }
    }

    public function resetPassword(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($password_confirm)) {
            return ['success' => false, 'message' => 'Všechna pole jsou povinná.'];
        }

        try {
            $tokenValidation = $this->authService->validateResetToken($token);
            if (!$tokenValidation['valid']) {
                return ['success' => false, 'message' => $tokenValidation['message']];
            }

            $passwordErrors = ValidationService::validatePassword($password, $password_confirm);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'errors' => $passwordErrors];
            }

            $updated = $this->authService->updatePasswordWithToken($token, $password);
            
            if ($updated) {
                return ['success' => true, 'message' => 'Heslo bylo úspěšně změněno. Můžete se nyní přihlásit.'];
            } else {
                return ['success' => false, 'message' => 'Chyba při změně hesla.'];
            }

        } catch (Exception $e) {
            $this->handleError($e);
            return ['success' => false, 'message' => 'Došlo k chybě při změně hesla.'];
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        $this->redirect('/');
    }

    private function getRedirectUrl(int $roleLevel): string
    {
        return ($roleLevel == 10) ? '/admin/dashboard' : '/';
    }

    private function showForgotPasswordForm(): void
    {
        $aggregated_cart = aggregateCart();
        $totalPrice = calculateCartPrice($aggregated_cart, $this->db);
        $freeShippingData = calculateFreeShippingProgress($totalPrice);
        
        header_html($aggregated_cart, "forgot-password");
        
        echo '<div class="container">
            <section class="form-section">
                <div class="background_box border-box">
                    <h2 class="register">Reset hesla</h2>

                    <form method="post" action="/auth/forgot-password">
                        
                        <input type="email" class="log-input" name="email" id="email" placeholder="Zadejte email" required>

                        <input type="submit" value="Odeslat" class="submit-register">
                        ';
        
        if(isset($_SESSION['email'])){
            echo '<p>'.htmlspecialchars($_SESSION['email']).'</p>';
            unset($_SESSION['email']);
        }
        
        echo '
                    </form>
                </div>
            </section>';
            
        before_footer_html();
        echo '</div>';
        footer_html();
    }

    private function getPasswordResetEmail(string $token): string
    {
        return "
            <h2>Reset hesla</h2>
            <p>Pro resetování hesla klikněte na následující odkaz:</p>
            <p><a href='https://touchthemagic.com/action/reset_password.php?token=$token'>Resetovat heslo</a></p>
            <p>Pokud jste o reset hesla nežádali, ignorujte tento email.</p>
            <p>Odkaz je platný 30 minut.</p>
        ";
    }
    
    private function renderLoginPage($aggregated_cart, array $errors, string $email, bool $stav): void
    {
        header_html($aggregated_cart, "/auth/login");
        
        echo '<div class="container">
    <section class="form-section">
        <div class="background_box border-box">
            <h2 class="register">Přihlášení</h2>
            <p>Vyplněním tohoto formuláře se přihlásíte</p>
            <form method="post" action="/auth/login" class="login-page-form">
                <input type="text" id="email_login" class="log-input" name="email" placeholder="Email*" autocomplete="off" required value="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">
                <input type="password" id="password_login" class="log-input" name="password" placeholder="Heslo*" autocomplete="off" required>
                <input type="submit" value="Přihlásit se" class="submit-register">';
        
        if (!empty($errors)) {
            echo '<div class="errors">';
            displayErrorMessages();
            echo '</div>';
        }
        
        echo '<div>';
        if ($stav) {
            echo "<span class='green'>Úspěšně jste se přihlásili!</span>";
        }
        echo '</div>
            </form>
        </div>
    </section>';
    
        before_footer_html();
        echo '</div>
</div>';
        footer_html();
        echo '<script src="/js/mobile.js"></script>
</body>
</html>';
    }
}