<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail objednávky #<?= htmlspecialchars($orderNumber) ?></title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .product-info {
            max-width: 200px;
        }

        .variant-info {
            font-size: 0.9em;
        }

        .variant-detail {
            color: #666;
            font-size: 0.85em;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-badge.preorder {
            background-color: #ffc107;
            color: #000;
        }

        .status-badge.in-stock {
            background-color: #28a745;
            color: white;
        }

        .stock-status {
            color: #6c757d;
            font-style: italic;
        }

        .total-row {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }

        .total-row td {
            padding: 12px 8px;
            font-size: 1.1em;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #6c757d;
        }

        .order-summary {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .summary-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }

        .preorder-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin-top: 12px;
        }

        .text-warning {
            color: #ffc107 !important;
            font-weight: bold;
        }

        .text-success {
            color: #28a745 !important;
        }

        .tracking-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .tracking-input {
            width: 300px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-success {
            background-color: #28a745;
        }

        .email-info-box {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
        }

        .admin-actions {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .back-link {
            display: inline-block;
            margin-right: 15px;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<?php $adminService->renderHeader(); ?>

<div class="container">
    <h1>Detail objednávky #<?= htmlspecialchars($orderNumber) ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="success-message">
            <?= nl2br(htmlspecialchars($message)) ?>
        </div>
    <?php endif; ?>
    
    <div class="order-summary">
        <h3>Souhrn objednávky</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <strong>Celkový počet položek:</strong> <?= $orderSummary['total_items'] ?>
            </div>
            <div class="summary-item">
                <strong>Z toho predobjednávka:</strong> 
                <span class="<?= $orderSummary['has_preorder'] ? 'text-warning' : 'text-success' ?>">
                    <?= $orderSummary['preorder_items'] ?>
                </span>
            </div>
            <div class="summary-item">
                <strong>Celková hodnota:</strong> <?= number_format($orderSummary['total_price'], 2, ',', ' ') ?> Kč
            </div>
        </div>
        
        <?php if ($orderSummary['has_preorder']): ?>
            <div class="preorder-warning">
                ⚠️ <strong>Upozornění:</strong> Tato objednávka obsahuje položky na predobjednávku!
            </div>
        <?php endif; ?>
    </div>
    
    <div class="tracking-section">
        <h3>Sledovací číslo</h3>
        <?php if (!empty($orderInfo['tracking_number'])): ?>
            <div class="tracking-current">
                <strong>Aktuální sledovací číslo:</strong> <?= htmlspecialchars($orderInfo['tracking_number']) ?>
            </div>
        <?php else: ?>
            <p class="muted-text">Sledovací číslo není zatím nastaveno.</p>
        <?php endif; ?>
        
        <form method="post" class="form-inline">
            <div class="form-group">
                <label for="tracking_number_only">Nové sledovací číslo:</label>
                <input type="text" 
                       name="tracking_number_only" 
                       id="tracking_number_only" 
                       class="tracking-input"
                       value="<?= htmlspecialchars($orderInfo['tracking_number'] ?? '') ?>"
                       placeholder="Zadejte sledovací číslo">
            </div>
            <button type="submit" name="update_tracking" class="btn btn-secondary">Aktualizovat sledovací číslo</button>
        </form>
        
        <?php if ($orderInfo['order_status'] === 'send' && !empty($orderInfo['tracking_number'])): ?>
            <form method="post" class="shipping-email-form">
                <button type="submit" name="send_shipping_email" class="btn btn-success">
                    Odeslat expediční email s aktuálním sledovacím číslem
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <h2>Položky objednávky</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Produkt</th>
                <th>Variant</th>
                <th>Kód</th>
                <th>Cena</th>
                <th>Počet</th>
                <th>Celkem</th>
                <th>Stav</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalOrderPrice = 0;
            foreach ($orderItems as $item): 
                $itemTotal = $item['final_price'] * $item['count'];
                $totalOrderPrice += $itemTotal;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['order_item_id']) ?></td>
                
                <td>
                    <div class="product-info">
                        <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                        <?php if (!empty($item['description_main'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(substr($item['description_main'], 0, 100)) ?>...</small>
                        <?php endif; ?>
                    </div>
                </td>
                
                <td>
                    <?php if (!empty($item['variant_id'])): ?>
                        <div class="variant-info">
                            <strong><?= htmlspecialchars($item['variant_name'] ?? $item['variant_code']) ?></strong>
                            <?php if (!empty($item['color'])): ?>
                                <br><span class="variant-detail">Barva: <?= htmlspecialchars($item['color']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['material'])): ?>
                                <br><span class="variant-detail">Materiál: <?= htmlspecialchars($item['material']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">Bez variantu</span>
                    <?php endif; ?>
                </td>
                
                <td>
                    <?= htmlspecialchars($item['product_code']) ?>
                    <?php if (!empty($item['variant_code'])): ?>
                        <br><small><?= htmlspecialchars($item['variant_code']) ?></small>
                    <?php endif; ?>
                </td>
                
                <td>
                    <?= number_format($item['final_price'], 2, ',', ' ') ?> Kč
                    <?php if (!empty($item['variant_id']) && $item['final_price'] != $item['base_price']): ?>
                        <br><small class="text-muted">Původní: <?= number_format($item['base_price'], 2, ',', ' ') ?> Kč</small>
                    <?php endif; ?>
                </td>
                
                <td class="text-center">
                    <strong><?= htmlspecialchars($item['count']) ?></strong>
                </td>
                
                <td>
                    <strong><?= number_format($itemTotal, 2, ',', ' ') ?> Kč</strong>
                </td>
                
                <td>
                    <?php if ($item['is_preorder']): ?>
                        <span class="status-badge preorder">Predobjednávka</span>
                    <?php else: ?>
                        <span class="status-badge in-stock">Skladem</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($item['stock_status'])): ?>
                        <br><small class="stock-status"><?= htmlspecialchars($item['stock_status']) ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="6" class="text-right"><strong>Celková suma objednávky:</strong></td>
                <td><strong><?= number_format($totalOrderPrice, 2, ',', ' ') ?> Kč</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <h2>Změna stavu objednávky</h2>
    <form method="post">
        <div class="form-group">
            <label for="order_status">Vyberte nový stav:</label>
            <select name="order_status" id="order_status">
                <option value="waiting" <?= ($orderInfo['order_status'] === 'waiting') ? 'selected' : '' ?>>Čeká na zpracování</option>
                <option value="processed" <?= ($orderInfo['order_status'] === 'processed') ? 'selected' : '' ?>>Zpracováno</option>
                <option value="send" <?= ($orderInfo['order_status'] === 'send') ? 'selected' : '' ?>>Odesláno</option>
                <option value="completed" <?= ($orderInfo['order_status'] === 'completed') ? 'selected' : '' ?>>Dokončeno</option>
                <option value="cancel" <?= ($orderInfo['order_status'] === 'cancel') ? 'selected' : '' ?>>Zrušena</option>
            </select>
        </div>
        
        <div class="form-group" id="tracking_group">
            <label for="tracking_number">Sledovací číslo (volitelné):</label>
            <input type="text" 
                   name="tracking_number" 
                   id="tracking_number" 
                   class="tracking-input"
                   value="<?= htmlspecialchars($orderInfo['tracking_number'] ?? '') ?>"
                   placeholder="Zadejte sledovací číslo">
            <small class="tracking-help-text">
                Pokud nevyplníte, použije se aktuální sledovací číslo nebo se vygeneruje automaticky.
            </small>
        </div>
        
        <button type="submit" class="btn">Uložit změny</button>
    </form>
    
    <div class="email-info-box">
        <h3>Informace o emailových notifikacích:</h3>
        <ul>
            <li><strong>Odesláno:</strong> Zákazník obdrží email s informacemi o expedici a sledovacím číslem</li>
            <li><strong>Zrušena:</strong> Zákazník obdrží email o zrušení objednávky</li>
            <li><strong>Ostatní stavy:</strong> Email se neodešle automaticky</li>
        </ul>
        <h4>Sledovací čísla:</h4>
        <ul>
            <li>Můžete nastavit sledovací číslo kdykoli pomocí formuláře výše</li>
            <li>Při změně stavu na "Odesláno" můžete zadat nové sledovací číslo</li>
            <li>Pokud je objednávka ve stavu "Odesláno", můžete kdykoliv odeslat expediční email znovu</li>
            <li>Pokud sledovací číslo není zadáno, vygeneruje se automaticky ve formátu CZ + číslo objednávky</li>
        </ul>
        <h4>Predobjednávky:</h4>
        <ul>
            <li>Položky označené jako "Predobjednávka" môžu mať dlhšiu dodaciu lehotu</li>
            <li>Status sa automaticky nastavuje na základe dostupnosti variantu</li>
            <li>Objednávky s predobjednávkami vyžadují špeciálnu pozornosť</li>
        </ul>
    </div>
</div>

<div class="admin-actions">
    <a href="/admin/orders" class="back-link">Zpět na seznam objednávek</a>
</div>

</body>
</html>