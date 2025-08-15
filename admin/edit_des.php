<?php
define('APP_ACCESS', true);

session_start();
include "../config.php";
include "../lib/function_admin.php";
checkAdminRole();

$message = "";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Neplatné ID.");
}

$stmt = $pdo->prepare("SELECT * FROM items_description WHERE id_item = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Položka s tímto ID neexistuje.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        processProductDescription($pdo, $id, $_POST);
        displaySuccessMessage("Položka byla úspěšně aktualizována!");
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
    <title>Editace popisu</title>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
    <?php adminHeader(); ?>

    <h1>Upravit položku</h1>

    <!-- Zobrazení zprávy -->
    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="paragraph1">Paragraph 1:</label>
        <textarea name="paragraph1" id="paragraph1" rows="4" cols="50"><?= htmlspecialchars($item['paragraph1'] ?? '') ?></textarea><br><br>

        <label for="paragraph2">Paragraph 2:</label>
        <textarea name="paragraph2" id="paragraph2" rows="4" cols="50"><?= htmlspecialchars($item['paragraph2'] ?? '') ?></textarea><br><br>

        <label for="paragraph3">Paragraph 3:</label>
        <textarea name="paragraph3" id="paragraph3" rows="4" cols="50"><?= htmlspecialchars($item['paragraph3'] ?? '') ?></textarea><br><br>

        <label for="paragraph4">Paragraph 4:</label>
        <textarea name="paragraph4" id="paragraph4" rows="4" cols="50"><?= htmlspecialchars($item['paragraph4'] ?? '') ?></textarea><br><br>

        <label for="paragraph5">Paragraph 5:</label>
        <textarea name="paragraph5" id="paragraph5" rows="4" cols="50"><?= htmlspecialchars($item['paragraph5'] ?? '') ?></textarea><br><br>

        <label for="paragraph6">Paragraph 6:</label>
        <textarea name="paragraph6" id="paragraph6" rows="4" cols="50"><?= htmlspecialchars($item['paragraph6'] ?? '') ?></textarea><br><br>

        <button type="submit">Uložit změny</button>
    </form>
    <a href="edit_goods.php">Zpět</a>
</body>
</html>
