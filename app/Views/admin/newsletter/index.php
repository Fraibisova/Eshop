<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <title>Seznam Newsletterů</title>
  <link rel="stylesheet" href="/css/admin_style.css">
</head>
<body>
<?php $adminService->renderHeader(); ?>

<h1>Seznam Newsletterů</h1>
<a href="/admin/newsletter/create">Vytvořit nový</a>
<?php echo $adminService->renderStatusFilter($statusFilter); ?>

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
          <a href="/admin/newsletter/edit?edit=<?= $nl['id'] ?>">🖉 Editovat</a> | 
          <a href="/admin/newsletter/delete?delete=<?= $nl['id'] ?>" onclick="return confirm('Opravdu smazat \"<?= htmlspecialchars($nl['title']) ?>\"?')">🗑️ Smazat</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>