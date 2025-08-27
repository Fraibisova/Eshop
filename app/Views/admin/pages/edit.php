<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Správa stránek</title>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
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
        
        .page-form {
            max-width: 800px;
            margin: 20px 0;
        }
        
        .page-form label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .page-form input[type="text"], 
        .page-form textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .page-form button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .page-form button:hover {
            background: #0056b3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .back-link {
            margin-top: 20px;
            display: inline-block;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php echo $adminService->renderHeader(); ?>
    
    <div class="container">
        <h1>Správa stránek</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, 'Chyba') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="page-form" action="<?= isset($pageId) && $pageId ? '/admin/pages?id=' . $pageId : '/admin/pages' ?>">
            <?php if (isset($pageId) && $pageId): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($pageId) ?>">
            <?php endif; ?>
            <label for="title">Název stránky:</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required><br><br>

            <label for="slug">Slug (např. "o-nas"):</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" required><br><br>

            <label for="content">Obsah stránky (HTML editor):</label>
            <textarea name="content" id="editor" rows="15"><?= htmlspecialchars($page['content'] ?? '') ?></textarea><br><br>

            <button type="submit"><?= isset($pageId) && $pageId ? 'Uložit změny' : 'Vytvořit stránku' ?></button>
        </form>

        <h2>Existující stránky</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Název</th>
                    <th>Slug</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($allPages)): ?>
                <?php foreach ($allPages as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['title']) ?></td>
                        <td><?= htmlspecialchars($p['slug']) ?></td>
                        <td>
                            <a href="?id=<?= $p['id'] ?>" class="btn-edit">Upravit</a>
                            <a href="?id=<?= $p['id'] ?>&action=delete" class="btn-delete" onclick="return confirm('Opravdu chcete smazat?')">Smazat</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Žádné stránky nejsou k dispozici.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        
        <a href="/admin/pages" class="back-link">Zpět na seznam stránek</a>
    </div>

    <script>
    ClassicEditor
        .create(document.querySelector('#editor'))
        .catch(error => {
            console.error(error);
        });
    </script>
</body>
</html>