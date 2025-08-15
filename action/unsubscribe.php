<?php
define('APP_ACCESS', true);

require '../config.php';
include '../lib/function_action.php';


$message = '';
$success = false;
$email = '';
$action = '';

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = trim($_GET['email']);
    $token = trim($_GET['token']);
    $action = $_GET['action'] ?? 'unsubscribe';
    
    $result = processNewsletterUnsubscribe($pdo, $email, $token, $action);
    $message = $result['message'];  
    $success = $result['success'];
    if (isset($result['action'])) {
        $action = $result['action'];
    }
} else {
    $message = "Neplatný odkaz. Použijte prosím odkaz z emailu.";
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'resubscribe' ? 'Opětovné přihlášení' : 'Odhlášení z newsletteru' ?></title>
    <link rel="stylesheet" href="./css/unsubscribe.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://touchthemagic.com/web_images/logo/darklogo.png" alt="Touch the Magic Logo">
        </div>
        
        <?php if (empty($email) || !isset($_GET['token'])): ?>
            <div class="icon error-icon">⚠️</div>
            <h1>Neplatný odkaz</h1>
            <div class="message error">
                Tento odkaz není platný nebo je poškozený. Prosím použijte odkaz přímo z emailu.
            </div>
            <div class="security-note">
                Z bezpečnostních důvodů vyžadujeme použití ověřeného odkazu z emailu.
            </div>
            
        <?php elseif ($success && $action === 'resubscribe'): ?>
            <div class="icon success-icon">🎉</div>
            <h1>Vítejte zpět!</h1>
            <div class="message success">
                <?= htmlspecialchars($message) ?>
            </div>
            <div class="email-info">
                <strong>Email:</strong> <?= htmlspecialchars($email) ?>
            </div>
            <p class="info-text">
                Těšíme se, že jste se rozhodli zůstat s námi! Budete opět dostávat naše newslettery s nejnovějšími informacemi a nabídkami.
            </p>
            
        <?php elseif ($success && $action === 'unsubscribed'): ?>
            <div class="icon success-icon">✅</div>
            <h1>Úspěšně odhlášeno</h1>
            <div class="message success">
                <?= htmlspecialchars($message) ?>
            </div>
            <div class="email-info">
                <strong>Odhlášený email:</strong> <?= htmlspecialchars($email) ?>
            </div>
            <p class="info-text">
                Mrzí nás, že nás opouštíte. Už vám nebudeme zasílat žádné newslettery. Pokud si to rozmyslíte, můžete se kdykoli vrátit.
            </p>
            
            <div class="action-buttons">
                <a href="<?= getResubscribeLink($email) ?>" class="btn btn-primary">
                    Přihlásit se zpět
                </a>
            </div>
            
        <?php elseif ($action === 'already_unsubscribed'): ?>
            <div class="icon error-icon">ℹ️</div>
            <h1>Již odhlášeno</h1>
            <div class="message error">
                <?= htmlspecialchars($message) ?>
            </div>
            <div class="email-info">
                <strong>Email:</strong> <?= htmlspecialchars($email) ?>
            </div>
            <p class="info-text">
                Pokud si to rozmyslíte, můžete se kdykoli znovu přihlásit k odběru.
            </p>
            
            <div class="action-buttons">
                <a href="<?= getResubscribeLink($email) ?>" class="btn btn-primary">
                    Přihlásit se zpět
                </a>
            </div>
            
        <?php else: ?>
            <div class="icon error-icon">❌</div>
            <h1>Chyba při zpracování</h1>
            <div class="message error">
                <?= htmlspecialchars($message) ?>
            </div>
            
            <?php if (!empty($email)): ?>
                <div class="email-info">
                    <strong>Email:</strong> <?= htmlspecialchars($email) ?>
                </div>
            <?php endif; ?>
            
            <div class="security-note">
                Pokud problém přetrvává, kontaktujte nás prosím přímo na email: newsletter@touchthemagic.com
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="https://touchthemagic.com">← Zpět na hlavní stránku</a>
        </div>
    </div>
</body>
</html>