<?php
if (!defined('APP_ACCESS')) {
    die('Přímý přístup není povolen');
}

function checkAdminRole() {
    if(!isset($_SESSION['role']) or $_SESSION['role'] != 10){
        header('location: ../index.php');
        exit();
    }
}

function calculatePagination($totalItems, $itemsPerPage, $currentPage = 1) {
    $currentPage = max($currentPage, 1);
    $offset = ($currentPage - 1) * $itemsPerPage;
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    return [
        'offset' => $offset,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

function renderPagination($totalPages, $currentPage, $baseUrl = '?', $getParams = []) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    for ($i = 1; $i <= $totalPages; $i++) {
        $params = array_merge($getParams, ['page' => $i]);
        $url = $baseUrl . http_build_query($params);
        $activeClass = ($i === $currentPage) ? 'active' : '';
        $html .= '<a href="' . htmlspecialchars($url) . '" class="' . $activeClass . '">' . $i . '</a>';
    }
    
    $html .= '</div>';
    return $html;
}

function handleFileUpload($fileKey, $uploadDir = 'uploads/') {
    if (empty($_FILES[$fileKey]['name'])) {
        return '';
    }
    
    $filename = basename($_FILES[$fileKey]['name']);
    $targetPath = '../' . $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
        return $uploadDir . $filename;
    }
    
    return false;
}

function buildDynamicQuery($table, $columns, $getParams, $orderBy = 'id DESC') {
    $filters = [];
    $params = [];
    
    foreach ($columns as $column) {
        if (!empty($getParams[$column])) {
            $filters[] = "$column LIKE :$column";
            $params[$column] = '%' . $getParams[$column] . '%';
        }
    }
    
    $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
    $query = "SELECT * FROM $table $whereClause ORDER BY $orderBy";
    $countQuery = "SELECT COUNT(*) FROM $table $whereClause";
    
    return [
        'query' => $query,
        'countQuery' => $countQuery,
        'params' => $params
    ];
}

function executeCountQuery($pdo, $countQuery, $params) {
    try {
        $stmt = $pdo->prepare($countQuery);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        die("Chyba při počítání záznamů: " . $e->getMessage());
    }
}

function executePaginatedQuery($pdo, $query, $params, $offset, $limit) {
    try {
        $stmt = $pdo->prepare($query . ' LIMIT :limit OFFSET :offset');
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Chyba při načítání dat: " . $e->getMessage());
    }
}

function displaySuccessMessage($message) {
    echo "<p style='color: green;'>" . htmlspecialchars($message) . "</p>";
}

function displayErrorMessage($message) {
    echo "<p style='color: red;'>" . htmlspecialchars($message) . "</p>";
}

function processProductInsert($pdo, $postData, $imageFile = null) {
    try {
        $image = '';
        if ($imageFile && !empty($imageFile['name'])) {
            $image = handleFileUpload('image');
            if ($image === false) {
                throw new Exception("Chyba při nahrávání obrázku");
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO items (name, product_code, description_main, price, price_without_dph, image, image_folder, mass, visible, category, stock) 
            VALUES (:name, :product_code, :description_main, :price, :price_without_dph, :image, :image_folder, :mass, :visible, :category, :stock)");
        
        $stmt->execute([
            ':name' => $postData['name'],
            ':product_code' => $postData['product_code'],
            ':description_main' => $postData['description_main'],
            ':price' => $postData['price'],
            ':price_without_dph' => $postData['price_without_dph'],
            ':image' => $image,
            ':image_folder' => $postData['image_folder'],
            ':mass' => $postData['mass'],
            ':visible' => $postData['visible'],
            ':category' => $postData['category'],
            ':stock' => $postData['stock_status'],
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        throw new Exception("Chyba při ukládání produktu: " . $e->getMessage());
    }
}

function processProductDescription($pdo, $itemId, $postData) {
    try {
        $stmt = $pdo->prepare("INSERT INTO items_description (id_item, paragraph1, paragraph2, paragraph3, paragraph4, paragraph5, paragraph6) 
            VALUES (:id_item, :paragraph1, :paragraph2, :paragraph3, :paragraph4, :paragraph5, :paragraph6)
            ON DUPLICATE KEY UPDATE 
            paragraph1 = VALUES(paragraph1), paragraph2 = VALUES(paragraph2), paragraph3 = VALUES(paragraph3),
            paragraph4 = VALUES(paragraph4), paragraph5 = VALUES(paragraph5), paragraph6 = VALUES(paragraph6)");
        
        $stmt->execute([
            ':id_item' => $itemId,
            ':paragraph1' => $postData['paragraph1'] ?? '',
            ':paragraph2' => $postData['paragraph2'] ?? '',
            ':paragraph3' => $postData['paragraph3'] ?? '',
            ':paragraph4' => $postData['paragraph4'] ?? '',
            ':paragraph5' => $postData['paragraph5'] ?? '',
            ':paragraph6' => $postData['paragraph6'] ?? '',
        ]);
        
        return true;
    } catch (PDOException $e) {
        throw new Exception("Chyba při ukládání popisu: " . $e->getMessage());
    }
}

function processProductUpdate($pdo, $postData, $currentImage = '', $imageFile = null) {
    try {
        $image = $currentImage;
        if ($imageFile && !empty($imageFile['name'])) {
            $uploadResult = handleFileUpload('image');
            if ($uploadResult !== false) {
                $image = $uploadResult;
            }
        }
        
        $stock = (isset($postData['stock']) && $postData['stock'] >= 1) ? 'Skladem' : 'Není skladem';
        
        $stmt = $pdo->prepare("
            UPDATE items 
            SET name = :name, 
                product_code = :product_code, 
                description_main = :description_main, 
                price = :price, 
                price_without_dph = :price_without_dph, 
                image = :image, 
                image_folder = :image_folder, 
                mass = :mass, 
                visible = :visible, 
                category = :category, 
                stock = :stock 
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $postData['id'],
            ':name' => $postData['name'],
            ':product_code' => $postData['product_code'],
            ':description_main' => $postData['description_main'],
            ':price' => $postData['price'],
            ':price_without_dph' => $postData['price_without_dph'],
            ':image' => $image,
            ':image_folder' => $postData['image_folder'],
            ':mass' => $postData['mass'],
            ':visible' => $postData['visible'],
            ':category' => $postData['category'],
            ':stock' => $stock,
        ]);
        
        return true;
    } catch (PDOException $e) {
        throw new Exception("Chyba při aktualizaci produktu: " . $e->getMessage());
    }
}

function getProductById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Chyba při načítání produktu: " . $e->getMessage());
    }
}

function renderFilterForm($columns, $getParams = []) {
    $html = '<form method="GET">';
    
    foreach ($columns as $column) {
        $value = isset($getParams[$column]) ? htmlspecialchars($getParams[$column]) : '';
        $placeholder = ucfirst(str_replace('_', ' ', $column));
        $html .= '<input type="text" name="' . htmlspecialchars($column) . '" placeholder="' . $placeholder . '" value="' . $value . '">';
    }
    
    $html .= '<button type="submit">Filtrovat</button>';
    $html .= '</form>';
    
    return $html;
}

function renderStatusFilter($currentStatus = '') {
    $html = '<form method="get" class="filter">
        <label>Filtrovat podle statusu:
            <select name="status" onchange="this.form.submit()">
                <option value="">-- Všechny --</option>
                <option value="draft"' . ($currentStatus === 'draft' ? ' selected' : '') . '>Rozpracovaný</option>
                <option value="ready"' . ($currentStatus === 'ready' ? ' selected' : '') . '>Připravený</option>
                <option value="sent"' . ($currentStatus === 'sent' ? ' selected' : '') . '>Odeslaný</option>
            </select>
        </label>
    </form>';
    
    return $html;
}

function logMessage($message) {
    $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
    $logFile = __DIR__ . '/../admin/newsletter_cron.log';
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
}

function generateUnsubscribeToken($email) {
    $secret = 'cE9vYzP7kG5aJ1mQxR2tU8nLwB0hXsMd';
    return hash('sha256', $email . $secret);
}

function getUnsubscribeLink($email, $base_url = 'https://touchthemagic.com') {
    $token = generateUnsubscribeToken($email);
    return $base_url . '/action/unsubscribe.php?email=' . urlencode($email) . '&token=' . $token;
}

function getNewsletterTemplate() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM newsletter_templates WHERE name='Basic' LIMIT 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Chyba při načítání šablony: " . $e->getMessage());
        logMessage("Chyba při načítání šablony: " . $e->getMessage());
        return [];
    }
}

function sendNewsletterToSubscribers($newsletter_id, $title, $template_with_placeholders) {
    global $pdo;

    try {
        logMessage("🚀 Začínám odesílání newsletteru ID: $newsletter_id s názvem: $title");
        
        $stmt = $pdo->prepare("UPDATE newsletters SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $stmt->execute([$newsletter_id]);
        
        $stmt = $pdo->prepare("SELECT email FROM newsletter_subscribers WHERE active = 1");
        $stmt->execute();
        $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($subscribers)) {
            logMessage("❌ Žádní aktivní odběratelé pro newsletter ID: $newsletter_id");
            return 0;
        }

        logMessage("📧 Nalezeno " . count($subscribers) . " aktivních odběratelů");

        $sent_count = 0;
        $failed_count = 0;
        
        foreach ($subscribers as $email) {
            try {
                $personalized_html = str_replace('{{unsubscribe}}', getUnsubscribeLink($email), $template_with_placeholders);

                $result = sendEmail($email, $title, $personalized_html);
                
                if ($result['success']) {
                    $sent_count++;
                    logMessage("✅ Úspěšně odesláno na: $email");
                } else {
                    $failed_count++;
                    logMessage("❌ Nepodařilo se odeslat na: $email - " . $result['message']);
                    
                    if ($failed_count > 5 && $sent_count == 0) {
                        logMessage("⚠️ Příliš mnoho chyb na začátku - přerušujem odesílání");
                        break;
                    }
                }
                
                // 2s
                usleep(200000); 
                
            } catch (Exception $e) {
                $failed_count++;
                logMessage("💥 Výjimka při odesílání na $email: " . $e->getMessage());
            }
        }

        logMessage("📊 Newsletter ID: $newsletter_id dokončen. Úspěšně: $sent_count, Neúspěšně: $failed_count z celkem " . count($subscribers));
        
        return $sent_count;
    } catch (Exception $e) {
        logMessage("💥 KRITICKÁ CHYBA při odesílání newsletteru ID $newsletter_id: " . $e->getMessage());
        
        try {
            $stmt = $pdo->prepare("UPDATE newsletters SET status = 'draft' WHERE id = ?");
            $stmt->execute([$newsletter_id]);
        } catch (Exception $updateError) {
            logMessage("💥 Chyba při aktualizaci statusu: " . $updateError->getMessage());
        }
        
        return false;
    }
}


function generatePreview($templateData, $postData = []) {
    if (empty($templateData)) {
        return '<html><body><h1>Chyba: Šablona nebyla nalezena</h1></body></html>';
    }
    
    $template = $templateData[0]['html_template'];
    
    $title = $postData['main_title'] ?? 'Ukázkový titulek';
    $subtitle = $postData['subtitle'] ?? 'Ukázkový podtitulek';
    $title_paragraph = $postData['title_paragraph'] ?? 'Ukázkový úvodní text';
    $text_block = $postData['text_block'] ?? 'Ukázkový textový blok newsletteru';
    $main_image = $postData['main_image'] ?? '';
    $shop_link = $postData['shop_link'] ?? '#';
    $shop_text = $postData['shop_text'] ?? 'Navštívit obchod';
    
    $dummy_email = 'info@touchthemagic.com';
    $unsubscribe_link = getUnsubscribeLink($dummy_email);
    
    $template = str_replace(['{{main_title}}', 'Ahoj'], htmlspecialchars($title), $template);
    $template = str_replace('{{subtitle}}', htmlspecialchars($subtitle), $template);
    $template = str_replace('{{title_paragraph}}', htmlspecialchars($title_paragraph), $template);
    $template = str_replace('{{text_block}}', nl2br(htmlspecialchars($text_block)), $template);
    $template = str_replace('{{main_image}}', htmlspecialchars($main_image), $template);
    $template = str_replace('{{shop_link}}', htmlspecialchars($shop_link), $template);
    $template = str_replace('{{shop_text}}', htmlspecialchars($shop_text), $template);
    $template = str_replace('{{unsubscribe}}', htmlspecialchars($unsubscribe_link), $template);
    
    $products_html = '';
    if (isset($postData['products']) && is_array($postData['products'])) {
        foreach ($postData['products'] as $product) {
            if (!empty($product['title']) || !empty($product['image'])) {
                $products_html .= "<div class='product'>\n";
                $products_html .= "  <img alt='produkt' src='" . htmlspecialchars($product['image'] ?? '') . "'>\n";
                $products_html .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info'>" . htmlspecialchars($product['title'] ?? '') . "</a>\n";
                $products_html .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info des'>" . htmlspecialchars($product['description'] ?? '') . "</a>\n";
                $products_html .= "  <a class='more' href='".htmlspecialchars($product['button_link'] ?? '#')."'>Více</a>\n";
                $products_html .= "</div>\n";
            }
        }
    }
    
    $template = str_replace(['{{#each products}}', '{{/each}}'], '', $template);
    $template = str_replace('{{products}}', $products_html, $template);
    
    $fullHtml = "<!DOCTYPE html>\n";
    $fullHtml .= "<html lang='cs'>\n";
    $fullHtml .= "<head>\n";
    $fullHtml .= "<meta charset='UTF-8'>\n";
    $fullHtml .= "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    $fullHtml .= "<title>Náhled newsletteru</title>\n";
    $fullHtml .= "</head>\n";
    $fullHtml .= "<body>\n";
    $fullHtml .= $template . "\n";
    $fullHtml .= "</body>\n";
    $fullHtml .= "</html>";
    
    return $fullHtml;
}


function saveNewsletter($title, $html_content, $status, $scheduled_at = null, $id = null) {
    global $pdo;
    
    try {
        $valid_statuses = ['draft', 'ready', 'sent', 'scheduled', 'sending', 'failed'];
        if (!in_array($status, $valid_statuses)) {
            $status = 'draft';
        }
        
        if ($id) {
            $sql = "UPDATE newsletters SET title = ?, content = ?, status = ?, scheduled_at = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $html_content, $status, $scheduled_at, $id]);
            logMessage("Newsletter aktualizován ID: $id, status: $status, scheduled_at: $scheduled_at");
            return $id;
        } else {
            $sql = "INSERT INTO newsletters (title, content, status, scheduled_at, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $html_content, $status, $scheduled_at]);
            
            $id = $pdo->lastInsertId();
            logMessage("Newsletter uložen s ID: $id, status: $status, scheduled_at: $scheduled_at");
            return $id;
        }
    } catch (Exception $e) {
        error_log("Chyba při ukládání newsletteru: " . $e->getMessage());
        logMessage("Chyba při ukládání newsletteru: " . $e->getMessage());
        return false;
    }
}

function deleteProduct($pdo, $id) {
    try {
        $pdo->beginTransaction();
        
        $stmtDescription = $pdo->prepare("DELETE FROM items_description WHERE id_item = :id");
        $stmtDescription->execute([':id' => $id]);
        
        $stmtItems = $pdo->prepare("DELETE FROM items WHERE id = :id");
        $stmtItems->execute([':id' => $id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollback();
        throw new Exception("Chyba při mazání produktu: " . $e->getMessage());
    }
}

function deleteNewsletter($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM newsletters WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch (Exception $e) {
        throw new Exception("Chyba při mazání newsletteru: " . $e->getMessage());
    }
}
function get_existing_content($section) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT content FROM homepage_content WHERE section = :section LIMIT 1");
    $stmt->execute(['section' => $section]);
    $row = $stmt->fetch();
    return $row ? $row['content'] : '';
}

function sendSimpleEmail($to, $subject, $html_content) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: newsletter@touchthemagic.com' . "\r\n";
    $headers .= 'Reply-To: newsletter@touchthemagic.com' . "\r\n";
    
    return mail($to, $subject, $html_content, $headers);
}


function checkAndSendScheduledNewsletters() {
    global $pdo;
    
    try {
        logMessage("Kontrolujem naplánované newslettery");
        
        $stmt = $pdo->prepare("
            SELECT id, title, content, scheduled_at 
            FROM newsletters 
            WHERE status = 'scheduled' 
            AND scheduled_at <= NOW()
            ORDER BY scheduled_at ASC
        ");
        $stmt->execute();
        $newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($newsletters)) {
            logMessage("Žádné newslettery k odeslání");
            return 0;
        }
        
        logMessage("Nalezeno " . count($newsletters) . " newsletterů k odeslání");
        
        $total_sent = 0;
        foreach ($newsletters as $newsletter) {
            logMessage("Zpracovávám newsletter ID: " . $newsletter['id'] . " - " . $newsletter['title']);
            
            $sent_count = sendNewsletterToSubscribers($newsletter['id'], $newsletter['title'], $newsletter['content']);
            
            if ($sent_count !== false) {
                $total_sent += $sent_count;
            }
        }
        
        return $total_sent;
        
    } catch (Exception $e) {
        logMessage("CHYBA při kontrole naplánovaných newsletterů: " . $e->getMessage());
        return false;
    }
}

function generateNewsletterPreview($templateData, $postData = []) {
    if (empty($templateData)) {
        return '<html><body><h1>Chyba: Šablona nebyla nalezena</h1></body></html>';
    }
    
    $template = $templateData[0]['html_template'];
    
    $title = $postData['main_title'] ?? 'Ukázkový titulek';
    $subtitle = $postData['subtitle'] ?? 'Ukázkový podtitulek';
    $title_paragraph = $postData['title_paragraph'] ?? 'Ukázkový úvodní text';
    $text_block = $postData['text_block'] ?? 'Ukázkový textový blok newsletteru';
    $main_image = $postData['main_image'] ?? '';
    $shop_link = $postData['shop_link'] ?? '#';
    $shop_text = $postData['shop_text'] ?? 'Navštívit obchod';
    
    $dummy_email = 'info@touchthemagic.com';
    $unsubscribe_link = getUnsubscribeLink($dummy_email);
    
    $template = str_replace(['{{main_title}}', 'Ahoj'], htmlspecialchars($title), $template);
    $template = str_replace('{{subtitle}}', htmlspecialchars($subtitle), $template);
    $template = str_replace('{{title_paragraph}}', htmlspecialchars($title_paragraph), $template);
    $template = str_replace('{{text_block}}', nl2br(htmlspecialchars($text_block)), $template);
    $template = str_replace('{{main_image}}', htmlspecialchars($main_image), $template);
    $template = str_replace('{{shop_link}}', htmlspecialchars($shop_link), $template);
    $template = str_replace('{{shop_text}}', htmlspecialchars($shop_text), $template);
    $template = str_replace('{{unsubscribe}}', htmlspecialchars($unsubscribe_link), $template);
    
    $products_html = '';
    if (isset($postData['products']) && is_array($postData['products'])) {
        foreach ($postData['products'] as $product) {
            if (!empty($product['title']) || !empty($product['image'])) {
                $products_html .= "<div class='product'>\n";
                $products_html .= "  <img alt='produkt' src='" . htmlspecialchars($product['image'] ?? '') . "'>\n";
                $products_html .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info'>" . htmlspecialchars($product['title'] ?? '') . "</a>\n";
                $products_html .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info des'>" . htmlspecialchars($product['description'] ?? '') . "</a>\n";
                $products_html .= "  <a class='more' href='".htmlspecialchars($product['button_link'] ?? '#')."'>Více</a>\n";
                $products_html .= "</div>\n";
            }
        }
    }
    
    $template = str_replace(['{{#each products}}', '{{/each}}'], '', $template);
    $template = str_replace('{{products}}', $products_html, $template);
    
    $fullHtml = "<!DOCTYPE html>\n";
    $fullHtml .= "<html lang='cs'>\n";
    $fullHtml .= "<head>\n";
    $fullHtml .= "<meta charset='UTF-8'>\n";
    $fullHtml .= "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    $fullHtml .= "<title>Náhled newsletteru</title>\n";
    $fullHtml .= "</head>\n";
    $fullHtml .= "<body>\n";
    $fullHtml .= $template . "\n";
    $fullHtml .= "</body>\n";
    $fullHtml .= "</html>";
    
    return $fullHtml;
}

function processNewsletterTemplate($templateData, $postData) {
    $template_for_save = $templateData[0]['html_template'];
    
    $template_for_save = str_replace(['{{main_title}}', 'Ahoj'], htmlspecialchars($postData['main_title']), $template_for_save);
    $template_for_save = str_replace('{{subtitle}}', htmlspecialchars($postData['subtitle']), $template_for_save);
    $template_for_save = str_replace('{{title_paragraph}}', htmlspecialchars($postData['title_paragraph']), $template_for_save);
    $template_for_save = str_replace('{{text_block}}', nl2br(htmlspecialchars($postData['text_block'])), $template_for_save);
    $template_for_save = str_replace('{{main_image}}', htmlspecialchars($postData['main_image']), $template_for_save);
    $template_for_save = str_replace('{{shop_link}}', htmlspecialchars($postData['shop_link']), $template_for_save);
    $template_for_save = str_replace('{{shop_text}}', htmlspecialchars($postData['shop_text']), $template_for_save);

    $products_html = '';
    if (isset($postData['products'])) {
        foreach ($postData['products'] as $product) {
            if (!empty($product['title']) || !empty($product['image'])) {
                $products_html .= "<div class='product'>\n";
                $products_html .= "  <img alt='produkt' src='" . htmlspecialchars($product['image']) . "'>\n";
                $products_html .= "  <a href='" . htmlspecialchars($product['link']) . "' class='info'>" . htmlspecialchars($product['title']) . "</a>\n";
                $products_html .= "  <a href='" . htmlspecialchars($product['link']) . "' class='info des'>" . htmlspecialchars($product['description']) . "</a>\n";
                $products_html .= "  <a class='more' href='".htmlspecialchars($product['button_link'])."'>Více</a>\n";
                $products_html .= "</div>\n";
            }
        }
    }
    $template_for_save = str_replace('{{#each products}}', '', $template_for_save);
    $template_for_save = str_replace('{{/each}}', '', $template_for_save);
    $template_for_save = str_replace('{{products}}', $products_html, $template_for_save);
    
    return $template_for_save;
}

function getAllNewsletters() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM newsletters ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getShippingEmailTemplate($order_number, $tracking_number = "CZ123456789", $carrier_name = "Zásilkovna", $delivery_name = "", $delivery_address = "", $delivery_city = "", $delivery_zip = "") {
    $shipping_date = date('d.m.Y');
    $estimated_delivery = date('d.m.Y', strtotime('+3 days'));
    $tracking_link = "https://tracking.packeta.com/en";
    
    return '
    <!DOCTYPE html>
    <html lang="cs">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style type="text/css">
                body{
                    font-family: Arial, sans-serif !important;
                    background-color: white;
                    margin: 0;
                    padding: 0;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                .main {
                    width: 100% !important;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: white;
                }
                .row{
                    width: 100%;
                    text-align: center;
                }
                .title{
                    border-top: 2px solid lightgray;
                    border-bottom: 2px solid lightgray;
                    margin: 20px 0;
                    text-transform: uppercase;
                    font-size: 12px;
                    font-weight: 700;
                    padding: 15px 0;
                }
                h1{
                    margin: 0;
                    font-size: 16px;
                }
                h2{
                    font-size: 16px;
                    text-transform: uppercase;
                    background-color: #17a2b8;
                    color: white !important;
                    font-weight: 500;
                    padding: 15px 30px;
                    letter-spacing: 2px;
                    margin: 40px 0 0 0;
                    display: inline-block;
                }
                .status-icon{
                    font-size: 48px;
                    color: #17a2b8;
                    margin: 30px 0;
                    line-height: 1;
                }
                .order-info{
                    background: #f8f9fa;
                    margin: 30px 20px;
                    padding: 25px;
                    text-align: left;
                    border-radius: 8px;
                    border: 1px solid #e9ecef;
                }
                .order-info h3{
                    color: #251d3c;
                    margin-top: 0;
                    font-size: 18px;
                    font-weight: 600;
                }
                .order-info p{
                    margin: 15px 0;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #333;
                }
                .order-number{
                    background-color: #251d3c;
                    color: white !important;
                    padding: 6px 12px;
                    border-radius: 4px;
                    font-weight: 600;
                    text-decoration: none;
                }
                .tracking-number{
                    background-color: #17a2b8;
                    color: white !important;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-weight: 600;
                    font-family: monospace;
                    font-size: 16px;
                    display: inline-block;
                    margin: 5px 0;
                }
                .shipping-info{
                    background: white;
                    border-left: 4px solid #17a2b8;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 0 4px 4px 0;
                }
                .shipping-info strong{
                    color: #251d3c;
                }
                .link{
                    text-align: center;
                    margin: 30px 0;
                }
                .link a{
                    background-color: #251d3c !important;
                    color: white !important;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 14px;
                    text-decoration: none;
                    padding: 15px 40px;
                    display: inline-block;
                    border-radius: 4px;
                    border: none;
                }
                .footer{
                    width: 100%;
                    margin: 40px 0 0 0;
                    background-color: #f8f9fa;
                    border-top: 1px solid #e9ecef;
                }
                .inside_footer{
                    padding: 25px 20px;
                    text-align: center;
                }
                .inside_footer a, .inside_footer p{
                    color: #251d3c !important;
                    text-decoration: none;
                    font-size: 13px;
                    margin: 0 8px;
                    display: inline-block;
                }
                .inside_footer p{
                    font-weight: 600;
                }
                /* Gmail specific fixes */
                u + .body .main { width: 600px !important; }
                @media only screen and (max-width: 600px) {
                    .main { width: 100% !important; }
                    .order-info { margin: 20px 10px !important; padding: 20px !important; }
                    h2 { padding: 12px 20px !important; font-size: 14px !important; }
                }
            </style>
        </head>
        <body class="body">
            <div class="main">
                <div class="row logo_main">
                    <img alt="Touch The Magic Logo" src="https://touchthemagic.com/web_images/logo/darklogo.png" style="max-width: 200px; height: auto; margin: 20px 0;">
                </div>
                <div class="row title">
                    <h1>EXPEDICE OBJEDNÁVKY</h1>
                </div>
                <div class="row">
                    <h2>Zabaleno a odesláno</h2>
                </div>
                <div class="row">
                    <div class="status-icon">📦</div>
                </div>
                <div class="order-info">
                    <h3>Vaše objednávka je na cestě!</h3>
                    <p>Objednávka číslo <span class="order-number">' . htmlspecialchars($order_number) . '</span> byla úspěšně zabalena a předána dopravci k doručení.</p>
                    
                    <div class="shipping-info">
                        <p><strong>Sledovací číslo:</strong><br><span class="tracking-number">' . htmlspecialchars($tracking_number) . '</span></p>
                        <p><strong>Dopravce:</strong> ' . htmlspecialchars($carrier_name) . '</p>
                        <p><strong>Datum odeslání:</strong> ' . $shipping_date . '</p>
                        <p><strong>Předpokládané doručení:</strong> ' . $estimated_delivery . '</p>
                    </div>
                    
                    <p><strong>Adresa doručení:</strong><br>
                    ' . htmlspecialchars($delivery_name) . '<br>
                    ' . htmlspecialchars($delivery_address) . '<br>
                    ' . htmlspecialchars($delivery_city) . ', ' . htmlspecialchars($delivery_zip) . '</p>
                    
                    <p>Zásilku můžete sledovat pomocí sledovacího čísla na webu dopravce. V případě jakýchkoli problémů s doručením nás neváhejte kontaktovat.</p>
                </div>
                <div class="row link">
                    <a href="' . $tracking_link . '">Sledovat zásilku</a>
                </div>
                <div class="footer">
                    <div class="inside_footer">
                        <p>Beáta Fraibišová</p>
                        <a href="tel:+420747473938">+420 747 473 938</a>
                        <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                    </div>
                </div>
            </div>
        </body>
    </html>';
}

function getCancellationEmailTemplate($order_number, $total_amount = "0") {
    $cancellation_date = date('d.m.Y H:i');
    $shop_link = "https://touchthemagic.com";
    
    return '
    <!DOCTYPE html>
    <html lang="cs">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style type="text/css">
                body{
                    font-family: Arial, sans-serif !important;
                    background-color: white;
                    margin: 0;
                    padding: 0;
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }
                .main {
                    width: 100% !important;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: white;
                }
                .row{
                    width: 100%;
                    text-align: center;
                }
                .title{
                    border-top: 2px solid lightgray;
                    border-bottom: 2px solid lightgray;
                    margin: 20px 0;
                    text-transform: uppercase;
                    font-size: 12px;
                    font-weight: 700;
                    padding: 15px 0;
                }
                h1{
                    margin: 0;
                    font-size: 16px;
                }
                h2{
                    font-size: 16px;
                    text-transform: uppercase;
                    background-color: #dc3545;
                    color: white !important;
                    font-weight: 500;
                    padding: 15px 30px;
                    letter-spacing: 2px;
                    margin: 40px 0 0 0;
                    display: inline-block;
                }
                .status-icon{
                    font-size: 48px;
                    color: #dc3545;
                    margin: 30px 0;
                    line-height: 1;
                }
                .order-info{
                    background: #f8f9fa;
                    margin: 30px 20px;
                    padding: 25px;
                    text-align: left;
                    border-radius: 8px;
                    border: 1px solid #e9ecef;
                }
                .order-info h3{
                    color: #251d3c;
                    margin-top: 0;
                    font-size: 18px;
                    font-weight: 600;
                }
                .order-info p{
                    margin: 15px 0;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #333;
                }
                .order-number{
                    background-color: #251d3c;
                    color: white !important;
                    padding: 6px 12px;
                    border-radius: 4px;
                    font-weight: 600;
                    text-decoration: none;
                }
                .link{
                    text-align: center;
                    margin: 30px 0;
                }
                .link a{
                    background-color: #251d3c !important;
                    color: white !important;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 14px;
                    text-decoration: none;
                    padding: 15px 40px;
                    display: inline-block;
                    border-radius: 4px;
                    border: none;
                }
                .footer{
                    width: 100%;
                    margin: 40px 0 0 0;
                    background-color: #f8f9fa;
                    border-top: 1px solid #e9ecef;
                }
                .inside_footer{
                    padding: 25px 20px;
                    text-align: center;
                }
                .inside_footer a, .inside_footer p{
                    color: #251d3c !important;
                    text-decoration: none;
                    font-size: 13px;
                    margin: 0 8px;
                    display: inline-block;
                }
                .inside_footer p{
                    font-weight: 600;
                }
                /* Gmail specific fixes */
                u + .body .main { width: 600px !important; }
                @media only screen and (max-width: 600px) {
                    .main { width: 100% !important; }
                    .order-info { margin: 20px 10px !important; padding: 20px !important; }
                    h2 { padding: 12px 20px !important; font-size: 14px !important; }
                }
            </style>
        </head>
        <body class="body">
            <div class="main">
                <div class="row logo_main">
                    <img alt="Touch The Magic Logo" src="https://toucthemagic.com/web_images/logo/darklogo.png" style="max-width: 200px; height: auto; margin: 20px 0;">
                </div>
                <div class="row title">
                    <h1>ZRUŠENÍ OBJEDNÁVKY</h1>
                </div>
                <div class="row">
                    <h2>Objednávka zrušena</h2>
                </div>
                <div class="row">
                    <div class="status-icon">✗</div>
                </div>
                <div class="order-info">
                    <h3>Objednávka byla zrušena</h3>
                    <p>Objednávka číslo <span class="order-number">' . htmlspecialchars($order_number) . '</span> byla zrušena a nebude zpracována.</p>
                    <p><strong>Datum zrušení:</strong> ' . $cancellation_date . '</p>
                    <p><strong>Částka:</strong> ' . htmlspecialchars($total_amount) . ' Kč</p>
                    <p>Pokud máte jakékoli dotazy ohledně zrušení objednávky, neváhejte nás kontaktovat.</p>
                </div>
                <div class="row link">
                    <a href="' . $shop_link . '">Návrat do obchodu</a>
                </div>
                <div class="footer">
                    <div class="inside_footer">
                        <p>Beáta Fraibišová</p>
                        <a href="tel:+420747473938">+420 747 473 938</a>
                        <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                    </div>
                </div>
            </div>
        </body>
    </html>';
}
?>