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
        'host' => env('DB_HOST', 'host'),
        'dbname' => env('DB_NAME', 'nazev-db'),
        'username' => env('DB_USERNAME', 'username'),
        'password' => env('DB_PASSWORD', 'heslo'),
        'options' => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    ],
    'fakturoid' => [
        'account' => env('FAKTUROID_ACCOUNT', 'nazev-uctu'),
        'client_id' => env('FAKTUROID_CLIENT_ID', 'fakturoid-client-id'),
        'client_secret' => env('FAKTUROID_CLIENT_SECRET', 'fakturoid-client-secret'),
        'redirect_uri' => env('FAKTUROID_REDIRECT_URI', 'redirect-url'),
        'email' => env('FAKTUROID_EMAIL', 'email'),
        'user_agent' => env('FAKTUROID_USER_AGENT', 'user-agent'),
        'auto_send_invoice' => true,
        'payment_method' => 'bank',
        'due_days' => 14,
        'currency' => 'CZK'
    ],
    'gopay' => [
        'goid' => (int)env('GOPAY_GOID', 'goid'),
        'client_id' => (int)env('GOPAY_CLIENT_ID', 'client-id'),
        'client_secret' => env('GOPAY_CLIENT_SECRET', 'client-secret'),
        'gateway_url' => env('GOPAY_GATEWAY_URL', 'gateway-url')
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
