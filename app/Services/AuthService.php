<?php

namespace App\Services;

use App\Interfaces\AuthServiceInterface;
use PDO;
use Exception;

class AuthService implements AuthServiceInterface
{
    private PDO $db;

    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minut

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function isLockedOut(string $email): array
    {
        $sql = "SELECT failed_attempts, last_attempt FROM login_attempts WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();

        if ($data) {
            if ($data['failed_attempts'] >= self::MAX_LOGIN_ATTEMPTS && (time() - strtotime($data['last_attempt'])) < self::LOCKOUT_TIME) {
                return [
                    'locked' => true,
                    'unlock_time' => date('H:i:s', strtotime($data['last_attempt']) + self::LOCKOUT_TIME)
                ];
            }
        }
        return ['locked' => false];
    }

    public function logAttempt(string $email, bool $success): void
    {
        $sql = "SELECT * FROM login_attempts WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch();

        if ($success) {
            if ($data) {
                $sql = "DELETE FROM login_attempts WHERE email = :email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email]);
            }
        } else {
            if ($data) {
                $sql = "UPDATE login_attempts SET failed_attempts = failed_attempts + 1, last_attempt = NOW() WHERE email = :email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email]);
            } else {
                $sql = "INSERT INTO login_attempts (email, failed_attempts, last_attempt) VALUES (:email, 1, NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['email' => $email]);
            }
        }
    }

    public function cleanupExpiredAttempts(): void
    {
        $sql = "DELETE FROM login_attempts WHERE TIMESTAMPDIFF(SECOND, last_attempt, NOW()) > :lockout_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['lockout_time' => self::LOCKOUT_TIME]);
    }

    public function validateRegistrationData(string $name, string $surname, string $email, string $password, string $password_confirm, $terms): array
    {
        $errors = [];
        
        $errors = array_merge($errors, ValidationService::validateName($name, "Jméno"));
        $errors = array_merge($errors, ValidationService::validateName($surname, "Příjmení"));
        
        $emailErrors = $this->validateEmailForRegistration($email);
        $errors = array_merge($errors, $emailErrors);
        
        $passwordErrors = ValidationService::validatePassword($password, $password_confirm);
        $errors = array_merge($errors, $passwordErrors);
        
        if (empty($terms)) {
            $errors[] = "Musíte souhlasit s podmínkami.";
        }
        
        return $errors;
    }

    public function validateEmailForRegistration(string $email): array
    {
        $errors = [];
        
        if (empty($email)) {
            $errors[] = "Email je povinný.";
        } elseif (strpos($email, ' ') !== false) {
            $errors[] = "Email nesmí obsahovat mezery.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email není správně napsaný.";
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Tato emailová adresa se již používá. Zvolte jinou nebo obnovte heslo kliknutím <a href='forgot_password.php'>zde</a>";
            }
        }
        
        return $errors;
    }

    public function createUser(string $name, string $surname, string $email, string $password, $newsletter, $terms): bool|string
    {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (name, surname, email, password, newsletter, terms, role_level) VALUES (:name, :surname, :email, :password, :newsletter, :terms, 0)";
        $stmt = $this->db->prepare($sql);
        
        try {
            $stmt->execute([
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password' => $hashed_password,
                'newsletter' => $newsletter,
                'terms' => $terms
            ]);
            return true;
        } catch (\PDOException $e) {
            return "Chyba při registraci: " . $e->getMessage();
        }
    }

    public function authenticateUser(string $email, string $password): ?\App\Models\User
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && password_verify($password, $userData['password'])) {
            return new \App\Models\User($userData);
        }
        
        return null;
    }

    public function generateResetToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function saveResetToken(string $email, string $token): bool
    {
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minut
        
        $sql = "UPDATE users SET reset_token_hash = :token_hash, reset_token_expires_at = :expiry WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'token_hash' => $token_hash,
            'expiry' => $expiry,
            'email' => $email
        ]);
        
        return $stmt->rowCount() > 0;
    }

    public function validateResetToken(string $token): array
    {
        $token_hash = hash("sha256", $token);
        
        $sql = "SELECT * FROM users WHERE reset_token_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['valid' => false, 'message' => 'Neplatný nebo již použitý token.'];
        }
        
        if (strtotime($user["reset_token_expires_at"]) <= time()) {
            return ['valid' => false, 'message' => 'Platnost tokenu vypršela.'];
        }
        
        return ['valid' => true, 'user' => $user];
    }

    public function updatePasswordWithToken(string $token, string $new_password): bool
    {
        $token_hash = hash("sha256", $token);
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        $sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE reset_token_hash = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashed_password, $token_hash]);
    }

    public function redirectBasedOnRole(int $role): void
    {
        if ($role == 10) {
            header("Location: /admin/dashboard");
        } else {
            header("Location: /");
        }
        exit();
    }
}