<?php
define('APP_ACCESS', true);

session_start();
include "../config.php";
include "../lib/function_admin.php";
checkAdminRole();

$itemsPerPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$totalItems = executeCountQuery($pdo, "SELECT COUNT(*) FROM items", []);
$pagination = calculatePagination($totalItems, $itemsPerPage, $page);
$items = executePaginatedQuery($pdo, "SELECT * FROM items ORDER BY id DESC", [], $pagination['offset'], $itemsPerPage);

$item = null;
if (isset($_GET['id']) and is_numeric($_GET['id'])) {
    $item = getProductById($pdo, $_GET['id']);
    if (!$item) {
        die("Položka s ID {$_GET['id']} neexistuje.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' and $_SESSION['role'] == 10) {
    try {
        processProductUpdate($pdo, $_POST, $item['image'] ?? '', $_FILES['image'] ?? null);
        displaySuccessMessage("Produkt byl úspěšně aktualizován!");
    } catch (Exception $e) {
        displayErrorMessage($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seznam produktů</title>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
    <?php adminHeader(); ?>

    <h1>Seznam produktů</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Název</th>
                <th>Kód produktu</th>
                <th>Cena</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['id']) ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['product_code']) ?></td>
                    <td><?= htmlspecialchars($item['price']) ?> Kč</td>
                    <td>
                        <a href="edit.php?id=<?= htmlspecialchars($item['id']) ?>">Upravit</a>
                        <a href="edit_des.php?id=<?= htmlspecialchars($item['id']) ?>">Upravit popis</a>
                        <a href="delete.php?id=<?= htmlspecialchars($item['id']) ?>" onclick="return confirm('Opravdu chcete smazat tento produkt?')">Smazat</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php echo renderPagination($pagination['totalPages'], $pagination['currentPage']); ?>
    <a href="dashboard.php" class="back-link">Zpět</a>

</body>
</html>
