<?php

namespace App\Services;

use PDO;
use PDOException;

class DatabaseService
{
    private static ?DatabaseService $instance = null;
    private PDO $connection;
    private array $config;

    private function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }

    public static function getInstance(): DatabaseService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                [$key, $value] = explode('=', $line, 2);
                $_ENV[$key] = $value;
            }
        }

        $this->config = [
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'dbname' => $_ENV['DB_NAME'] ?? 'eshop',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'options' => [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            ]
        ];
    }

    private function connect(): void
    {
        try {
            $dsn = "mysql:host={$this->config['database']['host']};dbname={$this->config['database']['dbname']}";
            $this->connection = new PDO(
                $dsn, 
                $this->config['database']['username'], 
                $this->config['database']['password'], 
                $this->config['database']['options']
            );
        } catch (PDOException $e) {
            throw new PDOException('Connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }

    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}