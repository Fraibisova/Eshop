<?php

namespace App\Controllers;

use App\Services\DatabaseService;
use App\Services\ConfigurationService;
use App\Utils\MessageHelper;

abstract class BaseController
{
    protected \PDO $db;
    protected ConfigurationService $config;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
        $this->config = ConfigurationService::getInstance();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function redirect(string $location): void
    {
        header("Location: $location");
        exit();
    }

    protected function redirectWithMessage(string $location, string $message, string $type = 'success'): void
    {
        $_SESSION[$type] = $message;
        $this->redirect($location);
    }

    protected function validateCSRF(): bool
    {
        return isset($_POST['csrf_token']) && 
               isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }

    protected function generateCSRF(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    protected function handleError(\Exception $e, string $default_message = 'Došlo k neočekávané chybě.'): void
    {
        error_log($e->getMessage());
        MessageHelper::setSessionErrors([$default_message]);
    }

    protected function validateRequired(array $fields, array $data): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Pole '$field' je povinné.";
            }
        }
        return $errors;
    }

    protected function sanitizeInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}