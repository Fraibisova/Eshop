<?php
define('APP_ACCESS', true);

require '../config.php';
require '../lib/function_admin.php';
checkAdminRole();
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

if (!empty($statusFilter)) {
    $newsletters = executePaginatedQuery($pdo, "SELECT * FROM newsletters WHERE status = ? ORDER BY created_at DESC", [$statusFilter], 0, 1000);
} else {
    $newsletters = executePaginatedQuery($pdo, "SELECT * FROM newsletters ORDER BY created_at DESC", [], 0, 1000);
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <title>Seznam Newsletterů</title>
  <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
<?php adminHeader(); ?>

<h1>Seznam Newsletterů</h1>
<a href="add_newsletter.php">Vytvořit nový</a>
<?php echo renderStatusFilter($statusFilter); ?>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Titulek</th>
      <th>Status</th>
      <th>Naplánováno</th>
      <th>Vytvořeno</th>
      <th>Akce</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($newsletters)): ?>
      <tr><td colspan="6">Žádné newslettery nebyly nalezeny.</td></tr>
    <?php else: ?>
      <?php foreach ($newsletters as $nl): ?>
        <tr>
          <td><?= htmlspecialchars($nl['id']) ?></td>
          <td><?= htmlspecialchars($nl['title']) ?></td>
          <td><?= htmlspecialchars($nl['status']) ?></td>
          <td><?= htmlspecialchars($nl['scheduled_at'] ?? '-') ?></td>
          <td><?= htmlspecialchars($nl['created_at']) ?></td>
          <td class="actions">
          <a href="edit_newsletter.php?edit=<?= $nl['id'] ?>">🖉 Editovat</a> | 
          <a href="delete_newsletter.php?delete=<?= $nl['id'] ?>" onclick="return confirm('Opravdu smazat \"<?= htmlspecialchars($nl['title']) ?>\"?')">🗑️ Smazat</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>