<?php
define('APP_ACCESS', true);

require '../config.php';
require '../mailer.php';
require '../lib/function.php';
require '../lib/function_admin.php';
checkAdminRole();

$template_html = '';
$message = '';
$templateData = getNewsletterTemplate();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preview') {
    $preview_html = generatePreview($templateData, $_POST);
    file_put_contents(__DIR__ . '/newsletter_preview.html', $preview_html);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['main_title'] ?? '';
    $action = $_POST['action'] ?? 'save';
    $status = $_POST['status'] ?? 'draft';
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
    
    if (empty($templateData)) {
        $message = "Chyba: Šablona 'Basic' nebyla nalezena v tabulce newsletter_templates";
    } else {
        $template_html = generatePreview($templateData, $_POST);
        
        if ($action !== 'preview') {
            $template_for_save = $templateData[0]['html_template'];
            
            $title_save = $_POST['main_title'] ?? '';
            $subtitle_save = $_POST['subtitle'] ?? '';
            $title_paragraph_save = $_POST['title_paragraph'] ?? '';
            $text_block_save = $_POST['text_block'] ?? '';
            $main_image_save = $_POST['main_image'] ?? '';
            $shop_link_save = $_POST['shop_link'] ?? '#';
            $shop_text_save = $_POST['shop_text'] ?? 'Navštívit obchod';
            
            $template_for_save = str_replace(['{{main_title}}', 'Ahoj'], htmlspecialchars($title_save), $template_for_save);
            $template_for_save = str_replace('{{subtitle}}', htmlspecialchars($subtitle_save), $template_for_save);
            $template_for_save = str_replace('{{title_paragraph}}', htmlspecialchars($title_paragraph_save), $template_for_save);
            $template_for_save = str_replace('{{text_block}}', nl2br(htmlspecialchars($text_block_save)), $template_for_save);
            $template_for_save = str_replace('{{main_image}}', htmlspecialchars($main_image_save), $template_for_save);
            $template_for_save = str_replace('{{shop_link}}', htmlspecialchars($shop_link_save), $template_for_save);
            $template_for_save = str_replace('{{shop_text}}', htmlspecialchars($shop_text_save), $template_for_save);
            
            $products_html_save = '';
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $product) {
                    if (!empty($product['title']) || !empty($product['image'])) {
                        $products_html_save .= "<div class='product'>\n";
                        $products_html_save .= "  <img alt='produkt' src='" . htmlspecialchars($product['image'] ?? '') . "'>\n";
                        $products_html_save .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info'>" . htmlspecialchars($product['title'] ?? '') . "</a>\n";
                        $products_html_save .= "  <a href='" . htmlspecialchars($product['link'] ?? '#') . "' class='info des'>" . htmlspecialchars($product['description'] ?? '') . "</a>\n";
                        $products_html_save .= "  <a class='more' href='".htmlspecialchars($product['button_link'] ?? '#')."'>Více</a>\n";
                        $products_html_save .= "</div>\n";
                    }
                }
            }
            
            $template_for_save = str_replace(['{{#each products}}', '{{/each}}'], '', $template_for_save);
            $template_for_save = str_replace('{{products}}', $products_html_save, $template_for_save);
            
            switch ($action) {
                case 'save':
                    $newsletter_id = saveNewsletter($title, $template_for_save, $status, $scheduled_at);
                    if ($newsletter_id) {
                        $message = "Newsletter byl úspěšně uložen (ID: $newsletter_id) se stavem: $status";
                    } else {
                        $message = "Chyba při ukládání newsletteru";
                    }
                    break;
                    
                case 'send_now':
                    $newsletter_id = saveNewsletter($title, $template_for_save, 'sent', null);
                    if ($newsletter_id) {
                        $sent_count = sendNewsletterToSubscribers($newsletter_id, $title, $template_for_save);
                        if ($sent_count !== false) {
                            $message = "Newsletter byl odeslán $sent_count odběratelům";
                        } else {
                            $message = "Chyba při odesílání newsletteru";
                        }
                    } else {
                        $message = "Chyba při ukládání newsletteru před odesláním";
                    }
                    break;
            }
        }
        
        file_put_contents(__DIR__ . '/newsletter_preview.html', $template_html);
    }
} else {
    $template_html = generatePreview($templateData);
    file_put_contents(__DIR__ . '/newsletter_preview.html', $template_html);
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Editor Newsletteru</title>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
    <?php if (function_exists('adminHeader')) adminHeader(); ?>
    
    <div class="form-container">
        <h1>Vytvořit Newsletter</h1>
        
        <div class="info-box">
            <strong>📧 Unsubscribe odkazy:</strong><br>
            Ve vaší šabloně použijte <code>{{unsubscribe}}</code> placeholder pro automatické vložení unsubscribe odkazu.
            Při odesílání bude automaticky nahrazen personalizovaným odkazem pro každého odběratele.
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="newsletterForm">
            <label>Název newsletteru:<br><input name="main_title" required value="<?= htmlspecialchars($_POST['main_title'] ?? '') ?>"></label><br>
            <label>Podtitul:<br><input name="subtitle" value="<?= htmlspecialchars($_POST['subtitle'] ?? '') ?>"></label><br>
            <label>Textový úvod:<br><input name="title_paragraph" value="<?= htmlspecialchars($_POST['title_paragraph'] ?? '') ?>"></label><br>
            <label>Hlavní obrázek URL:<br><input name="main_image" value="<?= htmlspecialchars($_POST['main_image'] ?? '') ?>"></label><br>
            <label>Textový blok:<br><textarea name="text_block"><?= htmlspecialchars($_POST['text_block'] ?? '') ?></textarea></label><br>
            <label>Odkaz do obchodu:<br><input name="shop_link" value="<?= htmlspecialchars($_POST['shop_link'] ?? '') ?>"></label><br>
            <label>Text odkazu:<br><input name="shop_text" value="<?= htmlspecialchars($_POST['shop_text'] ?? '') ?>"></label><br>

            <h2>Produkty</h2>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <fieldset>
                    <legend>Produkt <?= $i+1 ?></legend>
                    <label>Obrázek:<br><input name="products[<?= $i ?>][image]" value="<?= htmlspecialchars($_POST['products'][$i]['image'] ?? '') ?>"></label><br>
                    <label>Odkaz:<br><input name="products[<?= $i ?>][link]" value="<?= htmlspecialchars($_POST['products'][$i]['link'] ?? '') ?>"></label><br>
                    <label>Titulek:<br><input name="products[<?= $i ?>][title]" value="<?= htmlspecialchars($_POST['products'][$i]['title'] ?? '') ?>"></label><br>
                    <label>Popis:<br><input name="products[<?= $i ?>][description]" value="<?= htmlspecialchars($_POST['products'][$i]['description'] ?? '') ?>"></label><br>
                    <label>Tlačítko odkaz:<br><input name="products[<?= $i ?>][button_link]" value="<?= htmlspecialchars($_POST['products'][$i]['button_link'] ?? '') ?>"></label><br>
                </fieldset>
            <?php endfor; ?>

            <h2>Status a plánování</h2>
            <label>Stav:
                <select name="status">
                    <option value="draft" <?= ($_POST['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rozpracovaný (draft)</option>
                    <option value="ready" <?= ($_POST['status'] ?? '') === 'ready' ? 'selected' : '' ?>>Připravený k odeslání (ready)</option>
                </select>
            </label><br>

            <div class="button-group">
                <button type="submit" name="action" value="save" class="btn-primary">Uložit jako draft</button>
                <button type="submit" name="action" value="send_now" class="btn-success" onclick="return confirm('Opravdu chcete odeslat newsletter všem odběratelům?')">Odeslat nyní</button>
            </div>
        </form>
    </div>
    
    <div class="preview-container">
        <h2>Náhled newsletteru</h2>
        <iframe src="newsletter_preview.html?<?= time() ?>" frameborder="0"></iframe>
    </div>

    <script src="../js/newsletter.js"></script>
</body>
</html>