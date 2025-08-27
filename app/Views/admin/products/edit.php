<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace produktu</title>
    <link rel="stylesheet" href="/css/admin_style.css">
    
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    <div class="container">
        <h1>Editace produktu</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($item)): ?>
        <form action="/admin/products/edit?id=<?= $item['id'] ?>" method="post" enctype="multipart/form-data" class="product-form">
            <label for="name">Název produktu:</label>
            <input type="text" id="name" name="name" maxlength="100" value="<?= htmlspecialchars($item['name'] ?? '') ?>" required><br><br>

            <label for="product_code">Kód produktu:</label>
            <input type="text" id="product_code" name="product_code" value="<?= htmlspecialchars($item['product_code'] ?? '') ?>" required><br><br>

            <label for="description_main">Popis produktu:</label>
            <textarea id="description_main" name="description_main" rows="5" required><?= htmlspecialchars($item['description_main'] ?? '') ?></textarea><br><br>

            <label for="price">Cena (včetně DPH):</label>
            <input type="number" id="price" name="price" value="<?= htmlspecialchars($item['price'] ?? '') ?>" required><br><br>

            <label for="price_without_dph">Cena (bez DPH):</label>
            <input type="number" id="price_without_dph" name="price_without_dph" value="<?= htmlspecialchars($item['price_without_dph'] ?? '') ?>" required><br><br>

            <label for="sale">Sleva (%):</label>
            <input type="number" id="sale" name="sale" min="0" max="100" value="<?= htmlspecialchars($item['sale'] ?? '0') ?>" 
                   title="Zadejte slevu v procentech (0-100)"><br><br>

            <label for="season">Sezóna:</label>
            <select id="season" name="season">
                <option value="" <?= empty($item['season']) ? 'selected' : '' ?>>-- Bez sezóny --</option>
                <option value="Halloween" <?= ($item['season'] ?? '') === 'Halloween' ? 'selected' : '' ?>>Halloween</option>
                <option value="Vánoce" <?= ($item['season'] ?? '') === 'Vánoce' ? 'selected' : '' ?>>Vánoce</option>
                <option value="Velikonoce" <?= ($item['season'] ?? '') === 'Velikonoce' ? 'selected' : '' ?>>Velikonoce</option>
                <option value="Léto" <?= ($item['season'] ?? '') === 'Léto' ? 'selected' : '' ?>>Léto</option>
                <option value="Zima" <?= ($item['season'] ?? '') === 'Zima' ? 'selected' : '' ?>>Zima</option>
                <option value="Jaro" <?= ($item['season'] ?? '') === 'Jaro' ? 'selected' : '' ?>>Jaro</option>
                <option value="Podzim" <?= ($item['season'] ?? '') === 'Podzim' ? 'selected' : '' ?>>Podzim</option>
                <option value="Valentýn" <?= ($item['season'] ?? '') === 'Valentýn' ? 'selected' : '' ?>>Valentýn</option>
                <option value="Mikuláš" <?= ($item['season'] ?? '') === 'Mikuláš' ? 'selected' : '' ?>>Mikuláš</option>
            </select><br><br>

            <label for="image">Obrázek produktu (ponechat prázdné pro zachování současného):</label>
            <input type="file" id="image" name="image" accept="image/*">
            <?php if (!empty($item['image'])): ?>
                <p>Současný obrázek: <?= htmlspecialchars($item['image']) ?></p>
            <?php endif; ?>
            <br><br>

            <label for="image_folder">Složka obrázku:</label>
            <input type="text" id="image_folder" name="image_folder" value="<?= htmlspecialchars($item['image_folder'] ?? '') ?>" required><br><br>

            <label for="mass">Hmotnost (g):</label>
            <input type="number" id="mass" name="mass" value="<?= htmlspecialchars($item['mass'] ?? '') ?>" required><br><br>

            <label for="visible">Viditelnost:</label>
            <select id="visible" name="visible" required>
                <option value="1" <?= ($item['visible'] ?? '1') == '1' ? 'selected' : '' ?>>Ano</option>
                <option value="0" <?= ($item['visible'] ?? '1') == '0' ? 'selected' : '' ?>>Ne</option>
            </select><br><br>

            <label for="category">Kategorie:</label>
            <input type="text" id="category" name="category" value="<?= htmlspecialchars($item['category'] ?? '') ?>" required><br><br>

            <label for="stock_status">Skladová dostupnost:</label>
            <select id="stock_status" name="stock_status" required>
                <option value="Skladem" <?= ($item['stock'] ?? '') === 'Skladem' ? 'selected' : '' ?>>Skladem</option>
                <option value="Není skladem" <?= ($item['stock'] ?? '') === 'Není skladem' ? 'selected' : '' ?>>Není skladem</option>
                <option value="Předobjednat" <?= ($item['stock'] ?? '') === 'Předobjednat' ? 'selected' : '' ?>>Předobjednat</option>
            </select><br><br>

            <label for="paragraph1">Paragraph 1:</label>
            <textarea name="paragraph1" id="paragraph1" rows="4"><?= htmlspecialchars($item['paragraph1'] ?? '') ?></textarea><br><br>

            <label for="paragraph2">Paragraph 2:</label>
            <textarea name="paragraph2" id="paragraph2" rows="4"><?= htmlspecialchars($item['paragraph2'] ?? '') ?></textarea><br><br>

            <label for="paragraph3">Paragraph 3:</label>
            <textarea name="paragraph3" id="paragraph3" rows="4"><?= htmlspecialchars($item['paragraph3'] ?? '') ?></textarea><br><br>

            <label for="paragraph4">Paragraph 4:</label>
            <textarea name="paragraph4" id="paragraph4" rows="4"><?= htmlspecialchars($item['paragraph4'] ?? '') ?></textarea><br><br>

            <label for="paragraph5">Paragraph 5:</label>
            <textarea name="paragraph5" id="paragraph5" rows="4"><?= htmlspecialchars($item['paragraph5'] ?? '') ?></textarea><br><br>

            <label for="paragraph6">Paragraph 6:</label>
            <textarea name="paragraph6" id="paragraph6" rows="4"><?= htmlspecialchars($item['paragraph6'] ?? '') ?></textarea><br><br>

            <button type="submit">Uložit změny</button>
        </form>
        <?php else: ?>
            <div class="message error">
                Produkt nenalezen nebo nastala chyba při načítání dat.
            </div>
        <?php endif; ?>
        
        <a href="/admin/products" class="back-link">Zpět na seznam produktů</a>
    </div>

    <script>
        document.getElementById('sale').addEventListener('input', function() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const sale = parseFloat(this.value) || 0;
            
            if (sale > 0 && price > 0) {
                const discountedPrice = price * (1 - sale / 100);
                console.log(`Původní cena: ${price} Kč, Sleva: ${sale}%, Nová cena: ${discountedPrice.toFixed(2)} Kč`);
            }
        });

        document.getElementById('sale').addEventListener('blur', function() {
            const sale = parseInt(this.value);
            if (sale < 0 || sale > 100) {
                alert('Sleva musí být mezi 0 a 100 procenty!');
                this.value = 0;
            }
        });
    </script>
</body>
</html>