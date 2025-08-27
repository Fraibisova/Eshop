<?php

namespace App\Services;

use PDO;

class ValidationService
{
    public static function validateName(string $name, string $fieldName): array
    {
        $errors = [];
        if (empty($name)) {
            $errors[] = $fieldName . " je povinné.";
        } elseif (strpos($name, ' ') !== false) {
            $errors[] = $fieldName . " nesmí obsahovat mezery.";
        } elseif (!preg_match("/^[a-zA-ZáéíóúýčďěňřšťžÁÉÍÓÚÝČĎĚŇŘŠŤŽ]+$/u", $name)) {
            $errors[] = $fieldName . " musí obsahovat pouze písmena.";
        }
        return $errors;
    }

    public static function validateEmail(string $email, PDO $pdo, ?string $oldEmail = null): array
    {
        $errors = [];
        if (empty($email)) {
            $errors[] = "Email je povinný.";
        } elseif (strpos($email, ' ') !== false) {
            $errors[] = "Email nesmí obsahovat mezery.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email není správně napsaný.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                if ($oldEmail != $email) {
                    $errors[] = "Emailová adresa se již používá. Pokud chcete změnit email, použijte prosím jiný.";
                }
            }
        }
        return $errors;
    }

    public static function validatePhone(string $phone, string $countryCode): array
    {
        $errors = [];
        if (empty($phone)) {
            $errors[] = "Telefonní číslo je povinné.";
        } elseif ($countryCode == "+420" && mb_strlen($phone) != 9) {
            $errors[] = "Nemáte správný formát telefonního čísla";
        } elseif (strpos($phone, ' ') !== false) {
            $errors[] = "Telefonní číslo nesmí obsahovat mezery.";
        }
        return $errors;
    }

    public static function validateAddress(string $street, string $number, string $city, string $zipcode, string $country): array
    {
        $errors = [];
        
        if (empty($street)) {
            $errors[] = 'Street is required.';
        } elseif (!preg_match("/^[a-zA-ZáéíóúýčďěňřšťžÁÉÍÓÚÝČĎĚŇŘŠŤŽ]+$/u", $street)) {
            $errors[] = 'Street should not contain numbers or special characters.';
        }

        if (empty($number)) {
            $errors[] = 'House number is required.';
        } elseif (!preg_match('/^\d+$/', $number)) {
            $errors[] = 'House number should contain only numbers.';
        }

        if (empty($city)) {
            $errors[] = 'City is required.';
        } elseif (!preg_match("/^[a-zA-ZáéíóúýčďěňřšťžÁÉÍÓÚÝČĎĚŇŘŠŤŽ]+$/u", $city)) {
            $errors[] = 'City should not contain numbers or special characters.';
        }

        if (empty($zipcode)) {
            $errors[] = 'Zip code is required.';
        } elseif (!preg_match('/^\d{5}$/', $zipcode)) {
            $errors[] = 'Zip code should contain exactly 5 digits.';
        }

        if (empty($country)) {
            $errors[] = 'Country is required.';
        }
        
        return $errors;
    }

    public static function validatePassword(string $password, ?string $password_confirm = null): array
    {
        $errors = [];
        
        if (empty($password)) {
            $errors[] = "Heslo je povinné.";
        } elseif (strpos($password, ' ') !== false) {
            $errors[] = "Heslo nesmí obsahovat mezery.";
        } else {
            if (strlen($password) < 8) {
                $errors[] = "Heslo musí být dlouhé alespoň 8 znaků.";
            }
            if (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
                $errors[] = "Heslo musí obsahovat alespoň 1 velké písmeno, 1 malé písmeno, 1 číslo a 1 speciální znak.";
            }
            if ($password_confirm !== null && $password !== $password_confirm) {
                $errors[] = "Hesla se neshodují.";
            }
        }
        
        return $errors;
    }

    public static function validateInput(string $input): string|false
    {
        $input = trim($input);
        if (preg_match('/^[\p{L}\p{N}\s\-]*$/u', $input)) {
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        } else {
            return false;
        }
    }

    public static function validateHiddenInput(string $input): ?string
    {
        if (!empty($input)) {
            return self::validateInput($input);
        }
        return null; 
    }
}