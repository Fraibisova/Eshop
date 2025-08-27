<?php

$aggregated_cart = [];
if(!empty($_SESSION['cart'])){
    foreach ($_SESSION['cart'] as $cartItem) {
        $id = $cartItem['id'];
        if (!isset($aggregated_cart[$id])) {
            $aggregated_cart[$id] = $cartItem;
        } else {
            $aggregated_cart[$id]['quantity'] += $cartItem['quantity'];
        }
    }

    $endprice = 1500;
    foreach ($aggregated_cart as $cartItem){
        global $db; 

        $id = $cartItem['id'];
        $sql = "SELECT * FROM items WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        $actualprice = 0;
        $actualprice += $item['price'] * $cartItem['quantity'];
        $endprice = $endprice - $actualprice;
    }
}

header_html($aggregated_cart, "/");
?>

<div class="container error_container">
<section class="not-found-section">
    <div>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
            <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
            <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
        </svg>
        <h1>404</h1>
        <p>Tady dávaj lišky dobrou noc 🦊</p>
        <p>Zkuste se vrátit na hlavní stránku nebo nás kontaktujte.</p>
        <a href="/">Vrátit se</a>
    </div>
</section>
    <?php before_footer_html(); ?>
    </div>
</div>
<?php footer_html(); ?>
<script src="/public/js/cart.js"></script>
</body>
</html>