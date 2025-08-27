<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= empty($folder) ? 'Nahrávání obrázků - Výběr složky' : 'Nahrávání obrázků' ?></title>
    <link rel="stylesheet" href="/css/admin_style.css">
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    
    <div class="container">
    <?php if (empty($folder)): ?>
        <h1>Nahrávání obrázků - Výběr složky</h1>

        <div class="folder-list">
            <h2>Dostupné složky:</h2>
            <?php if (!empty($folders)): ?>
                <ul class="folders">
                    <?php foreach ($folders as $folderItem): ?>
                        <li>
                            <a href="<?= htmlspecialchars($folderItem['url']) ?>" class="folder-link">
                                📁 <?= htmlspecialchars($folderItem['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Žádné složky nebyly nalezeny v uploads adresáři.</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($images)): ?>
        <div class="root-files">
            <h2>Soubory v hlavní složce uploads:</h2>
            <div class="image-grid">
                <?php foreach ($images as $image): ?>
                    <div class="image-item">
                        <img src="<?= htmlspecialchars($image['url']) ?>" alt="<?= htmlspecialchars($image['name']) ?>" width="100">
                        <p><?= htmlspecialchars($image['name']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="admin-actions">
            <a href="/admin/dashboard" class="back-link">Zpět na dashboard</a>
        </div>
        
    <?php else: ?>
        <h1>Nahrávání obrázků do složky: <?php echo htmlspecialchars($folder); ?></h1>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $message): ?>
                <div class="message <?= $message['type'] ?>">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="image">Vyberte obrázek:</label>
            <input type="file" name="image" id="image" required>
            <button type="submit">Nahrát obrázek</button>
        </form>

        <h2>Seznam obrázků:</h2>
        <ul>
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $image): ?>
                    <li><img src="<?= htmlspecialchars($image['url']) ?>" alt="<?= htmlspecialchars($image['name']) ?>" width="100"></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Žádné obrázky nebyly nalezeny.</li>
            <?php endif; ?>
        </ul>

        <div class="admin-actions">
            <a href="/admin/upload" class="back-link">Zpět na výběr složky</a>
            <a href="/admin/dashboard" class="back-link">Zpět na dashboard</a>
        </div>
    <?php endif; ?>
    </div>
    
    <style>
        .folder-list {
            margin: 20px 0;
        }
        
        .folders {
            list-style: none;
            padding: 0;
        }
        
        .folders li {
            margin: 10px 0;
        }
        
        .folder-link {
            display: inline-block;
            padding: 10px 15px;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .folder-link:hover {
            background-color: #e0e0e0;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .admin-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .admin-actions a {
            margin: 0 10px;
        }
        
        .image-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }
        
        .image-item {
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .image-item img {
            border-radius: 3px;
        }
        
        .image-item p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }
        
        .root-files {
            margin: 30px 0;
        }
    </style>
</body>
</html>