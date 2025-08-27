<?php

namespace App\Services;

class AccountValidationService
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function validateProfileData(array $data, int $excludeUserId = null): array
    {
        $errors = [];

        $errors = array_merge($errors, $this->validateName($data['name'] ?? '', "Jméno"));
        
        $errors = array_merge($errors, $this->validateName($data['surname'] ?? '', "Příjmení"));
        
        $errors = array_merge($errors, $this->validateEmail($data['email'] ?? '', $excludeUserId));
        
        $errors = array_merge($errors, $this->validatePhone($data['phone'] ?? '', $data['country_code'] ?? '+420'));

        return $errors;
    }

    public function validateAddressData(array $data): array
    {
        $errors = [];

        if (empty(trim($data['street'] ?? ''))) {
            $errors[] = "Ulice je povinná";
        }

        if (empty(trim($data['house_number'] ?? ''))) {
            $errors[] = "Číslo popisné je povinné";
        }

        if (empty(trim($data['city'] ?? ''))) {
            $errors[] = "Město je povinné";
        }

        $zipcode = trim($data['zipcode'] ?? '');
        if (empty($zipcode)) {
            $errors[] = "PSČ je povinné";
        } elseif (!$this->isValidZipcode($zipcode)) {
            $errors[] = "PSČ má neplatný formát";
        }

        if (empty(trim($data['country'] ?? ''))) {
            $errors[] = "Země je povinná";
        }

        return $errors;
    }

    public function validatePasswordChange(array $data): array
    {
        $errors = [];

        if (empty($data['current_password'] ?? '')) {
            $errors[] = "Původní heslo je povinné";
        }

        if (empty($data['new_password'] ?? '')) {
            $errors[] = "Nové heslo je povinné";
        }

        if (empty($data['confirm_password'] ?? '')) {
            $errors[] = "Potvrzení hesla je povinné";
        }

        if (!empty($data['new_password']) && !empty($data['confirm_password'])) {
            if ($data['new_password'] !== $data['confirm_password']) {
                $errors[] = "Nové heslo a potvrzení hesla se neshodují";
            }

            if (strlen($data['new_password']) < 6) {
                $errors[] = "Heslo musí mít alespoň 6 znaků";
            }
        }

        return $errors;
    }

    private function validateName(string $name, string $fieldName): array
    {
        $errors = [];
        $name = trim($name);

        if (empty($name)) {
            $errors[] = "$fieldName je povinné";
        } elseif (strlen($name) < 2) {
            $errors[] = "$fieldName musí mít alespoň 2 znaky";
        } elseif (strlen($name) > 50) {
            $errors[] = "$fieldName může mít maximálně 50 znaků";
        } elseif (!preg_match('/^[a-zA-ZáčďéěíňóřšťúůýžÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ\s-]+$/u', $name)) {
            $errors[] = "$fieldName může obsahovat pouze písmena, mezery a pomlčky";
        }

        return $errors;
    }

    private function validateEmail(string $email, int $excludeUserId = null): array
    {
        $errors = [];
        $email = trim($email);

        if (empty($email)) {
            $errors[] = "Email je povinný";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email má neplatný formát";
        } elseif (strlen($email) > 255) {
            $errors[] = "Email může mít maximálně 255 znaků";
        } elseif ($this->userService->isEmailTaken($email, $excludeUserId)) {
            $errors[] = "Tento email je již použit jiným uživatelem";
        }

        return $errors;
    }

    private function validatePhone(string $phone, string $countryCode): array
    {
        $errors = [];
        $phone = trim($phone);

        if (empty($phone)) {
            $errors[] = "Telefon je povinný";
        } elseif (!preg_match('/^[0-9]{9}$/', $phone)) {
            $errors[] = "Telefon musí obsahovat přesně 9 číslic";
        }

        if (!in_array($countryCode, ['+420'])) {
            $errors[] = "Neplatný kód země";
        }

        return $errors;
    }

    private function isValidZipcode(string $zipcode): bool
    {
        return preg_match('/^[0-9]{5}$/', str_replace(' ', '', $zipcode));
    }

    public function sanitizeInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }

        if (isset($sanitized['email'])) {
            $sanitized['email'] = filter_var($sanitized['email'], FILTER_SANITIZE_EMAIL);
        }

        return $sanitized;
    }

    public function validateUserAuthentication(): bool
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['email']) && 
               !empty($_SESSION['user_id']) && 
               !empty($_SESSION['email']);
    }
}