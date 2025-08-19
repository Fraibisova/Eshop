<?php
define('APP_ACCESS', true);

require '../config.php';
require '../lib/function_admin.php';
checkAdminRole();


$newsletter = [
    'id' => null,
    'main_title' => '',
    'subtitle' => '',
    'title_paragraph' => '',
    'main_image' => '',
    'text_block' => '',
    'shop_link' => '',
    'shop_text' => '',
    'status' => 'draft',
    'scheduled_at' => ''
];

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM newsletters WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $newsletter['id'] = $row['id'];
        $newsletter['main_title'] = $row['title'];
        $newsletter['status'] = $row['status'];
        $newsletter['scheduled_at'] = $row['scheduled_at'];
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM newsletters WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$scheduled_sent = checkAndSendScheduledNewsletters();

$template_html = '';
$message = '';
$templateData = getNewsletterTemplate();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preview') {

    $preview_html = generateNewsletterPreview($templateData, $_POST);
    file_put_contents('newsletter_preview.html', $preview_html);
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['main_title'] ?? '';
    $action = $_POST['action'] ?? 'save';
    $status = $_POST['status'] ?? 'draft';
    $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
    $id = $_POST['id'] ?? null;
    
    if (empty($templateData)) {
        $message = "Chyba: Šablona 'Basic' nebyla nalezena v tabulce newsletter_templates";
    } else {
        $template_html = generateNewsletterPreview($templateData, $_POST);
        
        if ($action !== 'preview') {
            $template_for_save = processNewsletterTemplate($templateData, $_POST);
            
            switch ($action) {
                case 'save':
                    $newsletter_id = saveNewsletter($title, $template_for_save, $status, $scheduled_at, $id);
                    if ($newsletter_id) {
                        $message = "Newsletter byl úspěšně " . ($id ? 'aktualizován' : 'uložen') . " (ID: $newsletter_id)";
                        if (!$id) {
                            header('Location: ' . $_SERVER['PHP_SELF'] . '?edit=' . $newsletter_id);
                            exit;
                        }
                    } else {
                        $message = "Chyba při ukládání newsletteru";
                    }
                    break;
                    
                case 'schedule':
                    if (empty($scheduled_at)) {
                        $message = "Pro naplánování musíte zadat datum a čas";
                    } else {
                        $scheduled_timestamp = strtotime($scheduled_at);
                        $current_timestamp = time();
                        
                        if ($scheduled_timestamp <= $current_timestamp) {
                            $newsletter_id = saveNewsletter($title, $template_for_save, 'sending', $scheduled_at, $id);
                            if ($newsletter_id) {
                                $sent_count = sendNewsletterToSubscribers($newsletter_id, $title, $template_for_save);
                                if ($sent_count !== false) {
                                    $message = "Naplánovaný čas již prošel - newsletter byl odeslán okamžitě $sent_count odběratelům";
                                } else {
                                    $message = "Chyba při okamžitém odeslání newsletteru";
                                }
                            } else {
                                $message = "Chyba při ukládání newsletteru";
                            }
                        } else {
                            $newsletter_id = saveNewsletter($title, $template_for_save, 'scheduled', $scheduled_at, $id);
                            if ($newsletter_id) {
                                $message = "Newsletter byl naplánován na odeslání: " . date('d.m.Y H:i', strtotime($scheduled_at));
                            } else {
                                $message = "Chyba při plánování newsletteru";
                            }
                        }
                    }
                    break;
                    
                case 'send_now':
                    $newsletter_id = saveNewsletter($title, $template_for_save, 'sending', null, $id);
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
        
        file_put_contents('newsletter_preview.html', $template_html);
    }
} else {
    $template_html = generateNewsletterPreview($templateData, $_POST ?? []);
    file_put_contents('newsletter_preview.html', $template_html);
}

if ($scheduled_sent > 0) {
    $message = "Automaticky odesláno $scheduled_sent naplánovaných newsletterů. " . ($message ?? '');
}

$newsletters = getAllNewsletters();
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
        <h1><?= $newsletter['id'] ? 'Upravit Newsletter' : 'Vytvořit Newsletter' ?></h1>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="newsletterForm" action="<?= $_SERVER['PHP_SELF'] ?><?= $newsletter['id'] ? '?edit=' . (int)$newsletter['id'] : '' ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($newsletter['id']) ?>">
            
            <label>Název newsletteru:<br><input name="main_title" value="<?= htmlspecialchars($newsletter['main_title']) ?>" required></label><br>
            <label>Podtitul:<br><input name="subtitle"></label><br>
            <label>Textový úvod:<br><input name="title_paragraph"></label><br>
            <label>Hlavní obrázek URL:<br><input name="main_image"></label><br>
            <label>Textový blok:<br><textarea name="text_block"></textarea></label><br>
            <label>Odkaz do obchodu:<br><input name="shop_link"></label><br>
            <label>Text odkazu:<br><input name="shop_text"></label><br>

            <h2>Produkty</h2>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <fieldset>
                    <legend>Produkt <?= $i+1 ?></legend>
                    <label>Obrázek:<br><input name="products[<?= $i ?>][image]"></label><br>
                    <label>Odkaz:<br><input name="products[<?= $i ?>][link]"></label><br>
                    <label>Titulek:<br><input name="products[<?= $i ?>][title]"></label><br>
                    <label>Popis:<br><input name="products[<?= $i ?>][description]"></label><br>
                    <label>Tlačítko odkaz:<br><input name="products[<?= $i ?>][button_link]"></label><br>
                </fieldset>
            <?php endfor; ?>

            <h2>Status a plánování</h2>
            <label>Stav:
                <select name="status">
                    <option value="draft" <?= $newsletter['status'] === 'draft' ? 'selected' : '' ?>>Rozpracovaný</option>
                    <option value="ready" <?= $newsletter['status'] === 'ready' ? 'selected' : '' ?>>Připravený</option>
                    <option value="sent" <?= $newsletter['status'] === 'sent' ? 'selected' : '' ?>>Odeslaný</option>
                </select>
            </label><br>
            <label>Naplánováno na:<br>
                <input type="datetime-local" name="scheduled_at" value="<?= $newsletter['scheduled_at'] ?>">
                <small>Pokud zadaný čas již prošel, newsletter se odešle okamžitě</small>
            </label><br>

            <div class="button-group">
                <button type="submit" name="action" value="save" class="btn-primary"><?= $newsletter['id'] ? 'Uložit změny' : 'Uložit' ?></button>
                <button type="submit" name="action" value="schedule" class="btn-warning">Naplánovat</button>
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