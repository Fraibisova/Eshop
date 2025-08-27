<?php

namespace App\Services;

use PDO;
use Exception;

class OAuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function handleFakturoidCallback(string $code, array $config): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $config['redirect_uri']
        ];

        $auth = base64_encode($config['client_id'] . ':' . $config['client_secret']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.fakturoid.cz/api/v3/oauth/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',        
            'Accept: application/json',              
            'Authorization: Basic ' . $auth,         
            'User-Agent: ' . $config['user_agent']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('OAuth authorization failed: ' . $response);
        }

        $tokenData = json_decode($response, true);
        
        if (!$tokenData || !isset($tokenData['access_token'])) {
            throw new Exception('Invalid token response');
        }

        return $tokenData;
    }

    public function saveTokens(string $service, string $accessToken, string $refreshToken, int $expiresIn): void
    {
        $expiresAt = time() + $expiresIn;
        
        $sql = "
            INSERT INTO oauth_tokens (service, access_token, refresh_token, expires_at)
            VALUES (:service, :access_token, :refresh_token, :expires_at)
            ON DUPLICATE KEY UPDATE 
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                expires_at = VALUES(expires_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':service' => $service,
            ':access_token' => $accessToken,
            ':refresh_token' => $refreshToken,
            ':expires_at' => $expiresAt
        ]);
    }

    public function getAccessToken(string $service): ?string
    {
        $sql = "SELECT access_token, expires_at FROM oauth_tokens WHERE service = :service";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':service' => $service]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }

        if (time() >= $result['expires_at']) {
            return null; 
        }

        return $result['access_token'];
    }

    public function refreshToken(string $service, array $config): ?array
    {
        $sql = "SELECT refresh_token FROM oauth_tokens WHERE service = :service";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':service' => $service]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || !$result['refresh_token']) {
            return null;
        }

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $result['refresh_token']
        ];

        $auth = base64_encode($config['client_id'] . ':' . $config['client_secret']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://app.fakturoid.cz/api/v3/oauth/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $auth,
            'User-Agent: ' . $config['user_agent']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    public function redirectToFakturoidAuth(): void
    {
        $clientId = $this->config->get('fakturoid.client_id');
        $redirectUri = $this->config->get('fakturoid.redirect_uri');

        $authorizeUrl = 'https://app.fakturoid.cz/api/v3/oauth?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'offline',
            'state' => 'xyz'
        ]);

        header('Location: ' . $authorizeUrl);
        exit;
    }
}