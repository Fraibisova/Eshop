<?php
define('APP_ACCESS', true);

include '../config.php';
include '../lib/function_admin.php';
session_start();
checkAdminRole();

$sections = ['slider', 'intro_text', 'categories', 'left_menu', 'before_footer', 'footer', 'mobile_nav'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($sections as $section) {
            $content = $_POST[$section] ?? '';
            $sql = "UPDATE homepage_content SET content = :content WHERE section = :section";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['content' => $content, 'section' => $section]);
        }
        displaySuccessMessage("Změny byly uloženy!");
    } catch (Exception $e) {
        displayErrorMessage($e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Editace úvodní stránky</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        textarea { width: 100%; max-width: 100%; }
        h1, h3 { color: #333; }
        form button { padding: 10px 20px; font-size: 16px; }
        .message { color: green; font-weight: bold; }
    </style>
    <link rel="stylesheet" href="./css/admin_style.css">
</head>
<body>
<?php adminHeader(); ?>

<h1>Editace úvodní stránky</h1>
<?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>

<form method="post">
    <?php foreach ($sections as $section): ?>
        <h3><?php echo ucfirst(str_replace('_', ' ', $section)); ?></h3>
        <textarea name="<?php echo $section; ?>" rows="10" id="<?php echo $section; ?>"><?php echo htmlspecialchars(get_existing_content($section)); ?></textarea>
        <br><br>
    <?php endforeach; ?>
    <button type="submit">Uložit změny</button>
</form>


</body>
</html>
