<?php
define('APP_ACCESS', true);

include "config.php";
include "template/template.php";
include "lib/function.php";

session_start();
$query = isset($_GET['query']) ? $_GET['query'] : '';

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $search_term = $_GET['query'];
    trackSearch($search_term);
}

$aggregated_cart = aggregateCart();
if ($query) {
    $results = searchProducts($pdo, $query);
    
    header_html_search($aggregated_cart, "search.php", 0);
    print('<div class="container">
        <section class="products-section top">');
        $breadcrumbs = [
            ['title' => 'Domů', 'url' => 'index.php'],
            ['title' => 'Vyhledávání']
        ];
        echo renderBreadcrumb($breadcrumbs);
    if ($results) {
        left_menu_html();
        print('<h2 class="search-h2">Výsledky hledání "'.$query.'"</h2>');
        print('<div class="products">');
        foreach ($results as $item) {
            echo renderProductCard($item);
        } 
                print('</div>');
        
    } else {
        
        left_menu_html_special();
        print('<h2 class="search-h2">Výsledky hledání "'.$query.'"</h2>');
        echo '<p class="unlucky">Nebyly nalezeny žádné produkty.</p>';
    }
    print('</section>');
} else {
    echo 'Zadejte klíčové slovo.';
}
?>
    <?php before_footer_html(); ?>
</div>
<?php footer_html(); ?>
<script src="js/mobile.js"></script>
<script src="js/loading.js"></script>

</body>
</html>
