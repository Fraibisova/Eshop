<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varianty produktu</title>
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
        
        .variants-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .variants-table th,
        .variants-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .variants-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .btn {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .back-link {
            margin: 20px 0;
            display: inline-block;
            color: #007bff;
            text-decoration: none;
        }
        
        .no-variants {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    
    <div class="container">
        <h1>Varianty produktu</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($product)): ?>
            <div class="product-info">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><strong>Kód produktu:</strong> <?= htmlspecialchars($product['product_code']) ?></p>
                <p><strong>Cena:</strong> <?= number_format($product['price'], 2) ?> Kč</p>
                <p><strong>Kategorie:</strong> <?= htmlspecialchars($product['category']) ?></p>
            </div>
            
            <div style="margin: 20px 0;">
                <a href="/admin/products/variants/add?product_id=<?= $product['id'] ?>" class="btn btn-primary">
                    Přidat novou variantu
                </a>
            </div>
            
            <?php if (!empty($variants)): ?>
                <table class="variants-table">
                    <thead>
                        <tr>
                            <th>Kód varianty</th>
                            <th>Název</th>
                            <th>Cena</th>
                            <th>Skladem</th>
                            <th>Viditelnost</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $variant): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($variant['variant_code'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($variant['name'] ?? '') ?></td>
                                <td><?= number_format($variant['price'] ?? 0, 2) ?> Kč</td>
                                <td>
                                    <span class="<?= ($variant['stock_status'] ?? '') === 'Skladem' ? 'text-success' : 'text-danger' ?>">
                                        <?= htmlspecialchars($variant['stock_status'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?= ($variant['visible'] ?? 0) == 1 ? 'text-success' : 'text-muted' ?>">
                                        <?= ($variant['visible'] ?? 0) == 1 ? 'Ano' : 'Ne' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/admin/products/variants/edit?id=<?= $variant['id'] ?>" class="btn btn-warning">Upravit</a>
                                    <a href="/admin/products/variants/delete?id=<?= $variant['id'] ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Opravdu chcete smazat tuto variantu?')">Smazat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-variants">
                    <p>Tento produkt nemá žádné varianty.</p>
                    <p><a href="/admin/products/variants/add?product_id=<?= $product['id'] ?>" class="btn btn-primary">Přidat první variantu</a></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="message error">
                Produkt nenalezen nebo nastala chyba při načítání dat.
            </div>
        <?php endif; ?>
        
        <a href="/admin/products" class="back-link">&larr; Zpět na seznam produktů</a>
    </div>
</body>
</html>