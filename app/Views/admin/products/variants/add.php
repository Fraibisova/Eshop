<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přidat variantu produktu</title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        
        .form-container {
            max-width: 600px;
            margin: 20px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            height: 80px;
        }
        
        .suggested-codes {
            margin-top: 10px;
        }
        
        .suggested-codes span {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .suggested-codes span:hover {
            background: #007bff;
            color: white;
        }
        
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    
    <div class="container">
        <h1>Přidat variantu produktu</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($product)): ?>
            <div class="product-info">
                <h3>Základní produkt: <?= htmlspecialchars($product['name']) ?></h3>
                <p><strong>Kód:</strong> <?= htmlspecialchars($product['product_code']) ?></p>
                <p><strong>Cena:</strong> <?= number_format($product['price'], 2) ?> Kč</p>
            </div>
            
            <div class="form-container">
                <form method="post">
                    <div class="form-group">
                        <label for="variant_code">Kód varianty *</label>
                        <input type="text" id="variant_code" name="variant_code" required maxlength="5" 
                               pattern="[A-Z0-9]+" title="Pouze velká písmena a čísla" style="text-transform: uppercase;">
                        <div class="suggested-codes">
                            <small>Navrhované kódy:</small>
                            <?php foreach ($suggestedCodes as $code): ?>
                                <span onclick="document.getElementById('variant_code').value='<?= $code ?>'"><?= $code ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="variant_name">Název varianty</label>
                        <input type="text" id="variant_name" name="variant_name" 
                               value="<?= htmlspecialchars($product['name']) ?> - " placeholder="Automaticky se vygeneruje">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Cena varianty (Kč)</label>
                        <input type="number" id="price" name="price" step="0.01" 
                               value="<?= $product['price'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Skladové množství</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Popis varianty</label>
                        <textarea id="description" name="description" placeholder="Popište rozdíly této varianty..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Přidat variantu</button>
                        <a href="/admin/products/variants?id=<?= $product['id'] ?>" class="btn btn-secondary">Zrušit</a>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <div class="message error">
                Produkt nenalezen nebo nastala chyba při načítání dat.
                <p><a href="/admin/products">Zpět na seznam produktů</a></p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="/admin/products/variants?id=<?= $product['id'] ?? '' ?>">&larr; Zpět na varianty</a> |
            <a href="/admin/products">Seznam produktů</a>
        </div>
    </div>

    <script>
        document.getElementById('variant_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            
            const baseProductName = "<?= addslashes($product['name'] ?? '') ?>";
            const variantCode = this.value;
            if (variantCode) {
                document.getElementById('variant_name').value = baseProductName + ' - ' + variantCode;
            }
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const variantCode = document.getElementById('variant_code').value.trim();
            if (!variantCode) {
                e.preventDefault();
                alert('Kód varianty je povinný!');
                document.getElementById('variant_code').focus();
                return false;
            }
            
            if (!/^[A-Z0-9]+$/.test(variantCode)) {
                e.preventDefault();
                alert('Kód varianty může obsahovat pouze velká písmena a čísla!');
                document.getElementById('variant_code').focus();
                return false;
            }
        });
    </script>
</body>
</html>