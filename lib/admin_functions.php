<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    die();
}
    function getAllNewsletters() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM newsletters ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        
    
    function adminHeader() {
        print('<nav>
            <a href="dashboard.php">Domů</a>
            <a href="newsletter.php">Newsletter</a>
            <a href="edit_website.php">Editovat web</a>
            <a href="edit_pages.php">Editovat stránky</a>
            <a href="add_goods.php">Přidat zboží</a>
            <a href="edit_goods.php">Upravit zboží</a>
            <a href="upload.php">Nahrát fotky</a>
            <a href="orders.php">Objednávky</a>
            <a href="users.php">Seznam uživatelů</a>
            <a href="../index.php">Přejít na web</a>
            <a href="../admin/logout.php">Odhlásit se</a>
        </nav>');
    }
?>