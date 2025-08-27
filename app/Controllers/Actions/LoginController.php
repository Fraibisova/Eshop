<?php

namespace App\Controllers\Actions;

use App\Services\AuthService;
use App\Services\TemplateService;
use App\Models\User;

class LoginController
{
    private AuthService $authService;
    private TemplateService $templateService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->templateService = new TemplateService();
    }

    public function login(): void
    {
        $errors = [];
        $email = "";
        $success = false;

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
            $password = $_POST['password'];

            if (empty($email) || empty($password)) {
                $errors[] = "Vyplňte prosím celý formulář (Email a heslo).";
            } else {
                $lockout = $this->authService->isLockedOut($email);
                if ($lockout['locked']) {
                    $errors[] = "Příliš mnoho pokusů. Zkuste to znovu v " . $lockout['unlock_time'];
                } else {
                    $user = $this->authService->authenticateUser($email, $password);
                    if ($user) {
                        $this->authService->recordSuccessfulLogin($email);
                        $_SESSION['user_id'] = $user->getId();
                        $_SESSION['email'] = $user->getEmail();
                        $_SESSION['name'] = $user->getName();
                        $_SESSION['role'] = $user->getRole();
                        
                        $success = true;
                        header("Location: /");
                        exit();
                    } else {
                        $this->authService->recordFailedLoginAttempt($email);
                        $errors[] = "Neplatné přihlašovací údaje.";
                    }
                }
            }
        }

        $this->renderLoginForm($errors, $email, $success);
    }

    private function renderLoginForm(array $errors, string $email, bool $success): void
    {
        $aggregatedCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $this->templateService->renderHeader($aggregatedCart, "login.php");

        echo '<div class="container">
                <section class="login-section">
                    <h1>Přihlášení</h1>';

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p class="error">' . htmlspecialchars($error) . '</p>';
            }
        }

        if ($success) {
            echo '<p class="success">Přihlášení bylo úspěšné!</p>';
        }

        echo '<form method="post" class="login-form">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="' . htmlspecialchars($email) . '" required>
                </div>
                <div class="form-group">
                    <label for="password">Heslo:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Přihlásit se</button>
                <p><a href="/auth/forgot-password">Zapomněli jste heslo?</a></p>
                <p><a href="/auth/register">Nemáte účet? Registrujte se</a></p>
              </form>
              </section>
              </div>';

        $this->templateService->renderFooter();
    }
}