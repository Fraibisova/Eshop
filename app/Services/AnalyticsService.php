<?php

namespace App\Services;

class AnalyticsService
{
    public function trackProductView(array $product): void
    {
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

    public function trackAddToCart(array $product, int $quantity = 1): void
    {
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

    public function trackBeginCheckout(array $cart_items, float $total_value): void
    {
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

    public function trackPurchase(string $order_number, float $total_value, array $bought_items, float $shipping_cost = 0, float $tax = 0): void
    {
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

    public function trackSearch(string $search_term): void
    {
        echo "<script>
            if (localStorage.getItem('cookie_consent') === 'accepted') {
                gtag('event', 'search', {
                    search_term: '" . addslashes($search_term) . "'
                });
            }
        </script>";
    }

    public function trackRemoveFromCart(array $product, int $quantity = 1): void
    {
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

    public function trackViewItemList(array $items, string $list_name = 'Produkty'): void
    {
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

    public function trackSelectItem(array $product, string $list_name = 'Produkty', int $index = 1): void
    {
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

    public function trackSignUp(string $method = 'email'): void
    {
        echo "<script>
            if (localStorage.getItem('cookie_consent') === 'accepted') {
                gtag('event', 'sign_up', {
                    method: '" . addslashes($method) . "'
                });
            }
        </script>";
    }

    public function trackLogin(string $method = 'email'): void
    {
        echo "<script>
            if (localStorage.getItem('cookie_consent') === 'accepted') {
                gtag('event', 'login', {
                    method: '" . addslashes($method) . "'
                });
            }
        </script>";
    }

    public function trackShare(string $content_type = 'product', string $item_id = '', string $method = 'social'): void
    {
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
}