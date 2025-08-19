<?php 
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
}
$client_id = 'aea547926c65578c3d4e3aa3b1c8dc4dd067cc02';
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