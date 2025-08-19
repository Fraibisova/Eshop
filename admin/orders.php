<?php
define('APP_ACCESS', true);

session_start();
include '../config.php';
include '../lib/function_admin.php';
checkAdminRole();
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$columns = ['id', 'order_number', 'name', 'surname', 'email', 'phone', 'street', 'house_number', 'city', 'zipcode', 'shipping_method', 'payment_method', 'branch', 'ico', 'dic', 'company_name', 'price', 'currency', 'payment_status', 'order_status'];
$queryData = buildDynamicQuery('orders_user', $columns, $_GET, 'ID DESC');

$total_records = executeCountQuery($pdo, $queryData['countQuery'], $queryData['params']);
$pagination = calculatePagination($total_records, $records_per_page, $current_page);
$orders = executePaginatedQuery($pdo, $queryData['query'], $queryData['params'], $pagination['offset'], $records_per_page);
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">¨
    <link rel="stylesheet" href="./css/admin_style.css">
    <title>Seznam objednávek</title>

</head>
<body>
    <?php adminHeader(); ?>

    <h1>Seznam objednávek</h1>
    <?php echo renderFilterForm($columns, $_GET); ?>

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
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td>
                            <a href="order_specific.php?order_number=<?php echo urlencode($order['order_number']); ?>">
                                <?php echo htmlspecialchars($order['order_number']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($order['name']); ?></td>
                        <td><?php echo htmlspecialchars($order['surname']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td><?php echo htmlspecialchars($order['street']); ?></td>
                        <td><?php echo htmlspecialchars($order['house_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['city']); ?></td>
                        <td><?php echo htmlspecialchars($order['zipcode']); ?></td>
                        <td><?php echo htmlspecialchars($order['shipping_method']); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($order['branch']); ?></td>
                        <td><?php echo htmlspecialchars($order['price']); ?><?php echo htmlspecialchars($order['currency']); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_status']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="20">Žádné objednávky nebyly nalezeny.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php echo renderPagination($pagination['totalPages'], $pagination['currentPage'], '?', $_GET); ?>
    <a href="dashboard.php" class="back-link">Zpět</a>
    <a href="" class="back-link">Odeslat objednávky najednou</a>
</body>
</html>
