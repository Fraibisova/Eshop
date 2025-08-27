<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smazat produkt</title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .confirmation-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .product-details {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .warning {
            color: #856404;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    
    <div class="container">
        <h1>Smazat produkt</h1>
        
        <?php if (!empty($product)): ?>
            <div class="confirmation-box">
                <p class="warning">⚠️ Opravdu chcete smazat tento produkt?</p>
                <p>Tato akce je <strong>nevratná</strong> a odstraní také všechny varianty tohoto produktu.</p>
            </div>
            
            <div class="product-details">
                <h3>Detail produktu:</h3>
                <p><strong>Název:</strong> <?= htmlspecialchars($product['name']) ?></p>
                <p><strong>Kód:</strong> <?= htmlspecialchars($product['product_code']) ?></p>
                <p><strong>Cena:</strong> <?= number_format($product['price'], 2) ?> Kč</p>
                <p><strong>Kategorie:</strong> <?= htmlspecialchars($product['category']) ?></p>
                <p><strong>Stav skladu:</strong> <?= htmlspecialchars($product['stock']) ?></p>
                <?php if (!empty($product['image'])): ?>
                    <p><strong>Obrázek:</strong> <?= htmlspecialchars($product['image']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Jste si jisti, že chcete smazat tento produkt? Tato akce je nevratná!')">
                        🗑️ Ano, smazat produkt
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <div class="message error">
                Produkt nenalezen nebo nastala chyba při načítání dat.
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="/admin/products">&larr; Zpět na seznam produktů</a>
        </div>
    </div>
</body>
</html>