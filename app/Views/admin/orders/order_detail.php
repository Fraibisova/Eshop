<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail objednávky #<?php echo htmlspecialchars($order_number); ?></title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>        .product-info {
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

        .message {
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .message.success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .message.warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>
<?php echo $adminService->renderHeader(); ?>

    <div class="container">
        <h1>Detail objednávky #<?php echo htmlspecialchars($order_number); ?></h1>
        
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="message <?= $message['type'] ?>">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="order-summary">
            <h3>Súhrn objednávky</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <strong>Celkový počet položiek:</strong> <?php echo $order_summary['total_items']; ?>
                </div>
                <div class="summary-item">
                    <strong>Z toho predob jednávka:</strong> 
                    <span class="<?php echo $order_summary['has_preorder'] ? 'text-warning' : 'text-success'; ?>">
                        <?php echo $order_summary['preorder_items']; ?>
                    </span>
                </div>
                <div class="summary-item">
                    <strong>Celková hodnota:</strong> <?php echo number_format($order_summary['total_price'], 2, ',', ' '); ?> Kč
                </div>
            </div>
            
            <?php if ($order_summary['has_preorder']): ?>
                <div class="preorder-warning">
                    ⚠️ <strong>Upozornenie:</strong> Táto objednávka obsahuje položky na predob jednávku!
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tracking-section">
            <h3>Sledovací číslo</h3>
            <?php if (!empty($current_tracking)): ?>
                <div class="tracking-current">
                    <strong>Aktuální sledovací číslo:</strong> <?php echo htmlspecialchars($current_tracking); ?>
                </div>
            <?php else: ?>
                <p class="muted-text">Sledovací číslo není zatím nastaveno.</p>
            <?php endif; ?>
            
            <form method="post" class="form-inline" action="/admin/orders/detail?order_number=<?= htmlspecialchars($order_number) ?>">
                <div class="form-group">
                    <label for="tracking_number_only">Nové sledovací číslo:</label>
                    <input type="text" 
                           name="tracking_number_only" 
                           id="tracking_number_only" 
                           class="tracking-input"
                           value="<?php echo htmlspecialchars($current_tracking ?? ''); ?>"
                           placeholder="Zadejte sledovací číslo">
                </div>
                <button type="submit" name="update_tracking" class="btn btn-secondary">Aktualizovat sledovací číslo</button>
            </form>
            
            <?php if ($current_status === 'send' && !empty($current_tracking)): ?>
                <form method="post" class="shipping-email-form" action="/admin/orders/detail?order_number=<?= htmlspecialchars($order_number) ?>">
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
                $total_order_price = 0;
                foreach ($order_items as $item): 
                    $item_total = $item['final_price'] * $item['count'];
                    $total_order_price += $item_total;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['order_item_id']); ?></td>
                    
                    <td>
                        <div class="product-info">
                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                            <?php if (!empty($item['description_main'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['description_main'], 0, 100)); ?>...</small>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <td>
                        <?php if (!empty($item['variant_id'])): ?>
                            <div class="variant-info">
                                <strong><?php echo htmlspecialchars($item['variant_name'] ?? $item['variant_code']); ?></strong>
                                <?php if (!empty($item['color'])): ?>
                                    <br><span class="variant-detail">Farba: <?php echo htmlspecialchars($item['color']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['material'])): ?>
                                    <br><span class="variant-detail">Materiál: <?php echo htmlspecialchars($item['material']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Bez variantu</span>
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <?php echo htmlspecialchars($item['product_code']); ?>
                        <?php if (!empty($item['variant_code'])): ?>
                            <br><small><?php echo htmlspecialchars($item['variant_code']); ?></small>
                        <?php endif; ?>
                    </td>
                    
                    <td>
                        <?php echo number_format($item['final_price'], 2, ',', ' '); ?> Kč
                        <?php if (!empty($item['variant_id']) && $item['final_price'] != $item['base_price']): ?>
                            <br><small class="text-muted">Pôvodná: <?php echo number_format($item['base_price'], 2, ',', ' '); ?> Kč</small>
                        <?php endif; ?>
                    </td>
                    
                    <td class="text-center">
                        <strong><?php echo htmlspecialchars($item['count']); ?></strong>
                    </td>
                    
                    <td>
                        <strong><?php echo number_format($item_total, 2, ',', ' '); ?> Kč</strong>
                    </td>
                    
                    <td>
                        <?php if ($item['is_preorder']): ?>
                            <span class="status-badge preorder">Predob jednávka</span>
                        <?php else: ?>
                            <span class="status-badge in-stock">Skladom</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($item['stock_status'])): ?>
                            <br><small class="stock-status"><?php echo htmlspecialchars($item['stock_status']); ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td colspan="6" class="text-right"><strong>Celková suma objednávky:</strong></td>
                    <td><strong><?php echo number_format($total_order_price, 2, ',', ' '); ?> Kč</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <h2>Změna stavu objednávky</h2>
        <form method="post" action="/admin/orders/detail?order_number=<?= htmlspecialchars($order_number) ?>">
            <div class="form-group">
                <label for="order_status">Vyberte nový stav:</label>
                <select name="order_status" id="order_status">
                    <option value="waiting" <?php echo ($current_status === 'waiting') ? 'selected' : ''; ?>>Čeká na zpracování</option>
                    <option value="processed" <?php echo ($current_status === 'processed') ? 'selected' : ''; ?>>Zpracováno</option>
                    <option value="send" <?php echo ($current_status === 'send') ? 'selected' : ''; ?>>Odesláno</option>
                    <option value="completed" <?php echo ($current_status === 'completed') ? 'selected' : ''; ?>>Dokončeno</option>
                    <option value="cancel" <?php echo ($current_status === 'cancel') ? 'selected' : ''; ?>>Zrušena</option>
                </select>
            </div>
            
            <div class="form-group" id="tracking_group" class="tracking-group-hidden">
                <label for="tracking_number">Sledovací číslo (volitelné):</label>
                <input type="text" 
                       name="tracking_number" 
                       id="tracking_number" 
                       class="tracking-input"
                       value="<?php echo htmlspecialchars($current_tracking ?? ''); ?>"
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
            <h4>Predob jednávky:</h4>
            <ul>
                <li>Položky označené ako "Predob jednávka" môžu mať dlhšiu dodaciu lehotu</li>
                <li>Status sa automaticky nastavuje na základe dostupnosti variantu</li>
                <li>Objednávky s predob jednávkami vyžadujú špeciálnu pozornosť</li>
            </ul>
        </div>
    </div>
    
    <a href="/admin/orders" class="back-link">Zpět</a>

    <script src="/js/order_specific.js"></script>
</body>
</html>