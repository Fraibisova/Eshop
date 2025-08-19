<?php
define('APP_ACCESS', true);
include 'config.php';
include 'template/template.php';
include 'lib/function.php';
ob_start();
session_start();
$slug = $_GET['page'] ?? '';
$aggregated_cart = aggregateCart();
$totalPrice = calculateCartPrice($aggregated_cart, $pdo);
$freeShippingData = calculateFreeShippingProgress($totalPrice);
$endprice = $freeShippingData['remaining'];
header_html($aggregated_cart, "index.php");
print('<div class="container">');

if (!$slug) {
    echo "Stránka nenalezena.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug");
$stmt->execute(['slug' => $slug]);
$page = $stmt->fetch();

if (!$page) {
    echo "Stránka nenalezena.";
    exit;
}
print('<section class="products-section">');
print('<div class="page">');
echo "<h1>" . htmlspecialchars($page['title']) . "</h1>";
echo "<div class='content'>" . $page['content'] . "</div>";
print('</div>');
if($slug == 'reklamace-a-vraceni-zbozi'){
    print('<div class="iframe">');
    print('<iframe src="https://docs.google.com/forms/d/e/1FAIpQLSd603aDOx7nF1161Sx2J6CrCWuoBeyBkUlIt094iEtST5LmBQ/viewform?embedded=true" width="640" height="1550" frameborder="0" marginheight="0" marginwidth="0">Načítání…</iframe>');
    print('</div>');
}
?>
</section>
    <?php before_footer_html(); ?>
    </div>
</div>
<?php footer_html(); ?>
<script src="../js/cart.js"></script>
<script src="../js/mobile.js"></script>

</body>
</html>
