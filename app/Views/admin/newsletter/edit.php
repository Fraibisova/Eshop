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
        <h1><?= $newsletter['id'] ? 'Upravit Newsletter' : 'Vytvořit Newsletter' ?></h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="newsletterForm" action="/admin/newsletter/edit<?= $newsletter['id'] ? '?edit=' . (int)$newsletter['id'] : '' ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($newsletter['id']) ?>">
            
            <label>Název newsletteru:<br><input name="main_title" value="<?= htmlspecialchars($newsletter['main_title']) ?>" required></label><br>
            <label>Podtitul:<br><input name="subtitle" value="<?= htmlspecialchars($newsletter['subtitle'] ?? '') ?>"></label><br>
            <label>Textový úvod:<br><input name="title_paragraph" value="<?= htmlspecialchars($newsletter['title_paragraph'] ?? '') ?>"></label><br>
            <label>Hlavní obrázek URL:<br><input name="main_image" value="<?= htmlspecialchars($newsletter['main_image'] ?? '') ?>"></label><br>
            <label>Textový blok:<br><textarea name="text_block"><?= htmlspecialchars($newsletter['text_block'] ?? '') ?></textarea></label><br>
            <label>Odkaz do obchodu:<br><input name="shop_link" value="<?= htmlspecialchars($newsletter['shop_link'] ?? '') ?>"></label><br>
            <label>Text odkazu:<br><input name="shop_text" value="<?= htmlspecialchars($newsletter['shop_text'] ?? '') ?>"></label><br>

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
        <iframe src="/public/admin/newsletter/newsletter_preview.html?<?= time() ?>" frameborder="0"></iframe>
    </div>

    <script src="/js/newsletter.js"></script>
</body>
</html>