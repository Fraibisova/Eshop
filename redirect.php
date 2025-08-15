<?php 
define('APP_ACCESS', true);

session_start();
include "config.php";
include "lib/function.php";

if(!isset($_SESSION['user_info']) OR !isset($_SESSION['cart'])){
    header('location: ../cart.php');
    exit();
}

if (isset($_SESSION['order_submitted']) && $_SESSION['order_submitted'] === true) {
    header("location: fakturoid_service.php");
    exit();
}

$fakturoidConfig = getConfig('fakturoid');

$client_id = $fakturoidConfig['client_id'];
$client_secret = $fakturoidConfig['client_secret'];
$stmt = $pdo->prepare("SELECT * FROM oauth_tokens WHERE service = :service LIMIT 1");
$stmt->execute(['service' => 'fakturoid']);
$tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenRow) {
    die('Není uložený refresh token!');
}

$refresh_token = $tokenRow['refresh_token'];
$url = 'https://app.fakturoid.cz/api/v3/oauth/token';

$basic_auth = base64_encode("$client_id:$client_secret");

$data = [
    'grant_type' => 'refresh_token',
    'refresh_token' => $refresh_token
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $basic_auth,
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json',
    'User-Agent: TvujNazevAplikace (tvuj@email.cz)'
]);

$response = curl_exec($ch);

if ($response === false) {
    die('Chyba cURL: ' . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    $newAccessToken = $result['access_token'];
    $newRefreshToken = $result['refresh_token'] ?? $refresh_token;

    $expiresIn = $result['expires_in'] ?? 3600;
    $newExpiresAt = time() + $expiresIn;

    $updateStmt = $pdo->prepare("
        UPDATE oauth_tokens 
        SET 
            access_token = :access_token,
            refresh_token = :refresh_token,
            expires_at = :expires_at,
            updated_at = NOW()
        WHERE 
            service = :service
    ");

    $updateStmt->execute([
        'access_token' => $newAccessToken,
        'refresh_token' => $newRefreshToken,
        'expires_at' => $newExpiresAt,
        'service' => 'fakturoid'
    ]);
    
    $aggregated_cart = aggregateCart();
    
    $orderNumber = generateNextOrderNumber($pdo);
    $totalPrice = 0;
    
    if (!validateOrderNumber($pdo, $orderNumber)) {
        exit();
    }
    
    $_SESSION['user_info']['order_number'] = $orderNumber;
    $_SESSION['order_number'] = $orderNumber;

    $pdo->beginTransaction();
    
    try {
        $totalPrice = processCartToOrder($pdo, $aggregated_cart, $orderNumber);

        if ($_SESSION['user_info'] && $_SESSION['cart']) {
            
            
            if (!validateOrderNumber($pdo, $orderNumber)) {
                throw new Exception("Objednávka s číslem $orderNumber již existuje!");
            }
            
            $sql = "INSERT INTO orders_user (order_number, name, surname, email, phone, street, house_number, city, zipcode, country, shipping_method, payment_method, branch, branch_name, ico, dic, company_name, price, currency, terms, newsletter, payment_status, order_status, timestamp) VALUES (:order_number, :name, :surname, :email, :phone, :street, :house_number, :city, :zipcode, :country, :shipping_method, :payment_method, :branch, :branch_name, :ico, :dic, :company_name, :price, :currency, :terms, :newsletter, :payment_status, :order_status, :timestamp)";
            $stmt = $pdo->prepare($sql);

            $executeResult = $stmt->execute([
                'order_number' => $orderNumber,
                'name' => $_SESSION['user_info']['name'] ?? '',
                'surname' => $_SESSION['user_info']['surname'] ?? '',
                'email' => $_SESSION['user_info']['email'] ?? '',
                'phone' => $_SESSION['user_info']['phone'] ?? '',
                'street' => $_SESSION['user_info']['street'] ?? '',
                'house_number' => $_SESSION['user_info']['housenumber'] ?? '',
                'city' => $_SESSION['user_info']['city'] ?? '',
                'zipcode' => $_SESSION['user_info']['zipcode'] ?? '',
                'country' => $_SESSION['user_info']['country'] ?? '',
                'shipping_method' => $_SESSION['user_info']['shipping_method'] ?? '',
                'payment_method' => $_SESSION['user_info']['payment_method'] ?? '',
                'branch' => $_SESSION['user_info']['zasilkovna_branch'] ?? '',
                'branch_name' => $_SESSION['user_info']['zasilkovna_name'] ?? '',
                'ico' => $_SESSION['user_info']['ico'] ?? '',
                'dic' => $_SESSION['user_info']['dic'] ?? '',
                'company_name' => $_SESSION['user_info']['companyname'] ?? '',
                'price' => $totalPrice,
                'currency' => 'CZK',
                'newsletter' => $_SESSION['user_info']['newsletter'] ?? 0,
                'terms' => $_SESSION['user_info']['terms'] ?? 0,
                'payment_status' => 'no',
                'order_status' => 'waiting',
                'timestamp' => $_SESSION['user_info']['timestamp'] ?? date('Y-m-d H:i:s')
            ]);

            if ($executeResult) {
                $pdo->commit();
            
                
                $_SESSION['order_submitted'] = true;
                $_SESSION['order_processed_at'] = time();
                
            } else {
                throw new Exception("Chyba při ukládání objednávky - execute vrátil false");
            }
            
        } else {
            throw new Exception("Podmínka pro ukládání objednávky nebyla splněna");
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        if (isset($stmt)) {
            print_r($stmt->errorInfo());
        }
        exit();
    }
    
    header("location: fakturoid_service.php");
} else {
    echo "Chyba při obnově tokenu: HTTP $httpCode\n";
    echo "Odpověď: $response";
}
?>