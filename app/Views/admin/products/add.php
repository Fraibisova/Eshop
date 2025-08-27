
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulář pro zadávání produktů</title>
    <link rel="stylesheet" href="/css/admin_style.css">
    
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    <div class="container">
        <h1>Formulář pro zadávání produktů</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form action="/admin/products/add" method="post" enctype="multipart/form-data" class="product-form">
            <label for="name">Název produktu:</label>
            <input type="text" id="name" name="name" maxlength="100" required><br><br>

            <label for="product_code">Kód produktu:</label>
            <input type="number" id="product_code" name="product_code" required><br><br>

            <label for="description_main">Popis produktu:</label>
            <textarea id="description_main" name="description_main" rows="5" required></textarea><br><br>

            <label for="price">Cena (včetně DPH):</label>
            <input type="number" id="price" name="price" required><br><br>

            <label for="price_without_dph">Cena (bez DPH):</label>
            <input type="number" id="price_without_dph" name="price_without_dph" required><br><br>

            <label for="sale">Sleva (%):</label>
            <input type="number" id="sale" name="sale" min="0" max="100" value="0" 
                   title="Zadejte slevu v procentech (0-100)"><br><br>

            <label for="season">Sezóna:</label>
            <select id="season" name="season">
                <option value="">-- Bez sezóny --</option>
                <option value="Halloween">Halloween</option>
                <option value="Vánoce">Vánoce</option>
                <option value="Velikonoce">Velikonoce</option>
                <option value="Léto">Léto</option>
                <option value="Zima">Zima</option>
                <option value="Jaro">Jaro</option>
                <option value="Podzim">Podzim</option>
                <option value="Valentýn">Valentýn</option>
                <option value="Mikuláš">Mikuláš</option>
            </select><br><br>

            <label for="image">Obrázek produktu:</label>
            <input type="file" id="image" name="image" accept="image/*" required><br><br>

            <label for="image_folder">Složka obrázku:</label>
            <input type="text" id="image_folder" name="image_folder" required><br><br>

            <label for="mass">Hmotnost (g):</label>
            <input type="number" id="mass" name="mass" required><br><br>

            <label for="visible">Viditelnost:</label>
            <select id="visible" name="visible" required>
                <option value="1">Ano</option>
                <option value="0">Ne</option>
            </select><br><br>

            <label for="category">Kategorie:</label>
            <input type="text" id="category" name="category" required><br><br>

            <label for="stock_status">Skladová dostupnost:</label>
            <select id="stock_status" name="stock_status" required>
                <option value="Skladem">Skladem</option>
                <option value="Není skladem">Není skladem</option>
                <option value="Předobjednat">Předobjednat</option>
            </select><br><br>

            <label for="paragraph1">Paragraph 1:</label>
            <textarea name="paragraph1" id="paragraph1" rows="4"></textarea><br><br>

            <label for="paragraph2">Paragraph 2:</label>
            <textarea name="paragraph2" id="paragraph2" rows="4"></textarea><br><br>

            <label for="paragraph3">Paragraph 3:</label>
            <textarea name="paragraph3" id="paragraph3" rows="4"></textarea><br><br>

            <label for="paragraph4">Paragraph 4:</label>
            <textarea name="paragraph4" id="paragraph4" rows="4"></textarea><br><br>

            <label for="paragraph5">Paragraph 5:</label>
            <textarea name="paragraph5" id="paragraph5" rows="4"></textarea><br><br>

            <label for="paragraph6">Paragraph 6:</label>
            <textarea name="paragraph6" id="paragraph6" rows="4"></textarea><br><br>

            <button type="submit">Odeslat</button>
        </form>
        <a href="/admin/dashboard" class="back-link">Zpět</a>
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