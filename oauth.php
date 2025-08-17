<?php 
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
}
$client_id = 'client-id';
$redirect_uri = 'https://touchthemagic.com/oauth/callback.php';

$authorize_url = 'https://app.fakturoid.cz/api/v3/oauth?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => 'offline',
    'state' => 'xyz' 
]);

header('Location: ' . $authorize_url);
exit;