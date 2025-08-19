<?php
define('APP_ACCESS', true);

session_start();
include '../lib/function_admin.php';
checkAdminRole();
$folder = isset($_GET['folder']) ? $_GET['folder'] : '';
$uploadDir = '../uploads/' . $folder;

if (!is_dir($uploadDir)) {
    die('Složka neexistuje.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    try {
        $file = $_FILES['image'];
        $fileName = basename($file['name']);
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowedTypes)) {
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                displaySuccessMessage("Obrázek byl nahrán: $fileName");
            } else {
                displayErrorMessage("Nastala chyba při nahrávání obrázku.");
            }
        } else {
            displayErrorMessage("Není povoleno nahrávání tohoto typu souboru.");
        }
    } catch (Exception $e) {
        displayErrorMessage($e->getMessage());
    }
}

$images = array_filter(glob($uploadDir . DIRECTORY_SEPARATOR . '*'), 'is_file');
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nahrávání obrázků</title>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
    <?php adminHeader(); ?>

    <h1>Nahrávání obrázků do složky: <?php echo htmlspecialchars($folder); ?></h1>

    <form method="POST" enctype="multipart/form-data">
        <label for="image">Vyberte obrázek:</label>
        <input type="file" name="image" id="image" required>
        <button type="submit">Nahrát obrázek</button>
    </form>

    <h2>Seznam obrázků:</h2>
    <ul>
        <?php
        foreach ($images as $image) {
            $imageName = basename($image);
            echo '<li><img src="' . htmlspecialchars($uploadDir . DIRECTORY_SEPARATOR . $imageName) . '" alt="' . htmlspecialchars($imageName) . '" width="100"></li>';
        }
        ?>
    </ul>

    <a href="upload.php" class="back-link">Zpět</a>
</body>
</html>
