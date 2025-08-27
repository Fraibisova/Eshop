
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravit variantu: <?= htmlspecialchars($variant['variant_code']) ?></title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .form-content {
            padding: 30px;
        }
        
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
        
        .breadcrumb {
            background: #e9ecef;
            padding: 15px 30px;
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-secondary {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <?php $adminService->renderHeader(); ?>
    
    <div class="breadcrumb">
        <a href="/admin/products">Seznam produktů</a> › 
        <a href="/admin/products/variants?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a> › 
        Úprava varianty <?= htmlspecialchars($variant['variant_code']) ?>
    </div>

    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h1>Upravit variantu produktu</h1>
                <p>Variant kód: <?= htmlspecialchars($variant['variant_code']) ?></p>
            </div>
            
            <div class="form-content">
                <?php if (!empty($message)): ?>
                    <div class="message <?= strpos($message, 'Chyba') === 0 ? 'error' : 'success' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-info">
                    <h3 style="margin-top: 0;">Informace o produktu</h3>
                    <p><strong>Název:</strong> <?= htmlspecialchars($product['name']) ?></p>
                    <p><strong>Kód:</strong> <?= htmlspecialchars($product['product_code'] ?? '') ?></p>
                    <p><strong>Základní cena:</strong> <?= number_format($product['price'], 0, ',', ' ') ?> Kč</p>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="variant_code">Kód varianty *</label>
                            <input type="text" id="variant_code" name="variant_code" 
                                   value="<?= htmlspecialchars($variant['variant_code']) ?>" 
                                   required maxlength="50">
                        </div>
                        
                        <div class="form-group">
                            <label for="variant_name">Název varianty</label>
                            <input type="text" id="variant_name" name="variant_name" 
                                   value="<?= htmlspecialchars($variant['variant_name'] ?? '') ?>" 
                                   maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price_modifier">Cenová úprava (Kč)</label>
                            <input type="number" id="price_modifier" name="price_modifier" 
                                   value="<?= $variant['price_modifier'] ?? 0 ?>" 
                                   step="0.01">
                            <small>Kladná hodnota = přirážka, záporná = sleva</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_override">Pevná cena (Kč)</label>
                            <input type="number" id="price_override" name="price_override" 
                                   value="<?= $variant['price_override'] ?? '' ?>" 
                                   step="0.01">
                            <small>Pokud vyplněno, přepíše základní cenu + úpravu</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock_quantity">Skladové množství</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" 
                                   value="<?= $variant['stock_quantity'] ?? 0 ?>" 
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_status">Status skladu</label>
                            <select id="stock_status" name="stock_status">
                                <option value="Skladem" <?= ($variant['stock_status'] ?? '') === 'Skladem' ? 'selected' : '' ?>>Skladem</option>
                                <option value="Není skladem" <?= ($variant['stock_status'] ?? '') === 'Není skladem' ? 'selected' : '' ?>>Není skladem</option>
                                <option value="Předobjednat" <?= ($variant['stock_status'] ?? '') === 'Předobjednat' ? 'selected' : '' ?>>Předobjednat</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="primary_image">Hlavní obrázek</label>
                        <?php if (!empty($variant['primary_image'])): ?>
                            <img src="<?= htmlspecialchars($variant['primary_image']) ?>" 
                                 alt="Současný obrázek" class="current-image">
                            <p><small>Současný obrázek: <?= basename($variant['primary_image']) ?></small></p>
                        <?php endif; ?>
                        <input type="file" id="primary_image" name="primary_image" 
                               accept="image/*">
                        <small>Pouze JPG, PNG, GIF soubory. Maximální velikost 5MB.</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="color">Barva</label>
                            <input type="text" id="color" name="color" 
                                   value="<?= htmlspecialchars($variant['color'] ?? '') ?>" 
                                   maxlength="50">
                        </div>
                        
                        <div class="form-group">
                            <label for="dimensions">Rozměry</label>
                            <input type="text" id="dimensions" name="dimensions" 
                                   value="<?= htmlspecialchars($variant['dimensions'] ?? '') ?>" 
                                   maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="material">Materiál</label>
                        <input type="text" id="material" name="material" 
                               value="<?= htmlspecialchars($variant['material'] ?? '') ?>" 
                               maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Popis varianty</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($variant['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= ($variant['is_active'] ?? 1) ? 'checked' : '' ?>>
                            Aktivní varianta
                        </label>
                    </div>
                    
                    <div class="form-actions" style="margin-top: 30px; text-align: center;">
                        <button type="submit" class="btn btn-primary">
                            💾 Uložit změny
                        </button>
                        <a href="/admin/products/variants?id=<?= $product['id'] ?>" class="btn btn-secondary">
                            ❌ Zrušit
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function updatePricePreview() {
            const basePrice = <?= $product['price'] ?>;
            const priceModifier = parseFloat(document.getElementById('price_modifier').value) || 0;
            const priceOverride = parseFloat(document.getElementById('price_override').value);
            
            let finalPrice;
            if (priceOverride && priceOverride > 0) {
                finalPrice = priceOverride;
            } else {
                finalPrice = basePrice + priceModifier;
            }
            
        }
        
        document.getElementById('price_modifier').addEventListener('input', updatePricePreview);
        document.getElementById('price_override').addEventListener('input', updatePricePreview);
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const variantCode = document.getElementById('variant_code').value.trim();
            if (!variantCode) {
                e.preventDefault();
                alert('Kód varianty je povinný!');
                document.getElementById('variant_code').focus();
                return;
            }
            
            const priceOverride = parseFloat(document.getElementById('price_override').value);
            const priceModifier = parseFloat(document.getElementById('price_modifier').value) || 0;
            
            if (priceOverride && priceOverride < 0) {
                e.preventDefault();
                alert('Pevná cena nemůže být záporná!');
                document.getElementById('price_override').focus();
                return;
            }
            
            if (<?= $product['price'] ?> + priceModifier < 0) {
                e.preventDefault();
                alert('Finální cena nemůže být záporná!');
                document.getElementById('price_modifier').focus();
                return;
            }
        });
    </script>
</body>
</html>