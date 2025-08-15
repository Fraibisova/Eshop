<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    die();
}

define("MAX_LOGIN_ATTEMPTS", 5);
define("LOCKOUT_TIME", 900); 

function is_locked_out($pdo, $email) {
    $sql = "SELECT failed_attempts, last_attempt FROM login_attempts WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $data = $stmt->fetch();

    if ($data) {
        if ($data['failed_attempts'] >= MAX_LOGIN_ATTEMPTS && (time() - strtotime($data['last_attempt'])) < LOCKOUT_TIME) {
            return [
                'locked' => true,
                'unlock_time' => date('H:i:s', strtotime($data['last_attempt']) + LOCKOUT_TIME)
            ];
        }
    }
    return ['locked' => false];
}

function log_attempt($pdo, $email, $success) {
    $sql = "SELECT * FROM login_attempts WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $data = $stmt->fetch();

    if ($success) {
        if ($data) {
            $sql = "DELETE FROM login_attempts WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
        }
    } else {
        if ($data) {
            $sql = "UPDATE login_attempts SET failed_attempts = failed_attempts + 1, last_attempt = NOW() WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
        } else {
            $sql = "INSERT INTO login_attempts (email, failed_attempts, last_attempt) VALUES (:email, 1, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
        }
    }
}

function cleanup_expired_attempts($pdo) {
    $sql = "DELETE FROM login_attempts WHERE TIMESTAMPDIFF(SECOND, last_attempt, NOW()) > :lockout_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['lockout_time' => LOCKOUT_TIME]);
}

function validatePassword($password, $password_confirm = null) {
    $errors = [];
    
    if (empty($password)) {
        $errors[] = "Heslo je povinné.";
    } elseif (strpos($password, ' ') !== false) {
        $errors[] = "Heslo nesmí obsahovat mezery.";
    } else {
        if (strlen($password) < 8) {
            $errors[] = "Heslo musí být dlouhé alespoň 8 znaků.";
        }
        if (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
            $errors[] = "Heslo musí obsahovat alespoň 1 velké písmeno, 1 malé písmeno, 1 číslo a 1 speciální znak.";
        }
        if ($password_confirm !== null && $password !== $password_confirm) {
            $errors[] = "Hesla se neshodují.";
        }
    }
    
    return $errors;
}

function validateResetPassword($password, $password_confirmation) {
    $errors = [];
    
    if ($password !== $password_confirmation) {
        $errors[] = "Hesla se neshodují.";
    }
    
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = "Heslo musí mít alespoň 8 znaků, jedno velké písmeno a číslici.";
    }
    
    return $errors;
}

function validateRegistrationData($name, $surname, $email, $password, $password_confirm, $terms, $pdo) {
    $errors = [];
    
    $errors = array_merge($errors, validateName($name, "Jméno"));
    $errors = array_merge($errors, validateName($surname, "Příjmení"));
    
    $emailErrors = validateEmailForRegistration($email, $pdo);
    $errors = array_merge($errors, $emailErrors);
    
    $passwordErrors = validatePassword($password, $password_confirm);
    $errors = array_merge($errors, $passwordErrors);
    
    if (empty($terms)) {
        $errors[] = "Musíte souhlasit s podmínkami.";
    }
    
    return $errors;
}

function validateEmailForRegistration($email, $pdo) {
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email je povinný.";
    } elseif (strpos($email, ' ') !== false) {
        $errors[] = "Email nesmí obsahovat mezery.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email není správně napsaný.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Tato emailová adresa se již používá. Zvolte jinou nebo obnovte heslo kliknutím <a href='forgot_password.php'>zde</a>";
        }
    }
    
    return $errors;
}

function createUser($pdo, $name, $surname, $email, $password, $newsletter, $terms) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (name, surname, email, password, newsletter, terms, role_level) VALUES (:name, :surname, :email, :password, :newsletter, :terms, 0)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'password' => $hashed_password,
            'newsletter' => $newsletter,
            'terms' => $terms
        ]);
        return true;
    } catch (PDOException $e) {
        return "Chyba při registraci: " . $e->getMessage();
    }
}

function authenticateUser($pdo, $email, $password) {
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_level'] = $user['role_level'];
        return $user;
    }
    
    return false;
}

function generateResetToken() {
    return bin2hex(random_bytes(16));
}

function saveResetToken($pdo, $email, $token) {
    $token_hash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes
    
    $sql = "UPDATE users SET reset_token_hash = :token_hash, reset_token_expires_at = :expiry WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'token_hash' => $token_hash,
        'expiry' => $expiry,
        'email' => $email
    ]);
    
    return $stmt->rowCount() > 0;
}

function validateResetToken($pdo, $token) {
    $token_hash = hash("sha256", $token);
    
    $sql = "SELECT * FROM users WHERE reset_token_hash = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token_hash]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['valid' => false, 'message' => 'Neplatný nebo již použitý token.'];
    }
    
    if (strtotime($user["reset_token_expires_at"]) <= time()) {
        return ['valid' => false, 'message' => 'Platnost tokenu vypršela.'];
    }
    
    return ['valid' => true, 'user' => $user];
}

function updatePasswordWithToken($pdo, $token, $new_password) {
    $token_hash = hash("sha256", $token);
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
    $sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE reset_token_hash = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$hashed_password, $token_hash]);
}

function subscribeToNewsletter($pdo, $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Zadaný email je neplatný.'];
    }
    
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:email)");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Děkujeme za přihlášení k newsletteru!'];
        } else {
            return ['success' => true, 'message' => 'Tento email je již přihlášen.'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Chyba při ukládání: ' . $e->getMessage()];
    }
}

function setSessionErrors($errors) {
    $_SESSION["errors"] = $errors;
}

function displaySessionMessage($key, $default_class = 'green') {
    if (isset($_SESSION[$key])) {
        echo "<span class='{$default_class}'>{$_SESSION[$key]}</span>";
        unset($_SESSION[$key]);
    }
}

function redirectBasedOnRole($role) {
    if ($role == 10) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

function redirectWithMessage($location, $sessionKey, $message) {
    $_SESSION[$sessionKey] = $message;
    header("Location: $location");
    exit();
}

function processBoughtItems($aggregated_cart, $pdo) {
    $bought_items = [];
    foreach ($aggregated_cart as $cartItem) {
        $id = $cartItem['id'];
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        
        if ($item) {
            for ($i = 0; $i < $cartItem['quantity']; $i++) { 
                $bought_items[] = $item;
            }
        }
    }
    return $bought_items;
}

function calculateTotalPrice($aggregated_cart, $pdo) {
    $totalPrice = 0;
    foreach ($aggregated_cart as $cartItem) {
        $id = $cartItem['id'];
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        if ($item) {
            $itemPrice = $item['price'] * $cartItem['quantity'];
            $totalPrice += $itemPrice;
        }
    }
    return $totalPrice;
}

function generateOrderNumber($db) {
    $query = $db->query("SELECT order_number FROM orders_user ORDER BY id DESC LIMIT 1");
    $lastOrder = $query->fetch(PDO::FETCH_ASSOC);

    if ($lastOrder) {
        $lastNumber = (int)$lastOrder['order_number'];
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    $orderNumber = $newNumber < 10000 ? str_pad($newNumber, 4, '0', STR_PAD_LEFT) : $newNumber;
    return $orderNumber;
}

function validateInput($input) {
    $input = trim($input);
    if (preg_match('/^[\p{L}\p{N}\s\-]*$/u', $input)) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    } else {
        return false;
    }
}

function validateHiddenInput($input) {
    if (!empty($input)) {
        return validateInput($input);
    }
    return null; 
}

function validateBillingData($postData) {
    $errors = [];
    $validatedData = [];
    
    $validatedData['name'] = validateInput($postData['name_new'] ?? '');
    $validatedData['surname'] = validateInput($postData['surname_new'] ?? '');
    $validatedData['email'] = filter_var(trim($postData['email_new'] ?? ''), FILTER_VALIDATE_EMAIL);
    $validatedData['phone'] = preg_match('/^[0-9]{9,15}$/', $postData['phone_new'] ?? '') ? $postData['phone_new'] : false;
    $validatedData['street'] = validateInput($postData['street_new'] ?? '');
    $validatedData['housenumber'] = validateInput($postData['housenumber_new'] ?? '');
    $validatedData['city'] = validateInput($postData['city_new'] ?? '');
    $validatedData['zipcode'] = validateInput($postData['zipcode_new'] ?? '');
    $validatedData['country'] = validateInput($postData['country'] ?? '');
    $validatedData['terms'] = isset($postData['conditions']) ? 'yes' : 'no';
    $validatedData['newsletter'] = isset($postData['newsletter_register']) ? 'no' : 'yes';
    
    if (isset($postData['company']) && $postData['company'] === 'yes') {
        $validatedData['ico'] = validateHiddenInput($postData['ico_new'] ?? '');
        $validatedData['dic'] = validateHiddenInput($postData['dic_new'] ?? '');
        $validatedData['companyname'] = validateHiddenInput($postData['companyname_new'] ?? '');
        if (!$validatedData['ico'] || !$validatedData['dic'] || !$validatedData['companyname']) {
            $errors[] = "Údaje o firmě jsou neplatné.";
        }
    }
    
    if (isset($postData['another_address']) && $postData['another_address'] === 'yes') {
        $street_other = validateHiddenInput($postData['street_other'] ?? '');
        $housenumber_other = validateHiddenInput($postData['housenumber_other'] ?? '');
        $city_other = validateHiddenInput($postData['city_other'] ?? '');
        $zipcode_other = validateHiddenInput($postData['zipcode_other'] ?? '');
        if (!$street_other || !$housenumber_other || !$city_other || !$zipcode_other) {
            $errors[] = "Doručovací adresa je neplatná.";
        }
    }
    
    if (!$validatedData['name']) $errors[] = "Jméno obsahuje neplatné znaky.";
    if (!$validatedData['surname']) $errors[] = "Příjmení obsahuje neplatné znaky.";
    if (!$validatedData['email']) $errors[] = "Email není platný.";
    if (!$validatedData['phone']) $errors[] = "Telefonní číslo musí obsahovat pouze číslice a mít délku 9 až 15 znaků.";
    if (!$validatedData['street']) $errors[] = "Ulice obsahuje neplatné znaky.";
    if (!$validatedData['housenumber']) $errors[] = "Číslo popisné obsahuje neplatné znaky.";
    if (!$validatedData['city']) $errors[] = "Město obsahuje neplatné znaky.";
    if (!$validatedData['zipcode']) $errors[] = "PSČ obsahuje neplatné znaky.";
    if ($validatedData['terms'] !== 'yes') $errors[] = "Musíte souhlasit s podmínkami.";
    
    return ['errors' => $errors, 'data' => $validatedData];
}

function validateSessionCart() {
    return !empty($_SESSION['cart']);
}

function validateSessionMethodData() {
    return !empty($_SESSION['methodData']);
}

function validateSessionUserInfo() {
    return !empty($_SESSION['user_info']);
}

function getShippingCost($freeShipping = false) {
    return $freeShipping ? 0 : 79;
}

function processPaymentMethod($paymentMethod) {
    $payment_price = 0;
    $shortcode = '';
    $method_name = '';
    
    switch ($paymentMethod) {
        case 'cod':
            $method_name = "Dobírka";
            $payment_price = 45;
            $shortcode = "cod";
            break;
        case 'card':
            $method_name = "Platba kartou";
            $shortcode = "card";
            break;
        case 'sepa':
            $method_name = "On-line bankovní převod";
            $shortcode = "sepa";
            break;
    }
    
    return [
        'name' => $method_name,
        'price' => $payment_price,
        'shortcode' => $shortcode
    ];
}

function generateUnsubscribeToken($email) {
    $secret = 'cE9vYzP7kG5aJ1mQxR2tU8nLwB0hXsMd';
    return hash('sha256', $email . $secret);
}

function verifyUnsubscribeToken($email, $token) {
    return hash_equals(generateUnsubscribeToken($email), $token);
}

function getUnsubscribeLink($email, $base_url = 'https://touchthemagic.com') {
    $token = generateUnsubscribeToken($email);
    return $base_url . '/action/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;
}

function getResubscribeLink($email, $base_url = 'https://touchthemagic.com') {
    $token = generateUnsubscribeToken($email);
    return $base_url . '/action/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token . '&action=resubscribe';
}

function processNewsletterUnsubscribe($pdo, $email, $token, $action = 'unsubscribe') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Neplatný formát emailové adresy.'];
    }
    
    if (!verifyUnsubscribeToken($email, $token)) {
        return ['success' => false, 'message' => 'Neplatný nebo vypršelý odkaz. Z bezpečnostních důvodů použijte aktuální odkaz z emailu.'];
    }
    
    try {
        if ($action === 'resubscribe') {
            $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscriber) {
                $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET active = 1, unsubscribed_at = NULL WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    return ['success' => true, 'message' => 'Váš email byl úspěšně znovu přihlášen k odběru newsletteru.', 'action' => 'resubscribe'];
                } else {
                    return ['success' => false, 'message' => 'Email již je aktivní nebo došlo k chybě.'];
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at, active, unsubscribed_at) VALUES (?, NOW(), 1, NULL)");
                $stmt->execute([$email]);
                return ['success' => true, 'message' => 'Váš email byl úspěšně přihlášen k odběru newsletteru.', 'action' => 'resubscribe'];
            }
        } else {
            $stmt = $pdo->prepare("SELECT id, email, active FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subscriber) {
                if ($subscriber['active'] == 1) {
                    $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET active = 0, unsubscribed_at = NOW() WHERE email = ?");
                    $stmt->execute([$email]);
                    return ['success' => true, 'message' => 'Váš email byl úspěšně odhlášen z newsletteru.', 'action' => 'unsubscribed'];
                } else {
                    return ['success' => false, 'message' => 'Tento email již byl dříve odhlášen z newsletteru.', 'action' => 'already_unsubscribed'];
                }
            } else {
                return ['success' => false, 'message' => 'Email nebyl nalezen v seznamu odběratelů.'];
            }
        }
    } catch (Exception $e) {
        error_log("Chyba při zpracování unsubscribe: " . $e->getMessage());
        return ['success' => false, 'message' => 'Došlo k chybě při zpracování požadavku. Zkuste to prosím později.'];
    }
}

function prepareOrderEmail($userInfo, $boughtItems, $totalPrice, $orderNumber) {
    $emailTemplate = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Potvrzení objednávky</title>
        <style type="text/css">
            /* Reset styles */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            /* Main styles */
            body {
                font-family: Arial, sans-serif !important;
                background-color: #ffffff;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
            }
            
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            .header {
                background-color: #6f42c1;
                padding: 20px;
                text-align: center;
                border-top: 2px solid #dee2e6;
                border-bottom: 2px solid #dee2e6;
            }
            
            .header h1 {
                color: #ffffff;
                font-size: 18px;
                margin: 0;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 3px;
            }
            
            .status-section {
                background-color: #6f42c1;
                color: #ffffff;
                text-align: center;
                padding: 15px;
                margin: 20px 0;
            }
            
            .status-section h2 {
                font-size: 15px;
                text-transform: uppercase;
                font-weight: 500;
                letter-spacing: 4px;
                margin: 0;
            }
            
            .status-icon {
                font-size: 50px;
                color: #6f42c1;
                text-align: center;
                padding: 20px 0;
            }
            
            .content {
                padding: 30px 20px;
                background-color: #f8f9fa;
            }
            
            .order-info {
                background-color: #e5d7ee;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 20px;
            }
            
            .order-info h3 {
                color: #251d3c;
                margin-top: 0;
                font-size: 18px;
                font-weight: bold;
            }
            
            .order-info p {
                margin: 12px 0;
                font-size: 14px;
                line-height: 1.6;
                color: #333333;
            }
            
            .order-number {
                background-color: #251d3c;
                color: #ffffff;
                padding: 8px 12px;
                border-radius: 4px;
                font-weight: bold;
                display: inline-block;
            }
            
            .customer-section {
                background-color: #ffffff;
                border-left: 4px solid #6f42c1;
                padding: 15px;
                margin: 15px 0;
                border-radius: 0 5px 5px 0;
            }
            
            .customer-section strong {
                color: #251d3c;
            }
            
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                background-color: #ffffff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .products-table-header {
                background-color: #251d3c;
                color: #ffffff;
                padding: 12px 15px;
                font-weight: bold;
                font-size: 14px;
            }
            
            .product-row {
                padding: 12px 15px;
                border-bottom: 1px solid #f8f9fa;
                display: table-row;
            }
            
            .product-row:last-child {
                border-bottom: none;
            }
            
            .product-cell-left {
                display: table-cell;
                vertical-align: top;
                width: 70%;
            }
            
            .product-cell-right {
                display: table-cell;
                vertical-align: top;
                text-align: right;
                width: 30%;
            }
            
            .product-name {
                font-weight: bold;
                color: #251d3c;
                font-size: 14px;
                margin-bottom: 5px;
            }
            
            .product-details {
                color: #6c757d;
                font-size: 13px;
            }
            
            .product-price {
                font-weight: bold;
                color: #251d3c;
                font-size: 14px;
            }
            
            .total-section {
                background-color: #6f42c1;
                color: #ffffff;
                padding: 15px;
                text-align: right;
                font-weight: bold;
                font-size: 16px;
            }
            
            .next-steps {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .next-steps h4 {
                color: #856404;
                margin-top: 0;
                font-size: 16px;
                font-weight: bold;
            }
            
            .next-steps p {
                color: #856404;
                margin: 8px 0;
                font-size: 14px;
            }
            
            .social-section {
                text-align: center;
                padding: 30px 20px;
            }
            
            .social-section h3 {
                color: #251d3c;
                margin-bottom: 20px;
                font-size: 18px;
            }
            
            .social-links {
                text-align: center;
            }
            
            .social-links img {
                width: 40px;
                height: 40px;
                margin: 0 8px;
                border-radius: 50%;
            }
            
            .footer {
                background-color: #e5d7ee;
                padding: 25px 20px;
                text-align: center;
                font-size: 13px;
            }
            
            .footer a, .footer p {
                color: #251d3c;
                text-decoration: none;
                margin: 0 8px;
                display: inline-block;
            }
            
            .footer a:hover {
                color: #6f42c1;
            }
            
            .footer p {
                display: inline;
            }
            
            /* Responsive */
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                .order-info {
                    padding: 15px !important;
                }
                .products-table {
                    font-size: 12px !important;
                }
            }
        </style>
    </head>
    <body>
        <table cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa;">
            <tr>
                <td align="center">
                    <div class="email-container">
                        <!-- Logo -->
                        <div style="text-align: center; padding: 20px;">
                            <img alt="Touch The Magic logo" src="https://touchthemagic.com/web_images/logo/darklogo.png" style="max-width: 200px; height: auto;">
                        </div>
                        
                        <!-- Header -->
                        <div class="header">
                            <h1>POTVRZENÍ OBJEDNÁVKY</h1>
                        </div>
                        
                        <!-- Status -->
                        <div class="status-section">
                            <h2>Máte objednáno</h2>
                        </div>
                        
                        <div class="status-icon">
                            📋
                        </div>
                        
                        <!-- Content -->
                        <div class="content">
                            <div class="order-info">
                                <h3>Děkujeme za vaši objednávku!</h3>
                                <p>Vaše objednávka číslo <span class="order-number">{{order_number}}</span> byla úspěšně vytvořena a přijata ke zpracování.</p>
                                
                                <div class="customer-section">
                                    <p><strong>Datum objednávky:</strong> {{order_date}}</p>
                                    <p><strong>Způsob platby:</strong> {{payment_method}}</p>
                                    <p><strong>Způsob doručení:</strong> {{delivery_method}}</p>
                                </div>

                                <div class="customer-section">
                                    <p><strong>Dodací adresa:</strong><br>
                                    {{delivery_name}}<br>
                                    {{delivery_address}}<br>
                                    {{delivery_city}}, {{delivery_zip}}</p>
                                </div>

                                <!-- Products -->
                                <div style="margin: 20px 0;">
                                    <div class="products-table-header">
                                        Objednané produkty
                                    </div>
                                    <div style="background-color: #ffffff; border: 1px solid #dee2e6; border-top: none;">
                                        {{items_rows}}
                                        <div class="total-section">
                                            Celkem k úhradě: {{order_total}} Kč
                                        </div>
                                    </div>
                                </div>

                                <div class="next-steps">
                                    <h4>Další kroky:</h4>
                                    <p>1. Dokončete platbu</p>
                                    <p>2. Po zaplacení vám zašleme potvrzení o přijetí platby</p>
                                    <p>3. Objednávku připravíme a zašleme vám informace o expedici</p>
                                    <p>4. Zásilka bude doručena na uvedenou adresu</p>
                                </div>
                                
                                <p>Stav objednávky můžete sledovat ve vašem účtu. V případě jakýchkoli dotazů nás neváhejte kontaktovat.</p>
                            </div>
                        </div>
                        
                        <!-- Social Section -->
                        <div class="social-section">
                            <h3>Zůstaňte s námi v kontaktu</h3>
                            <div class="social-links">
                                <a href=""><img alt="instagram" src="https://touchthemagic.com/web_images/logo/instagram.png"></a>
                                <a href=""><img alt="facebook" src="https://touchthemagic.com/web_images/logo/facebook.png"></a>
                                <a href=""><img alt="tiktok" src="https://touchthemagic.com/web_images/logo/tiktok.png"></a>
                                <a href=""><img alt="pinterest" src="https://touchthemagic.com/web_images/logo/pinterest.png"></a>
                                <a href=""><img alt="youtube" src="https://touchthemagic.com/web_images/logo/youtube.png"></a>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="footer">
                            <p>Beáta Fraibišová</p>
                            <a href="tel:+420747473938">+420 747 473 938</a>
                            <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                            <a href="https://touchthemagic.com/page.php?page=obchodni-podminky">Podmínky</a>
                            <a href="https://touchthemagic.com/page.php?page=kontakty">Kontakt</a>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>';
    $emailTemplate = str_replace('{{order_number}}', $orderNumber, $emailTemplate);
    $emailTemplate = str_replace('{{order_date}}', date('d.m.Y H:i'), $emailTemplate);
    $emailTemplate = str_replace('{{payment_method}}', $userInfo['payment_method'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_method}}', 'Zásilkovna', $emailTemplate);
    $emailTemplate = str_replace('{{order_total}}', number_format($totalPrice, 0, ',', ' '), $emailTemplate);
    
    $emailTemplate = str_replace('{{delivery_name}}', $userInfo['name'] . ' ' . $userInfo['surname'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_address}}', $userInfo['street'] . ' ' . $userInfo['housenumber'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_city}}', $userInfo['city'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_zip}}', $userInfo['zipcode'], $emailTemplate);
    
    $itemsRows = '';
    $itemCounts = [];
    
    foreach ($boughtItems as $item) {
        $itemId = $item['id'];
        if (!isset($itemCounts[$itemId])) {
            $itemCounts[$itemId] = [
                'item' => $item,
                'quantity' => 1,
                'total' => $item['price']
            ];
        } else {
            $itemCounts[$itemId]['quantity']++;
            $itemCounts[$itemId]['total'] += $item['price'];
        }
    }
    
    foreach ($itemCounts as $itemData) {
        $item = $itemData['item'];
        $quantity = $itemData['quantity'];
        $total = $itemData['total'];
        
        $itemsRows .= '
        <div class="product-row">
            <div class="product-cell-left">
                <div class="product-name">' . htmlspecialchars($item['name']) . '</div>
                <div class="product-details">' . $quantity . '× ' . number_format($item['price'], 0, ',', ' ') . ' Kč</div>
            </div>
            <div class="product-cell-right">
                <div class="product-price">' . number_format($total, 0, ',', ' ') . ' Kč</div>
            </div>
        </div>';
    }
    
    $emailTemplate = str_replace('{{items_rows}}', $itemsRows, $emailTemplate);
    
    return $emailTemplate;
}
function getEmailTemplatePaymentConfirmation($order_number, $current_date, $total_amount, $payment_method) {
    return <<<END
<!DOCTYPE html>
<html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            /* Email client reset */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            body {
                font-family: Arial, Helvetica, sans-serif;
                background-color: #ffffff;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
            }
            
            /* Main container */
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            /* Header */
            .header {
                text-align: center;
                padding: 30px 20px 20px 20px;
            }
            
            .logo {
                max-width: 200px;
                height: auto;
            }
            
            .title {
                border-top: 2px solid #e0e0e0;
                border-bottom: 2px solid #e0e0e0;
                margin: 20px 0;
                padding: 15px 0;
                text-transform: uppercase;
                font-size: 10px;
                font-weight: 700;
                color: #333333;
                letter-spacing: 1px;
            }
            
            /* Status section */
            .status-section {
                text-align: center;
                padding: 20px;
            }
            
            .status-title {
                font-size: 15px;
                text-transform: uppercase;
                background-color: #28a745;
                color: white;
                font-weight: 500;
                padding: 10px 30px;
                letter-spacing: 3px;
                margin: 20px 0;
                display: inline-block;
                border-radius: 3px;
            }
            
            .status-icon {
                font-size: 60px;
                color: #28a745;
                margin: 20px 0;
                line-height: 1;
            }
            
            /* Order info */
            .order-info {
                background-color: #e5d7ee;
                margin: 20px;
                padding: 25px;
                border-radius: 5px;
                text-align: left;
            }
            
            .order-info h3 {
                color: #251d3c;
                margin: 0 0 15px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .order-info p {
                margin: 12px 0;
                font-size: 14px;
                line-height: 1.6;
                color: #333333;
            }
            
            .order-number {
                background-color: #251d3c;
                color: white;
                padding: 4px 8px;
                border-radius: 3px;
                font-weight: 600;
                font-size: 14px;
            }
            
            /* Social section */
            .social-section {
                text-align: center;
                padding: 30px 20px;
                background-color: #f8f9fa;
            }
            
            .social-section h3 {
                color: #251d3c;
                margin: 0 0 20px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .social-links {
                text-align: center;
            }
            
            .social-links a {
                display: inline-block;
                margin: 0 5px;
                text-decoration: none;
            }
            
            .social-links img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                vertical-align: middle;
            }
            
            /* Footer */
            .footer {
                background-color: #e5d7ee;
                padding: 25px 20px;
                text-align: center;
            }
            
            .footer p {
                margin: 0;
                display: inline;
                color: #251d3c;
                font-size: 13px;
                padding-right: 6px;
                border-right: 1.5px solid #9f8faa;
                margin-right: 6px;
            }
            
            .footer a {
                color: #251d3c;
                text-decoration: none;
                font-size: 13px;
                padding-right: 6px;
                border-right: 1.5px solid #9f8faa;
                margin-right: 6px;
            }
            
            .footer a:hover {
                color: #9f8faa;
            }
            
            .footer .end {
                border-right: none;
                padding-right: 0;
                margin-right: 0;
            }
            
            /* Responsive */
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                
                .order-info {
                    margin: 10px !important;
                    padding: 20px !important;
                }
                
                .status-title {
                    font-size: 13px;
                    padding: 8px 20px;
                    letter-spacing: 2px;
                }
                
                .footer p, .footer a {
                    display: block;
                    border-right: none;
                    margin: 5px 0;
                    padding-right: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <img class="logo" alt="Touch The Magic Logo" src="https://touchthemagic.com/web_images/logo/darklogo.png">
                <div class="title">
                    POTVRZENÍ PLATBY
                </div>
            </div>
            
            <!-- Status Section -->
            <div class="status-section">
                <div class="status-title">Objednávka byla zaplacena</div>
                <div class="status-icon">✓</div>
            </div>
            
            <!-- Order Info -->
            <div class="order-info">
                <h3>Děkujeme za vaši objednávku!</h3>
                <p>Vaše platba byla úspěšně zpracována. Objednávka číslo <span class="order-number">{$order_number}</span> je nyní potvrzena a bude co nejdříve připravena k odeslání.</p>
                <p><strong>Datum platby:</strong> {$current_date}</p>
                <p><strong>Částka:</strong> {$total_amount} Kč</p>
                <p><strong>Způsob platby:</strong> {$payment_method}</p>
                <p>O dalším postupu vás budeme informovat emailem. Sledovat stav objednávky můžete také ve vašem účtu na našem e-shopu.</p>
            </div>
            
            <!-- Social Section -->
            <div class="social-section">
                <h3>Zůstaňte s námi v kontaktu</h3>
                <div class="social-links">
                    <a href="https://www.instagram.com/touchthemagic"><img alt="Instagram" src="https://touchthemagic.com/web_images/logo/instagram.png"></a>
                    <a href="https://www.facebook.com/touchthemagic"><img alt="Facebook" src="https://touchthemagic.com/web_images/logo/facebook.png"></a>
                    <a href="https://www.tiktok.com/@touchthemagic"><img alt="TikTok" src="https://touchthemagic.com/web_images/logo/tiktok.png"></a>
                    <a href="https://www.pinterest.com/touchthemagic"><img alt="Pinterest" src="https://touchthemagic.com/web_images/logo/pinterest.png"></a>
                    <a href="https://www.youtube.com/@touchthemagic"><img alt="YouTube" src="https://touchthemagic.com/web_images/logo/youtube.png"></a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>Beáta Fraibišová</p>
                <a href="tel:+420747473938">+420 747 473 938</a>
                <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                <a href="https://touchthemagic.com/page.php?page=obchodni-podminky">Podmínky</a>
                <a href="https://touchthemagic.com/page.php?page=kontakty" class="end">Kontakt</a>
            </div>
        </div>
    </body>
</html>
END;
}

function getEmailTemplateCanceled($order_number, $current_date, $total_amount) {
    return <<<END
<!DOCTYPE html>
<html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            /* Email client reset */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            body {
                font-family: Arial, Helvetica, sans-serif;
                background-color: #ffffff;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
            }
            
            /* Main container */
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            /* Header */
            .header {
                text-align: center;
                padding: 30px 20px 20px 20px;
            }
            
            .logo {
                max-width: 200px;
                height: auto;
            }
            
            .title {
                border-top: 2px solid #e0e0e0;
                border-bottom: 2px solid #e0e0e0;
                margin: 20px 0;
                padding: 15px 0;
                text-transform: uppercase;
                font-size: 10px;
                font-weight: 700;
                color: #333333;
                letter-spacing: 1px;
            }
            
            /* Status section */
            .status-section {
                text-align: center;
                padding: 20px;
            }
            
            .status-title {
                font-size: 15px;
                text-transform: uppercase;
                background-color: #dc3545;
                color: white;
                font-weight: 500;
                padding: 10px 30px;
                letter-spacing: 3px;
                margin: 20px 0;
                display: inline-block;
                border-radius: 3px;
            }
            
            .status-icon {
                font-size: 60px;
                color: #dc3545;
                margin: 20px 0;
                line-height: 1;
            }
            
            /* Order info */
            .order-info {
                background-color: #e5d7ee;
                margin: 20px;
                padding: 25px;
                border-radius: 5px;
                text-align: left;
            }
            
            .order-info h3 {
                color: #251d3c;
                margin: 0 0 15px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .order-info p {
                margin: 12px 0;
                font-size: 14px;
                line-height: 1.6;
                color: #333333;
            }
            
            .order-number {
                background-color: #251d3c;
                color: white;
                padding: 4px 8px;
                border-radius: 3px;
                font-weight: 600;
                font-size: 14px;
            }
            
            /* Social section */
            .social-section {
                text-align: center;
                padding: 30px 20px;
                background-color: #f8f9fa;
            }
            
            .social-section h3 {
                color: #251d3c;
                margin: 0 0 20px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .social-links {
                text-align: center;
            }
            
            .social-links a {
                display: inline-block;
                margin: 0 5px;
                text-decoration: none;
            }
            
            .social-links img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                vertical-align: middle;
            }
            
            /* Footer */
            .footer {
                background-color: #e5d7ee;
                padding: 25px 20px;
                text-align: center;
            }
            
            .footer p {
                margin: 0;
                display: inline;
                color: #251d3c;
                font-size: 13px;
                padding-right: 6px;
                border-right: 1.5px solid #9f8faa;
                margin-right: 6px;
            }
            
            .footer a {
                color: #251d3c;
                text-decoration: none;
                font-size: 13px;
                padding-right: 6px;
                border-right: 1.5px solid #9f8faa;
                margin-right: 6px;
            }
            
            .footer a:hover {
                color: #9f8faa;
            }
            
            .footer .end {
                border-right: none;
                padding-right: 0;
                margin-right: 0;
            }
            
            /* Responsive */
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                
                .order-info {
                    margin: 10px !important;
                    padding: 20px !important;
                }
                
                .status-title {
                    font-size: 13px;
                    padding: 8px 20px;
                    letter-spacing: 2px;
                }
                
                .footer p, .footer a {
                    display: block;
                    border-right: none;
                    margin: 5px 0;
                    padding-right: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <img class="logo" alt="Touch The Magic Logo" src="https://touchthemagic.com/web_images/logo/darklogo.png">
                <div class="title">
                    ZRUŠENÍ OBJEDNÁVKY
                </div>
            </div>
            
            <!-- Status Section -->
            <div class="status-section">
                <div class="status-title">Platba byla zrušena</div>
                <div class="status-icon">✗</div>
            </div>
            
            <!-- Order Info -->
            <div class="order-info">
                <h3>Platba objednávky byla zrušena</h3>
                <p>Platba za objednávku číslo <span class="order-number">{$order_number}</span> byla zrušena. Objednávka nebyla dokončena a žádné produkty nebudou odeslány.</p>
                <p><strong>Datum zrušení:</strong> {$current_date}</p>
                <p><strong>Částka:</strong> {$total_amount} Kč</p>
            </div>
            
            <!-- Social Section -->
            <div class="social-section">
                <h3>Zůstaňte s námi v kontaktu</h3>
                <div class="social-links">
                    <a href="https://www.instagram.com/touchthemagic"><img alt="Instagram" src="https://touchthemagic.com/web_images/logo/instagram.png"></a>
                    <a href="https://www.facebook.com/touchthemagic"><img alt="Facebook" src="https://touchthemagic.com/web_images/logo/facebook.png"></a>
                    <a href="https://www.tiktok.com/@touchthemagic"><img alt="TikTok" src="https://touchthemagic.com/web_images/logo/tiktok.png"></a>
                    <a href="https://www.pinterest.com/touchthemagic"><img alt="Pinterest" src="https://touchthemagic.com/web_images/logo/pinterest.png"></a>
                    <a href="https://www.youtube.com/@touchthemagic"><img alt="YouTube" src="https://touchthemagic.com/web_images/logo/youtube.png"></a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>Beáta Fraibišová</p>
                <a href="tel:+420747473938">+420 747 473 938</a>
                <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                <a href="https://touchthemagic.com/page.php?page=obchodni-podminky">Podmínky</a>
                <a href="https://touchthemagic.com/page.php?page=kontakty" class="end">Kontakt</a>
            </div>
        </div>
    </body>
</html>
END;
}

function getEmailTemplateTimeouted($order_number, $current_date, $total_amount) {
    return <<<END
<!DOCTYPE html>
<html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            /* Email client reset */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            body {
                font-family: Arial, Helvetica, sans-serif;
                background-color: #ffffff;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
            }
            
            /* Main container */
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            /* Header */
            .header {
                text-align: center;
                padding: 30px 20px 20px 20px;
            }
            
            .logo {
                max-width: 200px;
                height: auto;
            }
            
            .title {
                border-top: 2px solid #e0e0e0;
                border-bottom: 2px solid #e0e0e0;
                margin: 20px 0;
                padding: 15px 0;
                text-transform: uppercase;
                font-size: 10px;
                font-weight: 700;
                color: #333333;
                letter-spacing: 1px;
            }
            
            /* Status section */
            .status-section {
                text-align: center;
                padding: 20px;
            }
            
            .status-title {
                font-size: 15px;
                text-transform: uppercase;
                background-color: #ffc107;
                color: #212529;
                font-weight: 500;
                padding: 10px 30px;
                letter-spacing: 3px;
                margin: 20px 0;
                display: inline-block;
                border-radius: 3px;
            }
            
            .status-icon {
                font-size: 60px;
                color: #ffc107;
                margin: 20px 0;
                line-height: 1;
            }
            
            /* Order info */
            .order-info {
                background-color: #e5d7ee;
                margin: 20px;
                padding: 25px;
                border-radius: 5px;
                text-align: left;
            }
            
            .order-info h3 {
                color: #251d3c;
                margin: 0 0 15px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .order-info p {
                margin: 12px 0;
                font-size: 14px;
                line-height: 1.6;
                color: #333333;
            }
            
            .order-number {
                background-color: #251d3c;
                color: white;
                padding: 4px 8px;
                border-radius: 3px;
                font-weight: 600;
                font-size: 14px;
            }
            
            /* Action button */
            .action-section {
                text-align: center;
                padding: 20px;
            }
            
            .action-button {
                background-color: #251d3c;
                color: white;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 15px;
                text-decoration: none;
                padding: 15px 40px;
                display: inline-block;
                border-radius: 3px;
                margin: 10px 0;
            }
            
            .action-button:hover {
                background-color: #9f8faa;
            }
            
            /* Social section */
            .social-section {
                text-align: center;
                padding: 30px 20px;
                background-color: #f8f9fa;
            }
            
            .social-section h3 {
                color: #251d3c;
                margin: 0 0 20px 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .social-links {
                text-align: center;
            }
            
            .social-links a {
                display: inline-block;
                margin: 0 5px;
                text-decoration: none;
            }
            
            .social-links img {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                vertical-align: middle;
            }
            
            /* Footer */
            .footer {
                background-color: #e5d7ee;
                padding: 25px 20px;
                text-align: center;
            }
            
            .footer p {
                margin: 0;
                display: inline;
                color: #251d3c;
                font-size: 13px;
                padding-right: 6px;
                border-right: 1.5px solid #9f8faa;
                margin-right: 6px;
            }
            
            .footer a {
                color: #251d3c;
                text-decoration: none;
                font-size: 13px;
                padding-right: 6px;
                border-right: 1.5px solid #9f8faa;
                margin-right: 6px;
            }
            
            .footer a:hover {
                color: #9f8faa;
            }
            
            .footer .end {
                border-right: none;
                padding-right: 0;
                margin-right: 0;
            }
            
            /* Responsive */
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                
                .order-info {
                    margin: 10px !important;
                    padding: 20px !important;
                }
                
                .status-title {
                    font-size: 13px;
                    padding: 8px 20px;
                    letter-spacing: 2px;
                }
                
                .action-button {
                    padding: 12px 30px;
                    font-size: 14px;
                }
                
                .footer p, .footer a {
                    display: block;
                    border-right: none;
                    margin: 5px 0;
                    padding-right: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <img class="logo" alt="Touch The Magic Logo" src="https://touchthemagic.com/web_images/logo/darklogo.png">
                <div class="title">
                    VYPRŠENÍ PLATBY
                </div>
            </div>
            
            <!-- Status Section -->
            <div class="status-section">
                <div class="status-title">Platba vypršela</div>
                <div class="status-icon">⏰</div>
            </div>
            
            <!-- Order Info -->
            <div class="order-info">
                <h3>Platba objednávky vypršela</h3>
                <p>Čas vyhrazený pro dokončení platby objednávky číslo <span class="order-number">{$order_number}</span> vypršel. Objednávka nebyla dokončena a žádné produkty nebudou odeslány.</p>
                <p><strong>Datum vypršení:</strong> {$current_date}</p>
                <p><strong>Částka:</strong> {$total_amount} Kč</p>
                <p>Pokud máte stále zájem o produkty z této objednávky, můžete je znovu objednat kliknutím na tlačítko níže. Produkty zůstávají ve vašem košíku.</p>
            </div>
            
            <!-- Action Button -->
            <div class="action-section">
                <a href="https://touchthemagic.com/cart.php" class="action-button">Znovu objednat</a>
            </div>
            
            <!-- Social Section -->
            <div class="social-section">
                <h3>Zůstaňte s námi v kontaktu</h3>
                <div class="social-links">
                    <a href="https://www.instagram.com/touchthemagic"><img alt="Instagram" src="https://touchthemagic.com/web_images/logo/instagram.png"></a>
                    <a href="https://www.facebook.com/touchthemagic"><img alt="Facebook" src="https://touchthemagic.com/web_images/logo/facebook.png"></a>
                    <a href="https://www.tiktok.com/@touchthemagic"><img alt="TikTok" src="https://touchthemagic.com/web_images/logo/tiktok.png"></a>
                    <a href="https://www.pinterest.com/touchthemagic"><img alt="Pinterest" src="https://touchthemagic.com/web_images/logo/pinterest.png"></a>
                    <a href="https://www.youtube.com/@touchthemagic"><img alt="YouTube" src="https://touchthemagic.com/web_images/logo/youtube.png"></a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>Beáta Fraibišová</p>
                <a href="tel:+420747473938">+420 747 473 938</a>
                <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                <a href="https://touchthemagic.com/page.php?page=obchodni-podminky">Podmínky</a>
                <a href="https://touchthemagic.com/page.php?page=kontakty" class="end">Kontakt</a>
            </div>
        </div>
    </body>
</html>
END;
}

function getOrderPaymentStatus($pdo, $order_number) {
    try {
        $stmt = $pdo->prepare("SELECT payment_status, invoice_pdf_path FROM orders_user WHERE order_number = :order_number");
        $stmt->execute([':order_number' => $order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            return [
                'payment_status' => $order['payment_status'],
                'invoice_pdf_path' => $order['invoice_pdf_path']
            ];
        }
        
        return [
            'payment_status' => 'pending',
            'invoice_pdf_path' => null
        ];
    } catch (PDOException $e) {
        return [
            'payment_status' => 'pending',
            'invoice_pdf_path' => null
        ];
    }
}

function calculateFinalPrice($totalPrice, $freeShipping, $paymentMethod) {
    $shippingCost = $freeShipping ? 0 : 79;
    $finalPrice = $totalPrice + $shippingCost;
    
    if ($paymentMethod == "Dobírka") {
        $finalPrice += 45;
    }
    
    return [
        'finalPrice' => $finalPrice,
        'shippingCost' => $shippingCost
    ];
}

function sendThankYouEmail($userEmail, $paymentStatus, $orderNumber, $currentDate, $totalAmount, $paymentMethod, $invoicePdfPath = null) {
    $emailSent = false;
    
    if ($paymentStatus == 'paid') {
        $emailBody = getEmailTemplatePaymentConfirmation($orderNumber, $currentDate, $totalAmount, $paymentMethod);
        $result = sendEmail($userEmail, "Zaplaceno!", $emailBody, $invoicePdfPath);
        $emailSent = true;
    } elseif ($paymentStatus == 'canceled') {
        $emailBody = getEmailTemplateCanceled($orderNumber, $currentDate, $totalAmount);
        $result = sendEmail($userEmail, "Platba zrušena", $emailBody);
        $emailSent = true;
    } elseif ($paymentStatus == 'timeouted') {
        $emailBody = getEmailTemplateTimeouted($orderNumber, $currentDate, $totalAmount);
        $result = sendEmail($userEmail, "Platba vypršela", $emailBody);
        $emailSent = true;
    }
    
    return $emailSent;
}

function renderPaymentStatus($paymentStatus, $orderNumber, $paymentMethod) {
    switch ($paymentStatus) {
        case 'paid':
            echo '<h2>Děkujeme, Vaše objednávka je zaplacena!</h2>';
            echo '<p>Rekapitulaci Vám zašleme i e-mailem.</p>';
            break;
        case 'canceled':
            echo '<h2>Platba byla zrušena</h2>';
            echo '<p>Vaše objednávka nebyla dokončena kvůli zrušení platby.</p>';
            break;
        case 'timeouted':
            echo '<h2>Platba vypršela</h2>';
            echo '<p>Čas pro dokončení platby vypršel. Objednávka nebyla dokončena.</p>';
            break;
        case 'pending':
        default:
            if($paymentMethod == "Dobírka"){
                echo '<h2>Děkujeme, Vaše objednávka se připravuje!</h2>';
                echo '<p>Rekapitulaci Vám zašleme i e-mailem.</p>';
            } else {
                echo '<h2>Zpracováváme Vaši platbu...</h2>';
                echo '<p>Počkejte prosím, dokud se nedokončí platební proces.</p>';
                echo '<a class="payment-info-button" href="checkout.php">Kliknutím zaplatíte objednávku</a>';
            }
            break;
    }
    
    if ($orderNumber) {
        echo '<p>Číslo vaší objednávky <span class="payment-info-status-color">'.$orderNumber.'</span></p>';
    }
}

function validate_input($input) {
    return validateInput($input);
}

function validate_hidden_input($input) {
    return validateHiddenInput($input);
}

function prepareAdminNotificationEmailFixed($userInfo, $boughtItems, $totalPrice, $orderNumber) {
    $emailTemplate = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Admin notifikace</title>
        <style type="text/css">
            /* Reset styles */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            /* Main styles */
            body {
                font-family: Arial, sans-serif !important;
                background-color: #ffffff;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
            }
            
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            .header {
                background-color: #fd7e14;
                padding: 20px;
                text-align: center;
            }
            
            .header h1 {
                color: #ffffff;
                font-size: 24px;
                margin: 0;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
            
            .content {
                padding: 30px 20px;
                background-color: #f8f9fa;
            }
            
            .order-info {
                background-color: #ffffff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .order-number {
                background-color: #251d3c;
                color: #ffffff;
                padding: 8px 12px;
                border-radius: 4px;
                font-weight: bold;
                display: inline-block;
            }
            
            .customer-section {
                background-color: #e9ecef;
                border-left: 4px solid #fd7e14;
                padding: 15px;
                margin: 15px 0;
            }
            
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                background-color: #ffffff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .products-table th {
                background-color: #251d3c;
                color: #ffffff;
                padding: 12px;
                text-align: left;
                font-weight: bold;
            }
            
            .products-table td {
                padding: 12px;
                border-bottom: 1px solid #f8f9fa;
            }
            
            .products-table tr:last-child td {
                border-bottom: none;
            }
            
            .total-row {
                background-color: #fd7e14;
                color: #ffffff;
                font-weight: bold;
                font-size: 16px;
            }
            
            .button {
                display: inline-block;
                background-color: #251d3c;
                color: #ffffff !important;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                text-transform: uppercase;
                margin: 20px 0;
            }
            
            .footer {
                background-color: #e5d7ee;
                padding: 20px;
                text-align: center;
                font-size: 13px;
            }
            
            .footer a {
                color: #251d3c;
                text-decoration: none;
                margin: 0 10px;
            }
        </style>
    </head>
    <body>
        <table cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa;">
            <tr>
                <td align="center">
                    <div class="email-container">
                        <!-- Header -->
                        <div class="header">
                            <h1>🛒 NOVÁ OBJEDNÁVKA</h1>
                        </div>
                        
                        <!-- Content -->
                        <div class="content">
                            <div class="order-info">
                                <h2 style="color: #251d3c; margin-top: 0;">Byla vytvořena nová objednávka</h2>
                                <p>Zákazník vytvořil novou objednávku číslo <span class="order-number">{{order_number}}</span> na vašem e-shopu.</p>
                                
                                <div class="customer-section">
                                    <h3 style="margin-top: 0; color: #251d3c;">Informace o zákazníkovi:</h3>
                                    <p><strong>Zákazník:</strong> {{customer_name}}</p>
                                    <p><strong>Email:</strong> {{customer_email}}</p>
                                    <p><strong>Telefon:</strong> {{customer_phone}}</p>
                                    <p><strong>Datum objednávky:</strong> {{order_date}}</p>
                                    <p><strong>Způsob platby:</strong> {{payment_method}}</p>
                                    <p><strong>Stav platby:</strong> {{payment_status}}</p>
                                </div>
                                
                                <div class="customer-section">
                                    <h3 style="margin-top: 0; color: #251d3c;">Dodací adresa:</h3>
                                    <p>{{delivery_name}}<br>
                                    {{delivery_address}}<br>
                                    {{delivery_city}}, {{delivery_zip}}</p>
                                </div>
                                
                                <h3 style="color: #251d3c;">Objednané produkty:</h3>
                                <table class="products-table">
                                    <thead>
                                        <tr>
                                            <th>Produkt</th>
                                            <th>Množství</th>
                                            <th>Cena za kus</th>
                                            <th>Celkem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{items_rows}}
                                        <tr class="total-row">
                                            <td colspan="3"><strong>CELKEM K ÚHRADĚ:</strong></td>
                                            <td><strong>{{order_total}} Kč</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <p><strong>Poznámka zákazníka:</strong> {{customer_note}}</p>
                                
                                <div style="text-align: center;">
                                    <a href="{{admin_order_link}}" class="button">Spravovat objednávku</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="footer">
                            <p>Beata Fraibišová | 
                            <a href="tel:+420747473938">+420 747 473 938</a> | 
                            <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a></p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>';

    $emailTemplate = str_replace('{{order_number}}', $orderNumber, $emailTemplate);
    $emailTemplate = str_replace('{{order_date}}', date('d.m.Y H:i'), $emailTemplate);
    $emailTemplate = str_replace('{{order_total}}', number_format($totalPrice, 0, ',', ' '), $emailTemplate);
    
    $emailTemplate = str_replace('{{customer_name}}', $userInfo['name'] . ' ' . $userInfo['surname'], $emailTemplate);
    $emailTemplate = str_replace('{{customer_email}}', $userInfo['email'], $emailTemplate);
    $emailTemplate = str_replace('{{customer_phone}}', $userInfo['phone'] ?? 'Neuvedeno', $emailTemplate);
    $emailTemplate = str_replace('{{payment_method}}', $userInfo['payment_method'], $emailTemplate);
    
    $paymentStatus = ($userInfo['payment_method'] == "Dobírka") ? "Dobírka - nezaplaceno" : "Čeká na platbu";
    $emailTemplate = str_replace('{{payment_status}}', $paymentStatus, $emailTemplate);
    
    $emailTemplate = str_replace('{{delivery_name}}', $userInfo['name'] . ' ' . $userInfo['surname'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_address}}', $userInfo['street'] . ' ' . $userInfo['housenumber'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_city}}', $userInfo['city'], $emailTemplate);
    $emailTemplate = str_replace('{{delivery_zip}}', $userInfo['zipcode'], $emailTemplate);
    
    $customerNote = isset($userInfo['note']) && !empty($userInfo['note']) ? $userInfo['note'] : 'Žádná poznámka';
    $emailTemplate = str_replace('{{customer_note}}', $customerNote, $emailTemplate);
    
    $adminLink = 'https://touchthemagic.com/admin/orders.php?order=' . $orderNumber;
    $emailTemplate = str_replace('{{admin_order_link}}', $adminLink, $emailTemplate);
    
    $itemsRows = '';
    $itemCounts = [];
    
    foreach ($boughtItems as $item) {
        $itemId = $item['id'];
        if (!isset($itemCounts[$itemId])) {
            $itemCounts[$itemId] = [
                'item' => $item,
                'quantity' => 1,
                'total' => $item['price']
            ];
        } else {
            $itemCounts[$itemId]['quantity']++;
            $itemCounts[$itemId]['total'] += $item['price'];
        }
    }
    
    foreach ($itemCounts as $itemData) {
        $item = $itemData['item'];
        $quantity = $itemData['quantity'];
        $total = $itemData['total'];
        
        $itemsRows .= '<tr>
            <td>' . htmlspecialchars($item['name']) . '</td>
            <td>' . $quantity . 'x</td>
            <td>' . number_format($item['price'], 0, ',', ' ') . ' Kč</td>
            <td>' . number_format($total, 0, ',', ' ') . ' Kč</td>
        </tr>';
    }
    
    $emailTemplate = str_replace('{{items_rows}}', $itemsRows, $emailTemplate);
    
    return $emailTemplate;
}
?>