<?php

define('APP_ACCESS', true);

include '../config.php';
include '../lib/function_admin.php';
session_start();
checkAdminRole();

$success = '';
$error = '';
$page_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($action === 'delete' && $page_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM pages WHERE id = :id");
        $stmt->execute(['id' => $page_id]);
        displaySuccessMessage("Stránka byla smazána.");
    } catch (Exception $e) {
        displayErrorMessage($e->getMessage());
    }
}

$page = ['title' => '', 'slug' => '', 'content' => ''];

if ($page_id && $action !== 'delete') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = :id");
        $stmt->execute(['id' => $page_id]);
        $page = $stmt->fetch();
        if (!$page) {
            displayErrorMessage("Stránka nenalezena.");
        }
    } catch (Exception $e) {
        displayErrorMessage($e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $content = $_POST['content'];

    if ($title && $slug && $content) {
        try {
            if ($page_id) {
                $stmt = $pdo->prepare("UPDATE pages SET title = :title, slug = :slug, content = :content WHERE id = :id");
                $stmt->execute(['title' => $title, 'slug' => $slug, 'content' => $content, 'id' => $page_id]);
                displaySuccessMessage("Stránka byla aktualizována.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content) VALUES (:title, :slug, :content)");
                $stmt->execute(['title' => $title, 'slug' => $slug, 'content' => $content]);
                displaySuccessMessage("Stránka byla vytvořena.");
            }
        } catch (Exception $e) {
            displayErrorMessage($e->getMessage());
        }
    } else {
        displayErrorMessage("Vyplň všechna pole.");
    }
}

$allPages = $pdo->query("SELECT * FROM pages ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Správa stránek</title>
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <link rel="stylesheet" href="./css/admin_style.css">

</head>
<body>
<?php adminHeader(); ?>

<h1>Správa stránek</h1>

<?php if ($success): ?><p style="color:green"><?= $success ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>

<form method="post">
    <label>Název stránky:</label><br>
    <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required><br><br>

    <label>Slug (např. "o-nas"):</label><br>
    <input type="text" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" required><br><br>

    <label>Obsah stránky (HTML editor):</label><br>
    <textarea name="content" id="editor" rows="15"><?= htmlspecialchars($page['content'] ?? '') ?></textarea><br><br>

    <button type="submit">Uložit stránku</button>
</form>

<h2>Existující stránky</h2>
<table>
    <thead>
        <tr><th>Název</th><th>Slug</th><th>Akce</th></tr>
    </thead>
    <tbody>
    <?php foreach ($allPages as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><?= htmlspecialchars($p['slug']) ?></td>
            <td>
                <a href="?id=<?= $p['id'] ?>">Upravit</a> |
                <a href="?id=<?= $p['id'] ?>&action=delete" class="danger" onclick="return confirm('Opravdu chcete smazat?')">Smazat</a>
            </td>
        </tr>
    <?php endforeach; ?>
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
