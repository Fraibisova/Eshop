<!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel</title>
        <link rel="stylesheet" href="/css/admin_style.css">
    </head>
    <body>
    <?php $adminService->renderHeader(); ?>

    <h2>Posledních 5 objednávek:</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Číslo objednávky</th>
                <th>Jméno</th>
                <th>Příjmení</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Cena</th>
                <th>Měna</th>
                <th>Čas objednávky</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['id']) ?></td>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td><?= htmlspecialchars($order['name']) ?></td>
                    <td><?= htmlspecialchars($order['surname']) ?></td>
                    <td><?= htmlspecialchars($order['email']) ?></td>
                    <td><?= htmlspecialchars($order['phone']) ?></td>
                    <td><?= htmlspecialchars($order['price']) ?></td>
                    <td><?= htmlspecialchars($order['currency']) ?></td>
                    <td><?= htmlspecialchars($order['timestamp']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>