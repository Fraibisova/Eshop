<?php

namespace App\Services;

use App\Interfaces\UserServiceInterface;
use App\Models\User;
use PDO;
use Exception;

class UserService implements UserServiceInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function getUserById(int $userId): ?User
    {
        try {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $userData ? new User($userData) : null;
        } catch (\PDOException $e) {
            throw new Exception("Chyba při načítání uživatele: " . $e->getMessage());
        }
    }

    public function getUserByEmail(string $email): ?User
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $userData ? new User($userData) : null;
        } catch (\PDOException $e) {
            throw new Exception("Chyba při načítání uživatele: " . $e->getMessage());
        }
    }

    public function updateUserProfile(int $userId, array $profileData): bool
    {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE users SET 
                    name = :name, 
                    surname = :surname, 
                    email = :email, 
                    country_code = :country_code, 
                    phone = :phone
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $userId,
                'name' => $profileData['name'],
                'surname' => $profileData['surname'],
                'email' => $profileData['email'],
                'country_code' => $profileData['country_code'],
                'phone' => $profileData['phone']
            ]);
            
            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception("Chyba při aktualizaci profilu: " . $e->getMessage());
        }
    }

    public function updateUserBillingAddress(int $userId, array $addressData): bool
    {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE users SET 
                    street = :street, 
                    house_number = :house_number, 
                    city = :city, 
                    zipcode = :zipcode, 
                    country = :country
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $userId,
                'street' => $addressData['street'],
                'house_number' => $addressData['house_number'],
                'city' => $addressData['city'],
                'zipcode' => $addressData['zipcode'],
                'country' => $addressData['country']
            ]);
            
            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception("Chyba při aktualizaci adresy: " . $e->getMessage());
        }
    }

    public function changeUserPassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            $user = $this->getUserById($userId);
            if (!$user || !$user->verifyPassword($currentPassword)) {
                throw new Exception("Původní heslo není správné");
            }
            
            $this->db->beginTransaction();
            
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $userId,
                'password' => $hashedPassword
            ]);
            
            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception("Chyba při změně hesla: " . $e->getMessage());
        }
    }

    public function isEmailTaken(string $email, int $excludeUserId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $params = ['email' => $email];
            
            if ($excludeUserId) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeUserId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            throw new Exception("Chyba při kontrole emailu: " . $e->getMessage());
        }
    }

    public function authenticateUser(string $email, string $password): ?User
    {
        try {
            $user = $this->getUserByEmail($email);
            
            if ($user && $user->verifyPassword($password)) {
                return $user;
            }
            
            return null;
        } catch (Exception $e) {
            throw new Exception("Chyba při autentifikaci: " . $e->getMessage());
        }
    }

    public function checkUserAuthentication(): void
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /auth/login");
            exit();
        }
    }

    public function getUserData(int $userId): ?array
    {
        try {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new Exception("Chyba při načítání uživatelských dat: " . $e->getMessage());
        }
    }

    public function renderAccountNavigation(): string
    {
        return '
        <section class="other-category">
            <div class="other-categories">
                <a href="/account/profile" class="one-other-category bo">
                    <p>Osobní údaje</p>
                </a>
                <a href="/account/billing" class="one-other-category bo">
                    <p>Nastavení fakturační adresy</p>
                </a>
                <a href="/account/password" class="one-other-category bo">
                    <p>Reset hesla</p>
                </a>
                <a href="/auth/logout" class="one-other-category bo">
                    <p>Odhlásit se</p>
                </a>
            </div>
        </section>';
    }

    public function getBillingAddress(int $userId): ?array
    {
        try {
            $sql = "SELECT street, house_number, city, zipcode, country, ico, dic, company_name FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new Exception("Chyba při načítání fakturační adresy: " . $e->getMessage());
        }
    }

    public function updateBillingAddress(int $userId, array $addressData): bool
    {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE users SET 
                    street = :street, 
                    house_number = :house_number, 
                    city = :city, 
                    zipcode = :zipcode, 
                    country = :country,
                    ico = :ico,
                    dic = :dic,
                    company_name = :company_name
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $userId,
                'street' => $addressData['street'],
                'house_number' => $addressData['house_number'],
                'city' => $addressData['city'],
                'zipcode' => $addressData['zipcode'],
                'country' => $addressData['country'],
                'ico' => $addressData['ico'],
                'dic' => $addressData['dic'],
                'company_name' => $addressData['company_name']
            ]);
            
            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception("Chyba při aktualizaci fakturační adresy: " . $e->getMessage());
        }
    }

    public function verifyCurrentPassword(int $userId, string $password): bool
    {
        try {
            $user = $this->getUserById($userId);
            return $user && $user->verifyPassword($password);
        } catch (Exception $e) {
            return false;
        }
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $this->db->beginTransaction();
            
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $userId,
                'password' => $hashedPassword
            ]);
            
            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception("Chyba při aktualizaci hesla: " . $e->getMessage());
        }
    }

    public function updateProfile(int $userId, array $profileData): bool
    {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE users SET 
                    name = :name, 
                    surname = :surname, 
                    email = :email, 
                    phone = :phone
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'id' => $userId,
                'name' => $profileData['name'],
                'surname' => $profileData['surname'],
                'email' => $profileData['email'],
                'phone' => $profileData['phone']
            ]);
            
            $this->db->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new Exception("Chyba při aktualizaci profilu: " . $e->getMessage());
        }
    }
}