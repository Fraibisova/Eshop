<?php
if (!defined('APP_ACCESS')) {
    http_response_code(403);
    header('location: ../template/not-found-page.php');
    die();
}

function trackProductView($product) {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'view_item', {
                currency: 'CZK',
                value: {$product['price']},
                items: [{
                    item_id: '{$product['id']}',
                    item_name: '" . addslashes($product['name']) . "',
                    category: '" . addslashes($product['category'] ?? 'Nezařazeno') . "',
                    quantity: 1,
                    price: {$product['price']}
                }]
            });
        }
    </script>";
}


function trackAddToCart($product, $quantity = 1) {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'add_to_cart', {
                currency: 'CZK',
                value: " . ($product['price'] * $quantity) . ",
                items: [{
                    item_id: '{$product['id']}',
                    item_name: '" . addslashes($product['name']) . "',
                    category: '" . addslashes($product['category'] ?? 'Nezařazeno') . "',
                    quantity: {$quantity},
                    price: {$product['price']}
                }]
            });
        }
    </script>";
}


function trackBeginCheckout($cart_items, $total_value) {
    $items = [];
    foreach ($cart_items as $item) {
        $items[] = "{
            item_id: '{$item['id']}',
            item_name: '" . addslashes($item['name']) . "',
            category: '" . addslashes($item['category'] ?? 'Nezařazeno') . "',
            quantity: {$item['quantity']},
            price: {$item['price']}
        }";
    }
    
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'begin_checkout', {
                currency: 'CZK',
                value: {$total_value},
                items: [" . implode(',', $items) . "]
            });
        }
    </script>";
}


function trackPurchase($order_number, $total_value, $bought_items, $shipping_cost = 0, $tax = 0) {
    $items = [];
    foreach ($bought_items as $item) {
        $items[] = "{
            item_id: '{$item['id']}',
            item_name: '" . addslashes($item['name']) . "',
            category: '" . addslashes($item['category'] ?? 'Nezařazeno') . "',
            quantity: 1,
            price: {$item['price']}
        }";
    }
    
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'purchase', {
                transaction_id: '{$order_number}',
                value: {$total_value},
                currency: 'CZK',
                shipping: {$shipping_cost},
                tax: {$tax},
                items: [" . implode(',', $items) . "]
            });
        }
    </script>";
}


function trackSearch($search_term) {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'search', {
                search_term: '" . addslashes($search_term) . "'
            });
        }
    </script>";
}


function trackRemoveFromCart($product, $quantity = 1) {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'remove_from_cart', {
                currency: 'CZK',
                value: " . ($product['price'] * $quantity) . ",
                items: [{
                    item_id: '{$product['id']}',
                    item_name: '" . addslashes($product['name']) . "',
                    category: '" . addslashes($product['category'] ?? 'Nezařazeno') . "',
                    quantity: {$quantity},
                    price: {$product['price']}
                }]
            });
        }
    </script>";
}


function trackViewItemList($items, $list_name = 'Produkty') {
    $js_items = [];
    foreach ($items as $index => $item) {
        $js_items[] = "{
            item_id: '{$item['id']}',
            item_name: '" . addslashes($item['name']) . "',
            category: '" . addslashes($item['category'] ?? 'Nezařazeno') . "',
            index: " . ($index + 1) . ",
            price: {$item['price']}
        }";
    }
    
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'view_item_list', {
                item_list_name: '" . addslashes($list_name) . "',
                items: [" . implode(',', $js_items) . "]
            });
        }
    </script>";
}


function trackSelectItem($product, $list_name = 'Produkty', $index = 1) {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'select_item', {
                item_list_name: '" . addslashes($list_name) . "',
                items: [{
                    item_id: '{$product['id']}',
                    item_name: '" . addslashes($product['name']) . "',
                    category: '" . addslashes($product['category'] ?? 'Nezařazeno') . "',
                    index: {$index},
                    price: {$product['price']}
                }]
            });
        }
    </script>";
}


function trackSignUp($method = 'email') {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'sign_up', {
                method: '" . addslashes($method) . "'
            });
        }
    </script>";
}


function trackLogin($method = 'email') {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'login', {
                method: '" . addslashes($method) . "'
            });
        }
    </script>";
}


function trackShare($content_type = 'product', $item_id = '', $method = 'social') {
    echo "<script>
        if (localStorage.getItem('cookie_consent') === 'accepted') {
            gtag('event', 'share', {
                method: '" . addslashes($method) . "',
                content_type: '" . addslashes($content_type) . "',
                item_id: '" . addslashes($item_id) . "'
            });
        }
    </script>";
}

?>