<?php
    define('APP_ACCESS', true);

    session_start();
    include "../config.php";
    include "../lib/function_admin.php";
    if(isset($_SESSION['user_id'])){
        $userId = $_SESSION['user_id']; 

        $stmt = $pdo->prepare("SELECT role_level FROM users WHERE id = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['role'] = $result['role_level'];
    }else{
        header("location: ../index.php");
        exit();
    }
    ?>
<!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel</title>
        <link rel="stylesheet" href="./css/admin_style.css">
    </head>
    <body>
    <?php
    adminHeader();
        try {
            $orders = executePaginatedQuery($pdo, "SELECT id, order_number, name, surname, email, phone, price, currency, timestamp FROM orders_user ORDER BY timestamp DESC", [], 0, 5);
        
    ?>

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
            <?php foreach ($orders as $order): ?>
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

    <?php
        } catch (PDOException $e) {
            echo "<p>Chyba při načítání objednávek: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
?>

</body>
</html>
