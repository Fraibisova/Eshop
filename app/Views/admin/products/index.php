<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seznam produktů</title>
    <link rel="stylesheet" href="/css/admin_style.css">
    <style>
        .variant-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 13px;
        }
        
        .variant-count {
            color: #666;
            font-style: italic;
        }
        
        .variant-actions {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .variant-actions a {
            padding: 2px 6px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 11px;
            text-align: center;
            transition: background-color 0.2s;
        }
        
        .btn-variants {
            background: #007bff;
            color: white;
        }
        
        .btn-variants:hover {
            background: #0056b3;
        }
        
        .btn-add-variant {
            background: #28a745;
            color: white;
        }
        
        .btn-add-variant:hover {
            background: #1e7e34;
        }
        
        .table-actions {
            min-width: 200px;
        }
        
        .action-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .basic-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .basic-actions a {
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 3px;
            text-decoration: none;
            background: #6c757d;
            color: white;
            transition: background-color 0.2s;
        }
        
        .basic-actions a:hover {
            background: #545b62;
        }
        
        .basic-actions a.edit {
            background: #ffc107;
            color: #212529;
        }
        
        .basic-actions a.edit:hover {
            background: #e0a800;
        }
        
        .basic-actions a.delete {
            background: #dc3545;
            color: white;
        }
        
        .basic-actions a.delete:hover {
            background: #c82333;
        }
        
        table {
            font-size: 14px;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px 8px;
        }
        
        td {
            padding: 8px;
            vertical-align: top;
        }
        
        .product-name {
            font-weight: 500;
            max-width: 200px;
            word-wrap: break-word;
        }
        
        .price-display {
            font-weight: 500;
            color: #2f244f;
        }
        
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
    </style>
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>

    <h1>Seznam produktů a jejich variant</h1>

    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="message <?= $message['type'] === 'error' ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <a href="/admin/products/add" class="btn" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">
            Přidat nový produkt
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Název</th>
                <th>Kód produktu</th>
                <th>Cena</th>
                <th>Varianty</th>
                <th class="table-actions">Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): 
                    $hasVariants = isset($variantCounts[$item['id']]) && $variantCounts[$item['id']] > 0;
                    $variantCount = $variantCounts[$item['id']] ?? 0;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td class="product-name"><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['product_code']) ?></td>
                        <td class="price-display"><?= number_format($item['price'], 0, ',', ' ') ?> Kč</td>
                        
                        <td>
                            <div class="variant-info">
                                <?php if ($hasVariants): ?>
                                    <span class="variant-count"><?= $variantCount ?> variant<?= $variantCount > 1 ? 'y' : 'a' ?></span>
                                    <div class="variant-actions">
                                        <a href="/admin/products/variants?id=<?= htmlspecialchars($item['id']) ?>" class="btn-variants">
                                            Zobrazit varianty
                                        </a>
                                        <a href="/admin/products/variants/add?product_id=<?= htmlspecialchars($item['id']) ?>" class="btn-add-variant">
                                            Přidat variantu
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span class="variant-count">Žádné varianty</span>
                                    <div class="variant-actions">
                                        <a href="/admin/products/variants/add?product_id=<?= htmlspecialchars($item['id']) ?>" class="btn-add-variant">
                                            Přidat variantu
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td class="table-actions">
                            <div class="action-group">
                                <div class="basic-actions">
                                    <a href="/admin/products/edit?id=<?= htmlspecialchars($item['id']) ?>" class="edit">Upravit</a>
                                    <a href="/admin/products/description/edit?id=<?= htmlspecialchars($item['id']) ?>">Popis</a>
                                    <a href="/admin/products/delete?id=<?= htmlspecialchars($item['id']) ?>" 
                                       class="delete"
                                       onclick="return confirm('Opravdu chcete smazat tento produkt? Budou smazány i všechny jeho varianty!')">
                                       Smazat
                                    </a>
                                </div>
                                
                                <?php if ($hasVariants): ?>
                                    <div style="margin-top: 5px; font-size: 11px; color: #666;">
                                        <strong>Rychlé akce s variantami:</strong><br>
                                        <a href="/admin/products/variants?id=<?= htmlspecialchars($item['id']) ?>" 
                                           style="color: #007bff; text-decoration: none;">
                                           Spravovat všechny varianty →
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                        <p>Žádné produkty nenalezeny.</p>
                        <a href="/admin/products/add" style="color: #28a745;">Přidat první produkt</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!empty($pagination)): ?>
        <div style="margin-top: 20px;">
            <?php echo $adminService->renderPagination($pagination['totalPages'], $pagination['currentPage']); ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <a href="/admin/dashboard" class="back-link">Zpět na dashboard</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const variantCounts = document.querySelectorAll('.variant-count');
            variantCounts.forEach(function(element) {
                element.style.cursor = 'help';
                if (element.textContent.includes('variant')) {
                    element.title = 'Tento produkt má definované varianty (A, B, C, atď.)';
                } else {
                    element.title = 'Tento produkt nemá žádné varianty. Klikněte na "Přidat variantu" pro vytvoření první varianty.';
                }
            });

            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });

            const deleteLinks = document.querySelectorAll('a.delete');
            deleteLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const row = this.closest('tr');
                    const variantCount = row.querySelector('.variant-count').textContent;
                    
                    if (variantCount.includes('variant')) {
                        const confirmed = confirm(
                            'POZOR: Tento produkt má varianty!\n\n' +
                            'Smazáním produktu se automaticky smažou i všechny jeho varianty.\n' +
                            'Tato akce je nevratná.\n\n' +
                            'Opravdu chcete pokračovat?'
                        );
                        
                        if (!confirmed) {
                            e.preventDefault();
                            return false;
                        }
                    }
                });
            });
        });
    </script>

</body>
</html>