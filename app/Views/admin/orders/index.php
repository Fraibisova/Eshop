<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/admin_style.css">
    <title>Seznam objednávek</title>
</head>
<body>
    <?php $adminService->renderHeader(); ?>

    <h1>Seznam objednávek</h1>
    
    <form method="GET" class="filter-form">
        <div class="filter-row">
            <input type="text" name="order_number" placeholder="Číslo objednávky" value="<?= htmlspecialchars($getParams['order_number'] ?? '') ?>">
            <input type="text" name="name" placeholder="Jméno" value="<?= htmlspecialchars($getParams['name'] ?? '') ?>">
            <input type="text" name="surname" placeholder="Příjmení" value="<?= htmlspecialchars($getParams['surname'] ?? '') ?>">
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($getParams['email'] ?? '') ?>">
            <button type="submit">Filtrovat</button>
            <a href="?" class="reset-filter">Resetovat</a>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Číslo objednávky</th>
                <th>Jméno</th>
                <th>Příjmení</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Ulice</th>
                <th>Číslo domu</th>
                <th>Město</th>
                <th>PSČ</th>
                <th>Způsob dopravy</th>
                <th>Způsob platby</th>
                <th>Pobočka</th>
                <th>Cena</th>
                <th>Stav platby</th>
                <th>Stav objednávky</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td>
                            <a href="/admin/orders/detail?order_number=<?= urlencode($order['order_number']) ?>">
                                <?= htmlspecialchars($order['order_number']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($order['name']) ?></td>
                        <td><?= htmlspecialchars($order['surname']) ?></td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><?= htmlspecialchars($order['street']) ?></td>
                        <td><?= htmlspecialchars($order['house_number']) ?></td>
                        <td><?= htmlspecialchars($order['city']) ?></td>
                        <td><?= htmlspecialchars($order['zipcode']) ?></td>
                        <td><?= htmlspecialchars($order['shipping_method']) ?></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td><?= htmlspecialchars($order['branch']) ?></td>
                        <td><?= htmlspecialchars($order['price']) ?> <?= htmlspecialchars($order['currency']) ?></td>
                        <td><?= htmlspecialchars($order['payment_status']) ?></td>
                        <td><?= htmlspecialchars($order['order_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="16">Žádné objednávky nebyly nalezeny.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?= $adminService->renderPagination($pagination['totalPages'], $pagination['currentPage'], '?', $getParams) ?>
    
    <div class="admin-actions">
        <a href="/admin/dashboard" class="back-link">Zpět na dashboard</a>
        <a href="/admin/orders/export" class="back-link">Exportovat objednávky</a>
    </div>
</body>
</html>