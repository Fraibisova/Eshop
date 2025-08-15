<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    die();
}
function sendEmail($recipient, $subject, $body, $attachmentPath = null) {
    try {
        $mail = getMailer();
        $mail->setFrom("info@touchthemagic.com", "Touch The Magic");
        $mail->CharSet = 'UTF-8'; 
        $mail->Encoding = 'base64';

        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;

        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        $mail->send();
        return ['success' => true, 'message' => 'E-mail byl úspěšně odeslán.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "E-mail nemohl být odeslán. Chyba: " . $e->getMessage()];
    }
}

function newsletter($token){
    return <<<END
    <p>Klikněte <a href="https://touchthemagic.com/action/reset_password.php?token=$token">zde</a> 
    pro resetování hesla.</p>
    END;
}

function addToNewsletterSubscribers($email) {
    global $db;
    try {
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email = :email");
        $checkStmt->execute(['email' => $email]);
        
        if ($checkStmt->fetchColumn() > 0) {
            return true;
        }
        
        $insertStmt = $db->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at, active) VALUES (:email, NOW(), 1)");
        $insertStmt->execute(['email' => $email]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Chyba při přidávání do newsletter_subscribers: " . $e->getMessage());
        return false;
    }
}

function checkUserAuthentication() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: ../action/login.php");
        exit();
    }
}

function getUserData($pdo, $userId) {
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $userId]);
    return $stmt->fetch();
}

function aggregateCart() {
    $aggregated_cart = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $cartItem) {
            $id = $cartItem['id'];
            if (!isset($aggregated_cart[$id])) {
                $aggregated_cart[$id] = $cartItem;
            } else {
                $aggregated_cart[$id]['quantity'] += $cartItem['quantity'];
            }
        }
    }
    return $aggregated_cart;
}

function calculateCartPrice($aggregated_cart, $db) {
    $actualprice = 0;
    foreach ($aggregated_cart as $cartItem) {
        $id = $cartItem['id'];
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        $actualprice = $actualprice + $item['price'] * $cartItem['quantity'];
    }
    return $actualprice;
}

function validateName($name, $fieldName) {
    $errors = [];
    if (empty($name)) {
        $errors[] = $fieldName . " je povinné.";
    } elseif (strpos($name, ' ') !== false) {
        $errors[] = $fieldName . " nesmí obsahovat mezery.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúýčďěňřšťžÁÉÍÓÚÝČĎĚŇŘŠŤŽ]+$/u", $name)) {
        $errors[] = $fieldName . " musí obsahovat pouze písmena.";
    }
    return $errors;
}

function validateEmail($email, $pdo, $oldEmail = null) {
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
            if ($oldEmail != $email) {
                $errors[] = "Emailová adresa se již používá. Pokud chcete změnit email, použijte prosím jiný.";
            }
        }
    }
    return $errors;
}

function validatePhone($phone, $countryCode) {
    $errors = [];
    if (empty($phone)) {
        $errors[] = "Telefonní číslo je povinné.";
    } elseif ($countryCode == "+420" && mb_strlen($phone) != 9) {
        $errors[] = "Nemáte správný formát telefonního čísla";
    } elseif (strpos($phone, ' ') !== false) {
        $errors[] = "Telefonní číslo nesmí obsahovat mezery.";
    }
    return $errors;
}

function validateAddress($street, $number, $city, $zipcode, $country) {
    $errors = [];
    
    if (empty($street)) {
        $errors[] = 'Street is required.';
    } elseif (!preg_match("/^[a-zA-ZáéíóúýčďěňřšťžÁÉÍÓÚÝČĎĚŇŘŠŤŽ]+$/u", $street)) {
        $errors[] = 'Street should not contain numbers or special characters.';
    }

    if (empty($number)) {
        $errors[] = 'House number is required.';
    } elseif (!preg_match('/^\d+$/', $number)) {
        $errors[] = 'House number should contain only numbers.';
    }

    if (empty($city)) {
        $errors[] = 'City is required.';
    } elseif (!preg_match("/^[a-zA-ZáéíóúýčďěňřšťžÁÉÍÓÚÝČĎĚŇŘŠŤŽ]+$/u", $city)) {
        $errors[] = 'City should not contain numbers or special characters.';
    }

    if (empty($zipcode)) {
        $errors[] = 'Zip code is required.';
    } elseif (!preg_match('/^\d{5}$/', $zipcode)) {
        $errors[] = 'Zip code should contain exactly 5 digits.';
    }

    if (empty($country)) {
        $errors[] = 'Country is required.';
    }
    
    return $errors;
}

function displayErrorMessages() {
    if (isset($_SESSION['errors'])) {
        foreach ($_SESSION['errors'] as $value) {
            echo "<p class='red'>" . htmlspecialchars($value) . "</p>";
        }
        unset($_SESSION['errors']);
    }
}

function displaySuccessMessage() {
    if (isset($_SESSION['success'])) {
        echo $_SESSION['success'];
        unset($_SESSION['success']);
    }
}

function renderAccountNavigation() {
    return '
    <section class="other-category">
        <div class="other-categories">
            <a href="my_account.php" class="one-other-category bo">
                <p>Osobní údaje</p>
            </a>
            <a href="billing_address.php" class="one-other-category bo">
                <p>Nastavení fakturační adresy</p>
            </a>
            <a href="change_password.php" class="one-other-category bo">
                <p>Reset hesla</p>
            </a>
            <a href="../action/logout.php" class="one-other-category bo">
                <p>Odhlásit se</p>
            </a>
        </div>
    </section>';
}

function calculateFreeShippingProgress($totalPrice, $freeShippingLimit = 1500) {
    $percentage = min(100, ($totalPrice / $freeShippingLimit) * 100);
    $remaining = $freeShippingLimit - $totalPrice;
    
    return [
        'percentage' => $percentage,
        'remaining' => max(0, $remaining),
        'qualifiesForFreeShipping' => $totalPrice >= $freeShippingLimit
    ];
}

function renderBreadcrumb($breadcrumbs) {
    $html = '<div class="path">';
    
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="path-margin" width="16" height="16" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
        <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
    </svg>';
    
    foreach ($breadcrumbs as $index => $breadcrumb) {
        $html .= '<div class="other-path-margin">';
        
        if (isset($breadcrumb['url'])) {
            $html .= '<a href="' . htmlspecialchars($breadcrumb['url']) . '">' . htmlspecialchars($breadcrumb['title']) . '</a>';
        } else {
            $html .= '<p class="other-path-p">' . htmlspecialchars($breadcrumb['title']) . '</p>';
        }
        
        $html .= '</div>';
        
        if ($index < count($breadcrumbs) - 1) {
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-right" viewBox="0 0 16 16">
                <path d="M6 12.796V3.204L11.481 8zm.659.753 5.48-4.796a1 1 0 0 0 0-1.506L6.66 2.451C6.011 1.885 5 2.345 5 3.204v9.592a1 1 0 0 0 1.659.753"/>
            </svg>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

function renderProductCard($item) {
    $image = htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8');
    $price = htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8');
    $id = htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8');
    
    return '<div class="product">
        <img src="' . $image . '" alt="' . $name . '">
        <a href="product.php?id=' . $id . '"><h3>' . $name . '</h3></a>
        <p class="price">' . $price . 'Kč</p>
        <form method="post" action="">
            <input type="hidden" name="item_id" value="' . $id . '">
            <button type="submit" class="btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10.5 3.5a2.5 2.5 0 0 0-5 0V4h5zm1 0V4H15v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4h3.5v-.5a3.5 3.5 0 1 1 7 0M14 14V5H2v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1M8 7.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                </svg>
                <p class="add-to-cart">Přidat do košíku</p>
            </button>
        </form>
    </div>';
}

function getRandomProducts($pdo, $limit = 4) {
    $sql = "SELECT * FROM items WHERE visible = 1 AND stock = 'Skladem' ORDER BY RAND() LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchProducts($pdo, $query) {
    $sql = 'SELECT * FROM items WHERE name LIKE :query AND visible = 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => "%$query%"]);
    return $stmt->fetchAll();
}

function getProductsByCategory($pdo, $category) {
    $sql = "SELECT * FROM items WHERE visible = 1 AND category = :category";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['category' => $category]);
    return $stmt->fetchAll();
}

function generateNextOrderNumber($pdo) {
    $query = $pdo->query("SELECT MAX(order_number) as max_order FROM orders_user");
    $maxOrder = $query->fetch(PDO::FETCH_ASSOC);
    return ($maxOrder['max_order'] ?? 0) + 1;
}

function validateOrderNumber($pdo, $orderNumber) {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders_user WHERE order_number = :order_number");
    $checkStmt->execute(['order_number' => $orderNumber]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    return $exists['count'] == 0;
}

function processCartToOrder($pdo, $aggregated_cart, $orderNumber) {
    $totalPrice = 0;
    
    foreach ($aggregated_cart as $cartItem) {
        $id = $cartItem['id'];
        $quantity = $cartItem['quantity'];

        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        if ($item) {
            $itemPrice = $item['price'] * $quantity; 
            $totalPrice += $itemPrice;

            $insertSql = "INSERT INTO orders_items (id_product, order_number, count) VALUES (:id_product, :order_number, :count)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                'id_product' => $id,
                'order_number' => $orderNumber,
                'count' => $quantity
            ]);
        }
    }
    
    return $totalPrice;
}

function addToCart($product, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $_SESSION['cart'][] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity
    ];
}

function removeFromCart($itemId) {
    if (isset($_SESSION['cart'][$itemId])) {
        unset($_SESSION['cart'][$itemId]);
    }
}

function updateCartQuantity($itemId, $action) {
    if (!isset($_SESSION['cart'][$itemId])) {
        return false;
    }
    
    if ($action === 'increase') {
        $_SESSION['cart'][$itemId]['quantity']++;
    } elseif ($action === 'decrease') {
        if ($_SESSION['cart'][$itemId]['quantity'] > 1) {
            $_SESSION['cart'][$itemId]['quantity']--;
        } else {
            unset($_SESSION['cart'][$itemId]);
        }
    }
    
    return true;
}

?>