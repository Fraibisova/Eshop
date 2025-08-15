<?php
// oauth/callback.php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    exit();
}

if (!defined('APP_ACCESS')) {
    define('APP_ACCESS', true);
}
require_once '../fakturoid_service.php';
require_once '../config.php';

if (isset($_GET['code'])) {
   $config = include '../fakturoid_config.php';

    $data = [
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $config['redirect_uri']
    ];

    // Vytvoření Basic Auth hlavičky
    $auth = base64_encode($config['client_id'] . ':' . $config['client_secret']);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://app.fakturoid.cz/api/v3/oauth/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // JSON místo form data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',        // JSON místo form-urlencoded
        'Accept: application/json',              // Přidáno Accept header
        'Authorization: Basic ' . $auth,         // Basic Auth místo client_id/secret v těle
        'User-Agent: ' . $config['user_agent']
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        
        $access_token = $tokenData['access_token'];
        $refresh_token = $tokenData['refresh_token'];
        $expires_in = $tokenData['expires_in']; // např. 3600 sekund

        $expires_at = time() + $expires_in;
        $sql = "
            INSERT INTO oauth_tokens (service, access_token, refresh_token, expires_at)
            VALUES ('fakturoid', :access_token, :refresh_token, :expires_at)
            ON DUPLICATE KEY UPDATE 
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                expires_at = VALUES(expires_at)
        ";

        $stmt = $pdo->prepare($sql);

        // Bind parametry
        $stmt->execute([
            ':access_token' => $access_token,
            ':refresh_token' => $refresh_token,
            ':expires_at' => $expires_at
        ]);

        // header('location: ../fakturoid_service.php');
    } else {
        echo "Chyba pri autorizaci: " . $response;
    }
} else {
    echo "Chybi autorizacnu kod.";
}