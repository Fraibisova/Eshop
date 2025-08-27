<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smazat variantu: <?= htmlspecialchars($variant['variant_code']) ?></title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .warning-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .warning-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .warning-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .warning-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .warning-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .warning-content {
            padding: 30px;
        }
        
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .variant-details {
            background: #fff3cd;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid #ffeaa7;
        }
        
        .variant-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #ddd;
            float: right;
            margin-left: 15px;
        }
        
        .warning-list {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid #f5c6cb;
        }
        
        .warning-list h4 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warning-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .warning-list li {
            margin-bottom: 8px;
        }
        
        .last-variant-warning {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid #bee5eb;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            min-width: 140px;
            justify-content: center;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            box-shadow: 0 2px 10px rgba(220,53,69,0.3);
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220,53,69,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 2px 10px rgba(108,117,125,0.3);
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(108,117,125,0.4);
        }
        
        .confirmation-checkbox {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 25px 0;
            border: 2px solid #dee2e6;
            text-align: center;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 16px;
        }
        
        .checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: #dc3545;
        }
        
        .breadcrumb {
            background: #e9ecef;
            padding: 15px 30px;
            color: #666;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .warning-container {
                margin: 20px;
                max-width: none;
            }
            
            .warning-content {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 200px;
            }
            
            .variant-image {
                float: none;
                display: block;
                margin: 0 auto 15px;
            }
        }
    </style>
</head>
<body>
    <?php $adminService->renderHeader(); ?>

    <div class="breadcrumb">
        <a href="/admin/products">Seznam produktů</a> › 
        <a href="/admin/products/variants?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a> › 
        Smazání varianty <?= htmlspecialchars($variant['variant_code']) ?>
    </div>

    <div class="warning-container">
        <div class="warning-header">
            <div class="warning-icon">⚠️</div>
            <h1 class="warning-title">Smazání varianty</h1>
            <p class="warning-subtitle">Tato akce je nevratná!</p>
        </div>

        <div class="warning-content">
            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <strong>Chyba:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="product-info">
                <h3 style="margin-top: 0;">📦 Informace o produktu</h3>
                <div class="info-row">
                    <span class="info-label">Název produktu:</span>
                    <span class="info-value"><?= htmlspecialchars($product['name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kód produktu:</span>
                    <span class="info-value"><?= htmlspecialchars($product['product_code'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Základní cena:</span>
                    <span class="info-value"><?= number_format($product['price'], 0, ',', ' ') ?> Kč</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Celkem variant:</span>
                    <span class="info-value"><?= $variantCount ?></span>
                </div>
            </div>

            <div class="variant-details">
                <h3 style="margin-top: 0;">🎯 Varianta určená ke smazání</h3>
                
                <?php if (!empty($variant['primary_image'])): ?>
                    <img src="<?= htmlspecialchars($variant['primary_image']) ?>" 
                         alt="Varianta <?= htmlspecialchars($variant['variant_code']) ?>"
                         class="variant-image">
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">Kód varianty:</span>
                    <span class="info-value"><strong><?= htmlspecialchars($variant['variant_code']) ?></strong></span>
                </div>
                
                <?php if (!empty($variant['variant_name'])): ?>
                    <div class="info-row">
                        <span class="info-label">Název:</span>
                        <span class="info-value"><?= htmlspecialchars($variant['variant_name']) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php 
                $finalPrice = !empty($variant['price_override']) ? 
                    $variant['price_override'] : 
                    $product['price'] + ($variant['price_modifier'] ?? 0);
                ?>
                <div class="info-row">
                    <span class="info-label">Finální cena:</span>
                    <span class="info-value"><?= number_format($finalPrice, 0, ',', ' ') ?> Kč</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Skladem:</span>
                    <span class="info-value"><?= $variant['stock_quantity'] ?? 0 ?> ks</span>
                </div>
            </div>

            <?php if ($variantCount <= 1): ?>
                <div class="last-variant-warning">
                    <h4 style="margin-top: 0;">ℹ️ Důležité upozornění</h4>
                    <p style="margin-bottom: 0;">
                        <strong>Toto je poslední varianta tohoto produktu!</strong><br>
                        Po smazání bude produkt označen jako "bez variant" a budete přesměrováni na seznam produktů.
                    </p>
                </div>
            <?php endif; ?>

            <div class="warning-list">
                <h4>🚨 Co se stane po smazání:</h4>
                <ul>
                    <li>Varianta "<?= htmlspecialchars($variant['variant_code']) ?>" bude <strong>trvale smazána</strong></li>
                    <li>Všechny údaje o této variantě budou <strong>nenávratně ztraceny</strong></li>
                    <?php if (($variant['stock_quantity'] ?? 0) > 0): ?>
                        <li><strong>Pozor:</strong> Ve skladu je ještě <?= $variant['stock_quantity'] ?> ks této varianty!</li>
                    <?php endif; ?>
                    <?php if ($variantCount <= 1): ?>
                        <li>Produkt bude označen jako "nemá varianty"</li>
                    <?php else: ?>
                        <li>Zůstane <?= $variantCount - 1 ?> aktivních variant</li>
                    <?php endif; ?>
                </ul>
            </div>

            <form method="post" id="delete-form">
                <div class="confirmation-checkbox">
                    <div class="checkbox-group">
                        <input type="checkbox" id="confirm_checkbox" class="checkbox-input" required>
                        <label for="confirm_checkbox">
                            <strong>Potvrzuji, že chci trvale smazat variantu "<?= htmlspecialchars($variant['variant_code']) ?>"</strong>
                        </label>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="confirm_delete" value="yes" class="btn btn-danger" id="delete-btn" disabled>
                        🗑️ Definitivně smazat
                    </button>
                    <button type="submit" name="cancel" class="btn btn-secondary">
                        ❌ Zrušit a vrátit se
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('confirm_checkbox').addEventListener('change', function() {
            const deleteBtn = document.getElementById('delete-btn');
            deleteBtn.disabled = !this.checked;
            
            if (this.checked) {
                deleteBtn.style.opacity = '1';
                deleteBtn.style.cursor = 'pointer';
            } else {
                deleteBtn.style.opacity = '0.5';
                deleteBtn.style.cursor = 'not-allowed';
            }
        });

        document.getElementById('delete-form').addEventListener('submit', function(e) {
            if (e.submitter && e.submitter.name === 'confirm_delete') {
                const variantCode = '<?= htmlspecialchars($variant['variant_code'], ENT_QUOTES) ?>';
                const isLastVariant = <?= $variantCount <= 1 ? 'true' : 'false' ?>;
                
                let confirmMessage = `POSLEDNÍ VAROVÁNÍ!\n\nOpravdu chcete TRVALE smazat variantu "${variantCode}"?\n\nTato akce je NEVRATNÁ!`;
                
                if (isLastVariant) {
                    confirmMessage += '\n\nToto je POSLEDNÍ varianta produktu!';
                }
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
                
                const typedConfirmation = prompt(
                    `Pro potvrzení zadejte kód varianty "${variantCode}" (bez uvozovek):`
                );
                
                if (typedConfirmation !== variantCode) {
                    e.preventDefault();
                    alert('Kód varianty se neshoduje. Smazání bylo zrušeno.');
                    return false;
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('confirm_checkbox').focus();
        });
    </script>
</body>
</html>