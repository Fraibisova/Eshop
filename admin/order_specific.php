<?php
define('APP_ACCESS', true);

include '../config.php';
include '../lib/function.php';
include '../lib/function_admin.php';
require "../mailer.php";

session_start();
checkAdminRole();
if (!isset($_GET['order_number']) || !is_numeric($_GET['order_number'])) {
    die("Neplatné číslo objednávky.");
}

$order_number = intval($_GET['order_number']);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_status']) && in_array($_POST['order_status'], ['waiting', 'cancel', 'completed', 'processed', 'send'])) {
        $new_status = $_POST['order_status'];
        $tracking_number = isset($_POST['tracking_number']) ? trim($_POST['tracking_number']) : '';
        
        $old_status_sql = "SELECT order_status FROM orders_user WHERE order_number = :order_number";
        $old_status_stmt = $pdo->prepare($old_status_sql);
        $old_status_stmt->execute(['order_number' => $order_number]);
        $old_status = $old_status_stmt->fetchColumn();

        $customer_sql = "SELECT 
                            ou.email, 
                            ou.price, 
                            ou.name, 
                            ou.surname, 
                            ou.street, 
                            ou.house_number, 
                            ou.city, 
                            ou.zipcode,
                            ou.tracking_number as current_tracking
                        FROM orders_user ou 
                        WHERE ou.order_number = :order_number";
        $customer_stmt = $pdo->prepare($customer_sql);
        $customer_stmt->execute(['order_number' => $order_number]);
        $customer_data = $customer_stmt->fetch();

        $update_params = ['order_status' => $new_status, 'order_number' => $order_number];
        $update_sql = "UPDATE orders_user SET order_status = :order_status";
        
        if (!empty($tracking_number)) {
            $update_sql .= ", tracking_number = :tracking_number";
            $update_params['tracking_number'] = $tracking_number;
        }
        
        $update_sql .= " WHERE order_number = :order_number";
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute($update_params);

        if ($result && $customer_data) {
            $email_sent = false;
            $email_message = '';

            switch ($new_status) {
                case 'send':
                    if ($old_status !== 'send') {
                        $final_tracking = !empty($tracking_number) ? $tracking_number : 
                                        (!empty($customer_data['current_tracking']) ? $customer_data['current_tracking'] : 
                                        "CZ" . str_pad($order_number, 9, '0', STR_PAD_LEFT));
                        
                        $subject = "Objednávka #{$order_number} byla odeslána - Touch The Magic";
                        $body = getShippingEmailTemplate(
                            $order_number,
                            $final_tracking,
                            "Zásilkovna",
                            $customer_data['name'] . ' ' . $customer_data['surname'],
                            $customer_data['street'] . ' ' . $customer_data['house_number'],
                            $customer_data['city'],
                            $customer_data['zipcode']
                        );
                        $email_result = sendEmail($customer_data['email'], $subject, $body);
                        $email_sent = $email_result['success'];
                        $email_message = $email_result['message'];
                    }
                    break;

                case 'cancel':
                    if ($old_status !== 'cancel') {
                        $subject = "Objednávka #{$order_number} byla zrušena - Touch The Magic";
                        $body = getCancellationEmailTemplate($order_number, $customer_data['price'] ?? '0');
                        $email_result = sendEmail($customer_data['email'], $subject, $body);
                        $email_sent = $email_result['success'];
                        $email_message = $email_result['message'];
                    }
                    break;
            }

            echo "<p class='success-message'>Stav objednávky byl úspěšně aktualizován na: " . htmlspecialchars($new_status) . "</p>";
            
            if (!empty($tracking_number)) {
                echo "<p class='success-message'>Sledovací číslo bylo úspěšně uloženo: " . htmlspecialchars($tracking_number) . "</p>";
            }
            
            if ($email_sent) {
                echo "<p class='success-message'>Email byl úspěšně odeslán zákazníkovi.</p>";
            } elseif (!empty($email_message)) {
                echo "<p class='warning-message'>Upozornění: " . htmlspecialchars($email_message) . "</p>";
            }
        } else {
            echo "<p class='error-message'>Chyba při aktualizaci stavu objednávky.</p>";
        }
    }
    
    if (isset($_POST['update_tracking']) && isset($_POST['tracking_number_only'])) {
        $tracking_number = trim($_POST['tracking_number_only']);
        
        if (!empty($tracking_number)) {
            $update_tracking_sql = "UPDATE orders_user SET tracking_number = :tracking_number WHERE order_number = :order_number";
            $update_tracking_stmt = $pdo->prepare($update_tracking_sql);
            $result = $update_tracking_stmt->execute([
                'tracking_number' => $tracking_number,
                'order_number' => $order_number
            ]);
            
            if ($result) {
                echo "<p class='success-message'>Sledovací číslo bylo úspěšně aktualizováno: " . htmlspecialchars($tracking_number) . "</p>";
            } else {
                echo "<p class='error-message'>Chyba při aktualizaci sledovacího čísla.</p>";
            }
        } else {
            echo "<p class='error-message'>Sledovací číslo nemůže být prázdné.</p>";
        }
    }
    
    if (isset($_POST['send_shipping_email'])) {
        $customer_sql = "SELECT 
                            ou.email, 
                            ou.name, 
                            ou.surname, 
                            ou.street, 
                            ou.house_number, 
                            ou.city, 
                            ou.zipcode,
                            ou.tracking_number,
                            ou.invoice_pdf_path,
                            ou.order_status
                        FROM orders_user ou 
                        WHERE ou.order_number = :order_number";
        $customer_stmt = $pdo->prepare($customer_sql);
        $customer_stmt->execute(['order_number' => $order_number]);
        $customer_data = $customer_stmt->fetch();
        
        if ($customer_data) {
            $final_tracking = !empty($customer_data['tracking_number']) ? $customer_data['tracking_number'] : 
                            "CZ" . str_pad($order_number, 9, '0', STR_PAD_LEFT);
            
            $subject = "Objednávka #{$order_number} byla odeslána - Touch The Magic";
            $body = getShippingEmailTemplate(
                $order_number,
                $final_tracking,
                "Zásilkovna",
                $customer_data['name'] . ' ' . $customer_data['surname'],
                $customer_data['street'] . ' ' . $customer_data['house_number'],
                $customer_data['city'],
                $customer_data['zipcode']
            );
            $email_result = sendEmail($customer_data['email'], $subject, $body, $customer_data['invoice_pdf_path']);
            
            if ($email_result['success']) {
                echo "<p class='success-message'>Expediční email byl úspěšně odeslán zákazníkovi.</p>";
            } else {
                echo "<p class='error-message'>Chyba při odesílání emailu: " . htmlspecialchars($email_result['message']) . "</p>";
            }
        } else {
            echo "<p class='error-message'>Nepodařilo se načíst data objednávky.</p>";
        }
    }
}

$sql = "
    SELECT 
        oi.id AS order_item_id,
        oi.id_product,
        oi.order_number,
        oi.count,
        i.name,
        i.product_code,
        i.description_main,
        i.price,
        i.price_without_dph
    FROM orders_items oi
    JOIN items i ON oi.id_product = i.id
    WHERE oi.order_number = :order_number
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['order_number' => $order_number]);
$order_items = $stmt->fetchAll();

if (!$order_items) {
    die("Pro tuto objednávku nebyly nalezeny žádné položky.");
}

$info_sql = "SELECT order_status, tracking_number FROM orders_user WHERE order_number = :order_number";
$info_stmt = $pdo->prepare($info_sql);
$info_stmt->execute(['order_number' => $order_number]);
$order_info = $info_stmt->fetch();
$current_status = $order_info['order_status'];
$current_tracking = $order_info['tracking_number'];
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail objednávky #<?php echo htmlspecialchars($order_number); ?></title>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
<?php adminHeader(); ?>

    <div class="container">
        <h1>Detail objednávky #<?php echo htmlspecialchars($order_number); ?></h1>
        
        <div class="tracking-section">
            <h3>Sledovací číslo</h3>
            <?php if (!empty($current_tracking)): ?>
                <div class="tracking-current">
                    <strong>Aktuální sledovací číslo:</strong> <?php echo htmlspecialchars($current_tracking); ?>
                </div>
            <?php else: ?>
                <p class="muted-text">Sledovací číslo není zatím nastaveno.</p>
            <?php endif; ?>
            
            <form method="post" class="form-inline">
                <div class="form-group">
                    <label for="tracking_number_only">Nové sledovací číslo:</label>
                    <input type="text" 
                           name="tracking_number_only" 
                           id="tracking_number_only" 
                           class="tracking-input"
                           value="<?php echo htmlspecialchars($current_tracking ?? ''); ?>"
                           placeholder="Zadejte sledovací číslo">
                </div>
                <button type="submit" name="update_tracking" class="btn btn-secondary">Aktualizovat sledovací číslo</button>
            </form>
            
            <?php if ($current_status === 'send' && !empty($current_tracking)): ?>
                <form method="post" class="shipping-email-form">
                    <button type="submit" name="send_shipping_email" class="btn btn-success">
                        Odeslat expediční email s aktuálním sledovacím číslem
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID Produktu</th>
                    <th>Číslo Objednávky</th>
                    <th>Název</th>
                    <th>Kód Produktu</th>
                    <th>Popis</th>
                    <th>Cena</th>
                    <th>Počet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['order_item_id']); ?></td>
                    <td><?php echo htmlspecialchars($item['id_product']); ?></td>
                    <td><?php echo htmlspecialchars($item['order_number']); ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                    <td><?php echo htmlspecialchars($item['description_main']); ?></td>
                    <td><?php echo htmlspecialchars($item['price']); ?> Kč</td>
                    <td><?php echo htmlspecialchars($item['count']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Změna stavu objednávky</h2>
        <form method="post">
            <div class="form-group">
                <label for="order_status">Vyberte nový stav:</label>
                <select name="order_status" id="order_status">
                    <option value="waiting" <?php echo ($current_status === 'waiting') ? 'selected' : ''; ?>>Čeká na zpracování</option>
                    <option value="processed" <?php echo ($current_status === 'processed') ? 'selected' : ''; ?>>Zpracováno</option>
                    <option value="send" <?php echo ($current_status === 'send') ? 'selected' : ''; ?>>Odesláno</option>
                    <option value="completed" <?php echo ($current_status === 'completed') ? 'selected' : ''; ?>>Dokončeno</option>
                    <option value="cancel" <?php echo ($current_status === 'cancel') ? 'selected' : ''; ?>>Zrušena</option>
                </select>
            </div>
            
            <div class="form-group" id="tracking_group" class="tracking-group-hidden">
                <label for="tracking_number">Sledovací číslo (volitelné):</label>
                <input type="text" 
                       name="tracking_number" 
                       id="tracking_number" 
                       class="tracking-input"
                       value="<?php echo htmlspecialchars($current_tracking ?? ''); ?>"
                       placeholder="Zadejte sledovací číslo">
                <small class="tracking-help-text">
                    Pokud nevyplníte, použije se aktuální sledovací číslo nebo se vygeneruje automaticky.
                </small>
            </div>
            
            <button type="submit" class="btn">Uložit změny</button>
        </form>
        
        <div class="email-info-box">
            <h3>Informace o emailových notifikacích:</h3>
            <ul>
                <li><strong>Odesláno:</strong> Zákazník obdrží email s informacemi o expedici a sledovacím číslem</li>
                <li><strong>Zrušena:</strong> Zákazník obdrží email o zrušení objednávky</li>
                <li><strong>Ostatní stavy:</strong> Email se neodešle automaticky</li>
            </ul>
            <h4>Sledovací čísla:</h4>
            <ul>
                <li>Můžete nastavit sledovací číslo kdykoli pomocí formuláře výše</li>
                <li>Při změně stavu na "Odesláno" můžete zadat nové sledovací číslo</li>
                <li>Pokud je objednávka ve stavu "Odesláno", můžete kdykoliv odeslat expediční email znovu</li>
                <li>Pokud sledovací číslo není zadáno, vygeneruje se automaticky ve formátu CZ + číslo objednávky</li>
            </ul>
        </div>
    </div>
    
    <a href="orders.php" class="back-link">Zpět</a>

    <script src="../js/order_specific.js"></script>
</body>
</html>