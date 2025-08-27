<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Správa stránek</title>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <link rel="stylesheet" href="/css/admin_style.css">

</head>
<body>
<?php echo $adminService->renderHeader(); ?>

<h1>Správa stránek</h1>

<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $message): ?>
        <div class="message <?= $message['type'] ?>">
            <?= htmlspecialchars($message['text']) ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<form method="post" action="/admin/pages<?= isset($page['id']) ? '?id=' . $page['id'] : '' ?>">
    <?php if (isset($page['id'])): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($page['id']) ?>">
    <?php endif; ?>
    
    <label>Název stránky:</label><br>
    <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required><br><br>

    <label>Slug (např. "o-nas"):</label><br>
    <input type="text" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" required><br><br>

    <label>Obsah stránky (HTML editor):</label><br>
    <textarea name="content" id="editor" rows="15"><?= htmlspecialchars($page['content'] ?? '') ?></textarea><br><br>

    <button type="submit"><?= isset($page['id']) ? 'Uložit změny' : 'Uložit stránku' ?></button>
</form>

<h2>Existující stránky</h2>
<table>
    <thead>
        <tr><th>Název</th><th>Slug</th><th>Akce</th></tr>
    </thead>
    <tbody>
    <?php if (!empty($pages)): ?>
        <?php foreach ($pages as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['slug']) ?></td>
                <td>
                    <a href="/admin/pages?id=<?= $p['id'] ?>">Upravit</a> |
                    <a href="/admin/pages?id=<?= $p['id'] ?>&action=delete" class="danger" onclick="return confirm('Opravdu chcete smazat?')">Smazat</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="3">Žádné stránky nebyly nalezeny.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<script>
ClassicEditor
    .create(document.querySelector('#editor'))
    .catch(error => {
        console.error(error);
    });
</script>

</body>
</html>