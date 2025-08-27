<?php

namespace App\Services;

class ConfigurationService
{
    private static ?ConfigurationService $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadConfiguration();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfiguration(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $this->loadEnvFile($envFile);
        }

        $this->config = [
            'app' => [
                'name' => $this->env('APP_NAME', 'Touch the Magic'),
                'url' => $this->env('APP_URL', 'https://touchthemagic.com'),
                'debug' => $this->env('APP_DEBUG', false),
                'timezone' => $this->env('APP_TIMEZONE', 'Europe/Prague')
            ],
            'database' => [
                'host' => $this->env('DB_HOST', 'localhost'),
                'dbname' => $this->env('DB_NAME', 'eshop'),
                'username' => $this->env('DB_USERNAME', 'root'),
                'password' => $this->env('DB_PASSWORD', ''),
                'options' => [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            ],
            'fakturoid' => [
                'account' => $this->env('FAKTUROID_ACCOUNT', ''),
                'client_id' => $this->env('FAKTUROID_CLIENT_ID', ''),
                'client_secret' => $this->env('FAKTUROID_CLIENT_SECRET', ''),
                'redirect_uri' => $this->env('FAKTUROID_REDIRECT_URI', ''),
                'scope' => $this->env('FAKTUROID_SCOPE', 'invoices:read invoices:write'),
            ],
            'zasilkovna' => [
                'api_key' => $this->env('ZASILKOVNA_API_KEY', ''),
                'api_password' => $this->env('ZASILKOVNA_API_PASSWORD', ''),
            ],
            'payment' => [
                'gateway' => $this->env('PAYMENT_GATEWAY', 'gopay'),
                'merchant_id' => $this->env('PAYMENT_MERCHANT_ID', ''),
                'private_key' => $this->env('PAYMENT_PRIVATE_KEY', ''),
                'public_key' => $this->env('PAYMENT_PUBLIC_KEY', ''),
                'test_mode' => $this->env('PAYMENT_TEST_MODE', true),
            ],
            'gopay' => [
                'goid' => $this->env('GOPAY_GOID', ''),
                'client_id' => $this->env('GOPAY_CLIENT_ID', ''),
                'client_secret' => $this->env('GOPAY_CLIENT_SECRET', ''),
                'is_production' => $this->env('GOPAY_IS_PRODUCTION', false),
                'language' => $this->env('GOPAY_LANGUAGE', 'CS'),
            ],
            'mail' => [
                'driver' => $this->env('MAIL_DRIVER', 'smtp'),
                'host' => $this->env('MAIL_HOST', 'smtp.gmail.com'),
                'port' => $this->env('MAIL_PORT', 587),
                'username' => $this->env('MAIL_USERNAME', ''),
                'password' => $this->env('MAIL_PASSWORD', ''),
                'encryption' => $this->env('MAIL_ENCRYPTION', 'tls'),
                'from' => [
                    'address' => $this->env('MAIL_FROM_ADDRESS', ''),
                    'name' => $this->env('MAIL_FROM_NAME', 'Touch the Magic')
                ]
            ],
            'session' => [
                'lifetime' => $this->env('SESSION_LIFETIME', 120),
                'expire_on_close' => $this->env('SESSION_EXPIRE_ON_CLOSE', false),
                'encrypt' => $this->env('SESSION_ENCRYPT', false),
                'cookie' => $this->env('SESSION_COOKIE', 'touchthemagic_session'),
                'domain' => $this->env('SESSION_DOMAIN', null),
            ],
            'recaptcha' => [
                'secret_key' => $this->env('RECAPTCHA_SECRET_KEY', ''),
                'site_key' => $this->env('RECAPTCHA_SITE_KEY', ''),
            ],
            'newsletter' => [
                'secret_key' => $this->env('NEWSLETTER_SECRET_KEY', ''),
            ],
            'analytics' => [
                'google_id' => $this->env('GOOGLE_ANALYTICS_ID', ''),
            ]
        ];
    }

    private function loadEnvFile(string $envFile): void
    {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                $_ENV[$key] = $value;
            }
        }
    }

    private function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $default;
        
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off'])) {
                return false;
            }
        }
        
        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $segment) {
            if (isset($value[$segment])) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }
        
        $config = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function getDatabaseConfig(): array
    {
        return $this->get('database');
    }

    public function getMailConfig(): array
    {
        return $this->get('mail');
    }

    public function getFakturoidConfig(): array
    {
        return $this->get('fakturoid');
    }

    public function getPaymentConfig(): array
    {
        return $this->get('payment');
    }

    public function isDebugMode(): bool
    {
        return $this->get('app.debug', false);
    }

    public function getAppName(): string
    {
        return $this->get('app.name', 'Touch the Magic');
    }

    public function getAppUrl(): string
    {
        return $this->get('app.url', 'https://touchthemagic.com');
    }

    public function getGopayConfig(): array
    {
        return $this->get('gopay');
    }
}