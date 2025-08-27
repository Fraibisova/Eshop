<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace popisu</title>
    <link rel="stylesheet" href="/css/admin_style.css">
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    
    <div class="container">
        <h1>Editace popisu produktu</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($item)): ?>
            <div class="product-info" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h3>Produkt: <?= htmlspecialchars($item['name']) ?></h3>
                <p><strong>Kód:</strong> <?= htmlspecialchars($item['product_code']) ?></p>
            </div>
        <?php endif; ?>

    <form action="" method="POST">
        <label for="paragraph1">Paragraph 1:</label>
        <textarea name="paragraph1" id="paragraph1" rows="4" cols="50"><?= htmlspecialchars($item['paragraph1'] ?? '') ?></textarea><br><br>

        <label for="paragraph2">Paragraph 2:</label>
        <textarea name="paragraph2" id="paragraph2" rows="4" cols="50"><?= htmlspecialchars($item['paragraph2'] ?? '') ?></textarea><br><br>

        <label for="paragraph3">Paragraph 3:</label>
        <textarea name="paragraph3" id="paragraph3" rows="4" cols="50"><?= htmlspecialchars($item['paragraph3'] ?? '') ?></textarea><br><br>

        <label for="paragraph4">Paragraph 4:</label>
        <textarea name="paragraph4" id="paragraph4" rows="4" cols="50"><?= htmlspecialchars($item['paragraph4'] ?? '') ?></textarea><br><br>

        <label for="paragraph5">Paragraph 5:</label>
        <textarea name="paragraph5" id="paragraph5" rows="4" cols="50"><?= htmlspecialchars($item['paragraph5'] ?? '') ?></textarea><br><br>

        <label for="paragraph6">Paragraph 6:</label>
        <textarea name="paragraph6" id="paragraph6" rows="4" cols="50"><?= htmlspecialchars($item['paragraph6'] ?? '') ?></textarea><br><br>

        <button type="submit" class="btn btn-primary">Uložit změny</button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="/admin/products">&larr; Zpět na seznam produktů</a>
    </div>
    </div>
</body>
</html>
