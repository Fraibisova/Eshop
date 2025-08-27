<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Editor Newsletteru</title>
    <link rel="stylesheet" href="/css/admin_style.css">
</head>
<body>
    <?php $adminService->renderHeader(); ?>
    
    <div class="form-container">
        <h1>Vytvořit Newsletter</h1>
        
        <div class="info-box">
            <strong>📧 Unsubscribe odkazy:</strong><br>
            Ve vaší šabloně použijte <code>{{unsubscribe}}</code> placeholder pro automatické vložení unsubscribe odkazu.
            Při odesílání bude automaticky nahrazen personalizovaným odkazem pro každého odběratele.
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="newsletterForm" action="/admin/newsletter/create">
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
        <iframe src="/public/admin/newsletter/newsletter_preview.html?<?= time() ?>" frameborder="0"></iframe>
    </div>

    <script src="/js/newsletter.js"></script>
</body>
</html>