<?php
include "lib/admin_functions.php";


$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; 
        [$key, $value] = explode('=', $line, 2);
        $_ENV[$key] = $value;
    }
}

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

$config = [
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'dbname' => env('DB_NAME', 'eshop'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'options' => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ],
    'fakturoid' => [
        'account' => env('FAKTUROID_ACCOUNT', 'beatafraibisova'),
        'client_id' => env('FAKTUROID_CLIENT_ID', 'aea547926c65578c3d4e3aa3b1c8dc4dd067cc02'),
        'client_secret' => env('FAKTUROID_CLIENT_SECRET', 'd18ce0793c45b8c4633cc1eb6d2085cc97a1f54f'),
        'redirect_uri' => env('FAKTUROID_REDIRECT_URI', 'https://touchthemagic.com/oauth/callback.php'),
        'email' => env('FAKTUROID_EMAIL', 'fraibisovab@gmail.com'),
        'user_agent' => env('FAKTUROID_USER_AGENT', 'info@touchthemagic.com'),
        'auto_send_invoice' => true,
        'payment_method' => 'bank',
        'due_days' => 14,
        'currency' => 'CZK'
    ],
    'gopay' => [
        'goid' => (int)env('GOPAY_GOID', 8308320361),
        'client_id' => (int)env('GOPAY_CLIENT_ID', 1763284732),
        'client_secret' => env('GOPAY_CLIENT_SECRET', 'pdDA9rd3'),
        'gateway_url' => env('GOPAY_GATEWAY_URL', 'https://gate.gopay.cz/api')
    ]
];

try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);
    
    global $db;
    $db = $pdo;
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}


if (!function_exists('getConfig')) {
    function getConfig($key = null) {
        global $config;
        if ($key === null) {
            return $config;
        }
        return isset($config[$key]) ? $config[$key] : null;
    }
}

?>
