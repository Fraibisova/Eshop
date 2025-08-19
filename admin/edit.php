<?php
define('APP_ACCESS', true);

session_start();
include "../config.php";
include "../lib/function_admin.php";
checkAdminRole();

$item = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $item = getProductById($pdo, $_GET['id']);
    if (!$item) {
        die("Položka s ID {$_GET['id']} neexistuje.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $_POST['stock'] = $_POST['stock_status'];
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
    <title>Úprava produktu</title>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
    <?php adminHeader(); ?>

    <h1>Úprava produktu</h1>

    <?php if ($item): ?>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

            <label for="name">Název produktu:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>" maxlength="100" required>
            <br><br>

            <label for="product_code">Kód produktu:</label>
            <input type="number" id="product_code" name="product_code" value="<?= htmlspecialchars($item['product_code']) ?>" required>
            <br><br>

            <label for="description_main">Popis produktu:</label>
            <textarea id="description_main" name="description_main" maxlength="10000" rows="5" required><?= htmlspecialchars($item['description_main']) ?></textarea>
            <br><br>

            <label for="price">Cena (včetně DPH):</label>
            <input type="number" id="price" name="price" value="<?= htmlspecialchars($item['price']) ?>" step="0.01" required>
            <br><br>

            <label for="price_without_dph">Cena (bez DPH):</label>
            <input type="number" id="price_without_dph" name="price_without_dph" value="<?= htmlspecialchars($item['price_without_dph']) ?>" step="0.01" required>
            <br><br>

            <label for="image">Obrázek produktu:</label>
            <input type="file" id="image" name="image" accept="image/*">
            <p>Aktuální obrázek: <?= htmlspecialchars($item['image']) ?></p>
            <br><br>

            <label for="image_folder">Složka obrázku:</label>
            <input type="text" id="image_folder" name="image_folder" value="<?= htmlspecialchars($item['image_folder']) ?>" maxlength="1000" required>
            <br><br>

            <label for="mass">Hmotnost (g):</label>
            <input type="number" id="mass" name="mass" value="<?= htmlspecialchars($item['mass']) ?>" step="1" required>
            <br><br>

            <label for="visible">Viditelnost:</label>
            <select id="visible" name="visible" required>
                <option value="1" <?= $item['visible'] == 1 ? 'selected' : '' ?>>Ano</option>
                <option value="0" <?= $item['visible'] == 0 ? 'selected' : '' ?>>Ne</option>
            </select>
            <br><br>

            <label for="category">Kategorie:</label>
            <input type="text" id="category" name="category" value="<?= htmlspecialchars($item['category']) ?>" maxlength="1000" required>
            <br><br>

            <label for="stock_status">Skladová dostupnost:</label>
            <select id="stock_status" name="stock_status" required>
                <option value="Skladem" <?= $item['stock'] == 'Skladem' ? 'selected' : '' ?>>Skladem</option>
                <option value="Není skladem" <?= $item['stock'] == 'Není skladem' ? 'selected' : '' ?>>Není skladem</option>
                <option value="Předobjednat" <?= $item['stock'] == 'Předobjednat' ? 'selected' : '' ?>>Předobjednat</option>
            </select>
            <br><br>

            <button type="submit">Uložit změny</button>
        </form>
    <?php else: ?>
        <p>Produkt nenalezen.</p>
    <?php endif; ?>

    <a href="edit_goods.php">Zpět</a>
</body>
</html>