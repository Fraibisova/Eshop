<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Úprava produktu</title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .price-preview {
            background: #f8f9fa;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid #007bff;
            font-size: 14px;
        }
        
        .current-values {
            background: #fff3cd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        
        .sale-warning {
            color: #dc3545;
            font-size: 12px;
            font-weight: bold;
        }
        
        .price-calculator {
            background: #d4edda;
            padding: 8px;
            margin: 5px 0;
            border-radius: 3px;
            font-size: 13px;
            display: none;
        }
    </style>
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>

    <h1>Úprava produktu</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($item)): ?>
        <div class="current-values">
            <h3>Aktuální nastavení:</h3>
            <p><strong>Název:</strong> <?= htmlspecialchars($item['name']) ?></p>
            <p><strong>Cena:</strong> <?= number_format($item['price'], 2) ?> Kč</p>
            <?php 
                $currentSale = (int)($item['sale'] ?? 0);
                $currentPrice = (float)$item['price'];
                $discountedPrice = $currentPrice * (1 - $currentSale / 100);
            ?>
            <p><strong>Sleva:</strong> <?= $currentSale > 0 ? $currentSale . '%' : 'Bez slevy' ?></p>
            <?php if ($currentSale > 0): ?>
                <p><strong>Cena po slevě:</strong> <?= number_format($discountedPrice, 2) ?> Kč</p>
            <?php endif; ?>
            <p><strong>Sezóna:</strong> <?= !empty($item['season']) ? htmlspecialchars($item['season']) : 'Bez sezóny' ?></p>
        </div>

        <form action="/admin/products/edit?id=<?= htmlspecialchars($item['id']) ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

            <label for="name">Název produktu:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>" maxlength="100" required>
            <br><br>

            <label for="product_code">Kód produktu:</label>
            <input type="number" id="product_code" name="product_code" value="<?= htmlspecialchars($item['product_code']) ?>" required>
            <br><br>

            <label for="description_main">Popis produktu:</label>
            <textarea id="description_main" name="description_main" maxlength="10000" rows="5" required><?= htmlspecialchars($item['description_main']) ?></textarea>
            <br><br>

            <label for="price">Cena (včetně DPH):</label>
            <input type="number" id="price" name="price" value="<?= htmlspecialchars($item['price']) ?>" step="0.01" required>
            <br><br>

            <label for="price_without_dph">Cena (bez DPH):</label>
            <input type="number" id="price_without_dph" name="price_without_dph" value="<?= htmlspecialchars($item['price_without_dph']) ?>" step="0.01" required>
            <br><br>

            <label for="sale">Sleva (%):</label>
            <input type="number" id="sale" name="sale" min="0" max="100" 
                   value="<?= htmlspecialchars($item['sale'] ?? 0) ?>" 
                   title="Zadejte slevu v procentech (0-100)">
            <div id="price-calculator" class="price-calculator">
                Nová cena po slevě: <span id="new-price">0</span> Kč
            </div>
            <br><br>

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
            </select>
            <br><br>

            <label for="image">Obrázek produktu:</label>
            <input type="file" id="image" name="image" accept="image/*">
            <p>Aktuální obrázek: <?= htmlspecialchars($item['image']) ?></p>
            <br><br>

            <label for="image_folder">Složka obrázku:</label>
            <input type="text" id="image_folder" name="image_folder" value="<?= htmlspecialchars($item['image_folder']) ?>" maxlength="1000" required>
            <br><br>

            <label for="mass">Hmotnost (g):</label>
            <input type="number" id="mass" name="mass" value="<?= htmlspecialchars($item['mass']) ?>" step="1" required>
            <br><br>

            <label for="visible">Viditelnost:</label>
            <select id="visible" name="visible" required>
                <option value="1" <?= $item['visible'] == 1 ? 'selected' : '' ?>>Ano</option>
                <option value="0" <?= $item['visible'] == 0 ? 'selected' : '' ?>>Ne</option>
            </select>
            <br><br>

            <label for="category">Kategorie:</label>
            <input type="text" id="category" name="category" value="<?= htmlspecialchars($item['category']) ?>" maxlength="1000" required>
            <br><br>

            <label for="stock_status">Skladová dostupnost:</label>
            <select id="stock_status" name="stock_status" required>
                <option value="Skladem" <?= $item['stock'] == 'Skladem' ? 'selected' : '' ?>>Skladem</option>
                <option value="Není skladem" <?= $item['stock'] == 'Není skladem' ? 'selected' : '' ?>>Není skladem</option>
                <option value="Předobjednat" <?= $item['stock'] == 'Předobjednat' ? 'selected' : '' ?>>Předobjednat</option>
            </select>
            <br><br>

            <button type="submit">Uložit změny</button>
        </form>
    <?php else: ?>
        <p>Produkt nenalezen.</p>
    <?php endif; ?>

    <a href="/admin/products">Zpět</a>

    <script>
        function updatePriceCalculator() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const sale = parseFloat(document.getElementById('sale').value) || 0;
            const calculator = document.getElementById('price-calculator');
            const newPriceSpan = document.getElementById('new-price');
            
            if (sale > 0 && price > 0) {
                const discountedPrice = price * (1 - sale / 100);
                newPriceSpan.textContent = discountedPrice.toFixed(2);
                calculator.style.display = 'block';
            } else {
                calculator.style.display = 'none';
            }
        }

        document.getElementById('price').addEventListener('input', updatePriceCalculator);
        document.getElementById('sale').addEventListener('input', updatePriceCalculator);

        document.getElementById('sale').addEventListener('blur', function() {
            const sale = parseInt(this.value);
            if (sale < 0 || sale > 100) {
                alert('Sleva musí být mezi 0 a 100 procenty!');
                this.value = <?= $item['sale'] ?? 0 ?>; 
                updatePriceCalculator();
            }
        });

        document.getElementById('sale').addEventListener('input', function() {
            const sale = parseInt(this.value);
            const existingWarning = document.getElementById('sale-warning');
            
            if (sale > 50) {
                this.style.borderColor = '#dc3545';
                if (!existingWarning) {
                    const warning = document.createElement('div');
                    warning.id = 'sale-warning';
                    warning.className = 'sale-warning';
                    warning.textContent = 'Pozor: Vysoká sleva!';
                    this.parentNode.insertBefore(warning, this.nextSibling);
                }
            } else {
                this.style.borderColor = '';
                if (existingWarning) {
                    existingWarning.remove();
                }
            }
            
            updatePriceCalculator();
        });

        document.addEventListener('DOMContentLoaded', function() {
            updatePriceCalculator();
            
            const currentSale = parseInt(document.getElementById('sale').value);
            if (currentSale > 50) {
                document.getElementById('sale').dispatchEvent(new Event('input'));
            }
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const sale = parseInt(document.getElementById('sale').value);
            if (sale < 0 || sale > 100) {
                e.preventDefault();
                alert('Sleva musí být mezi 0 a 100 procenty!');
                return false;
            }
            
            if (sale > 75) {
                if (!confirm('Sleva je velmi vysoká (' + sale + '%). Opravdu chcete pokračovat?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>