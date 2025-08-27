<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Services\AccountValidationService;
use App\Models\User;
use Exception;

class AccountController
{
    private UserService $userService;
    private AccountValidationService $validationService;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->validationService = new AccountValidationService();
    }

    public function checkAuthentication(): void
    {
        if (!$this->validationService->validateUserAuthentication()) {
            header('Location: ../action/login.php');
            exit();
        }
    }

    public function showProfile(): array
    {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user_id'];
        $user = $this->userService->getUserById($userId);
        
        if (!$user) {
            throw new Exception("Uživatel nenalezen");
        }

        return [
            'user' => $user,
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
            'form_data' => [
                'name' => $_SESSION['name_new'] ?? $user->getName(),
                'surname' => $_SESSION['surname_new'] ?? $user->getSurname(),
                'email' => $_SESSION['email_new'] ?? $user->getEmail(),
                'phone' => $_SESSION['phone_new'] ?? $user->getPhone(),
                'country_code' => $_SESSION['country_code'] ?? $user->getCountryCode()
            ]
        ];
    }

    public function updateProfile(array $postData): array
    {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user_id'];
        $user = $this->userService->getUserById($userId);
        
        if (!$user) {
            throw new Exception("Uživatel nenalezen");
        }

        $sanitizedData = $this->validationService->sanitizeInput($postData);
        
        $errors = $this->validationService->validateProfileData($sanitizedData, $userId);

        if (empty($errors)) {
            try {
                $profileData = [
                    'name' => $sanitizedData['name_new'],
                    'surname' => $sanitizedData['surname_new'],
                    'email' => $sanitizedData['email_new'],
                    'country_code' => $sanitizedData['country_code'],
                    'phone' => $sanitizedData['phone_new']
                ];

                $success = $this->userService->updateUserProfile($userId, $profileData);
                
                if ($success) {
                    $_SESSION['email'] = $sanitizedData['email_new'];
                    $_SESSION['success'] = "Změny byly úspěšně uloženy!";
                    $_SESSION['errors'] = [];
                } else {
                    $_SESSION['errors'] = ["Došlo k chybě při ukládání změn"];
                }
            } catch (Exception $e) {
                $_SESSION['errors'] = [$e->getMessage()];
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        $_SESSION['name_new'] = $sanitizedData['name_new'] ?? '';
        $_SESSION['surname_new'] = $sanitizedData['surname_new'] ?? '';
        $_SESSION['email_new'] = $sanitizedData['email_new'] ?? '';
        $_SESSION['phone_new'] = $sanitizedData['phone_new'] ?? '';
        $_SESSION['country_code'] = $sanitizedData['country_code'] ?? '+420';

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    public function showBillingAddress(): array
    {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user_id'];
        $user = $this->userService->getUserById($userId);
        
        if (!$user) {
            throw new Exception("Uživatel nenalezen");
        }

        return [
            'user' => $user,
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? '',
            'form_data' => [
                'street' => $_SESSION['street_new'] ?? $user->getStreet(),
                'house_number' => $_SESSION['housenumber_new'] ?? $user->getHouseNumber(),
                'city' => $_SESSION['city_new'] ?? $user->getCity(),
                'zipcode' => $_SESSION['zipcode_new'] ?? $user->getZipcode(),
                'country' => $_SESSION['country'] ?? $user->getCountry()
            ]
        ];
    }

    public function updateBillingAddress(array $postData): array
    {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user_id'];
        
        $sanitizedData = $this->validationService->sanitizeInput($postData);
        
        $errors = $this->validationService->validateAddressData($sanitizedData);

        if (empty($errors)) {
            try {
                $addressData = [
                    'street' => $sanitizedData['street_new'],
                    'house_number' => $sanitizedData['housenumber_new'],
                    'city' => $sanitizedData['city_new'],
                    'zipcode' => $sanitizedData['zipcode_new'],
                    'country' => $sanitizedData['country']
                ];

                $success = $this->userService->updateUserBillingAddress($userId, $addressData);
                
                if ($success) {
                    $_SESSION['success'] = "Změny byly úspěšně uloženy!";
                    $_SESSION['errors'] = [];
                } else {
                    $_SESSION['errors'] = ["Došlo k chybě při ukládání změn"];
                }
            } catch (Exception $e) {
                $_SESSION['errors'] = [$e->getMessage()];
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        $_SESSION['street_new'] = $sanitizedData['street_new'] ?? '';
        $_SESSION['housenumber_new'] = $sanitizedData['housenumber_new'] ?? '';
        $_SESSION['city_new'] = $sanitizedData['city_new'] ?? '';
        $_SESSION['zipcode_new'] = $sanitizedData['zipcode_new'] ?? '';
        $_SESSION['country'] = $sanitizedData['country'] ?? 'Česká republika';

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    public function changePassword(array $postData): array
    {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user_id'];
        
        $errors = $this->validationService->validatePasswordChange($postData);

        if (empty($errors)) {
            try {
                $success = $this->userService->changeUserPassword(
                    $userId,
                    $postData['current_password'],
                    $postData['new_password']
                );
                
                if ($success) {
                    $_SESSION['success'] = "Heslo bylo úspěšně změněno!";
                    $_SESSION['errors'] = [];
                } else {
                    $_SESSION['errors'] = ["Došlo k chybě při změně hesla"];
                }
            } catch (Exception $e) {
                $_SESSION['errors'] = [$e->getMessage()];
            }
        } else {
            $_SESSION['errors'] = $errors;
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->validationService->validateUserAuthentication()) {
            return null;
        }

        return $this->userService->getUserById($_SESSION['user_id']);
    }

    public function clearFormData(): void
    {
        $formFields = [
            'name_new', 'surname_new', 'email_new', 'phone_new', 'country_code',
            'street_new', 'housenumber_new', 'city_new', 'zipcode_new', 'country',
            'errors', 'success'
        ];

        foreach ($formFields as $field) {
            unset($_SESSION[$field]);
        }
    }
}