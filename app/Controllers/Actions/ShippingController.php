<?php

namespace App\Controllers\Actions;

use App\Controllers\BaseController;
use App\Services\CartService;
use App\Interfaces\CartServiceInterface;

class ShippingController extends BaseController
{
    private CartServiceInterface $cartService;

    public function __construct(CartServiceInterface $cartService = null)
    {
        parent::__construct();
        $this->cartService = $cartService ?? new CartService();
    }

    public function shippingAndPayment(): void
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $cartData = '';
        if (isset($_SESSION['cartjson'])) {
            $cartData = json_decode($_SESSION['cartjson'], true);
        } else {
            header("location: /");
            exit();
        }

        $aggregated_cart = $this->cartService->aggregateCart();

        $shipping_method = "";
        $payment_method = "";
        $payment_price = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $shipping_method = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : null;
            $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;
            $zasilkovna_branch = isset($_POST['zasilkovna_branch']) ? $_POST['zasilkovna_branch'] : null;
            $zasilkovna_branch_name = htmlspecialchars($_POST['zasilkovna_branch_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $_SESSION['shipping_method'] = $shipping_method;
            $_SESSION['payment_method'] = $payment_method;
            $_SESSION['zasilkovna_branch'] = $zasilkovna_branch;
            $_SESSION['zasilkovna_branch_name'] = $zasilkovna_branch_name;

            $payment_data = $this->processPaymentMethod($payment_method);
            $shipping_price = $this->getShippingCost(isset($_SESSION['freeshipping']) && $_SESSION['freeshipping']);
            $_SESSION['methodData'] = [
                'shipping' => [
                    'name' => 'Zásilkovna',
                    'price' => $shipping_price,
                    'branch_address' => $zasilkovna_branch_name,
                ],
                'payment' => $payment_data
            ];
            header('location: /action/billing-and-address');
            exit();
        }
        
        if ($_SESSION['cart']) {
            $this->renderShippingPage($aggregated_cart, $cartData);
        } else {
            header("location: /shop/cart");
            exit();
        }
    }

    private function renderShippingPage($aggregated_cart, $cartData): void
    {
        header_html($aggregated_cart, "/action/shipping-and-payment", 0);

        echo '<div class="container">
        <section class="products-section">
        <h1 class="my-cart">Doprava a platba</h1>
            <div class="info-cart">
                <div>
                    <p class="p-cart">1</p>
                    <p class="p-cart-text">Nákupní košík</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                </svg>
                <div>
                    <p class="p-cart p-cart-color">2</p>
                    <p class="p-cart-text">Doprava a platba</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                </svg>
                <div>
                    <p class="p-cart">3</p>
                    <p class="p-cart-text">Dodací údaje</p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                </svg>
                <div>
                    <p class="p-cart">4</p>
                    <p class="p-cart-text">Souhrn objednávky</p>
                </div>
            </div>
            <div>
                <h2 class="shipping-h2">Souhrn objednávky</h2>
                <div class="section-items">';
        
        foreach ($cartData['items'] as $item) {
            echo '<div class="item">
                    <p>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</p>
                    <p>' . htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') . 'ks</p>
                    <p>' . htmlspecialchars($item['total_price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>
                  </div>';
        }
        
        echo '</div>
            </div>
            <form action="" method="POST">
        <h2 class="shipping-h2">Způsob dopravy</h2>
        <div class="radio-group-shipping" id="zasilkovna-container" onclick="selectRadio(\'zasilkovna\')">
            <div class="scale-wrapper">
                <input type="radio" id="zasilkovna" name="shipping_method" value="zasilkovna" data-price="79" required>
                <label for="zasilkovna" class="label-align">Zásilkovna
                    <p id="select-branch-p">Kliknutím vyberte pobočku</p>
                </label>
                <input type="hidden" id="zasilkovna_branch" name="zasilkovna_branch">
                <input type="hidden" id="zasilkovna_branch_name" name="zasilkovna_branch_name">
                <div id="branch-info" class="branch-info">
                    <p id="branch-name">Vybraná pobočka:</p>
                    <button type="button" id="change-branch-btn" style="display:none;">Změnit výběr pobočky</button>
                </div>
                <img src="https://cdn.myshoptet.com/shipper/logistics-packeta/logistics-packeta-pickup/cs-68652d612c73c.svg" class="logo" alt="Zásilkovna logo">';
        
        if (isset($_SESSION['freeshipping']) && $_SESSION['freeshipping'] == true) {
            echo '<p class="shipping-price">Zdarma</p>';
        } else {
            echo '<p class="shipping-price">79 Kč</p>';
        }
        
        echo '</div>
        </div>

        <h2 class="shipping-h2">Způsob platby</h2>
        <div class="radio-group-pay" onclick="selectRadio(\'card\')">
            <input type="radio" id="card" name="payment_method" value="card" data-price="0" required>
            <label for="card" class="online-payment">Platba kartou</label>
            <img src="https://cdn.myshoptet.com/usr/www.epipi.cz/user/system/2e1bddbf_CARD_ALL.png" class="logo" alt="Platba kartou logo">
            <p class="shipping-price">Zdarma</p>
        </div>
        <div class="radio-group-pay" onclick="selectRadio(\'sepa\')">
            <input type="radio" id="sepa" name="payment_method" value="sepa" data-price="0" required>
            <label for="sepa" class="online-payment">On-line bankovní převod</label>
            <img src="https://cdn.myshoptet.com/usr/www.epipi.cz/user/system/b33b8558_BANK_ALL.png" class="logo" alt="On-line bankovní převod logo">
            <p class="shipping-price">Zdarma</p>
        </div>
        <div class="radio-group-pay" onclick="selectRadio(\'cod\')">
            <input type="radio" id="cod" name="payment_method" value="cod" data-price="30" required>
            <label for="cod">Dobírka</label>
            <p class="shipping-price">45 Kč</p>
        </div>
        <h2 class="final-price" id="final-price"></h2>
        <div class="cart-navigation">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg>
                <a href="/shop/cart">Zpět do košíku</a>
            </div>
            <div class="cart-navigation-div">
                <button class="submit-shipping" type="submit">Dodací údaje</button>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/>
                </svg>
            </div>
        </div>
    </form>
        </section>
    </div>';
    
        footer_html();
        echo '<script src="https://widget.packeta.com/v6/www/js/library.js" defer></script>
    <script src="/js/radiobutton.js"></script>
    <script src="/js/cart.js"></script>
    <script src="/js/zasilkovna.js"></script>
    <script src="/js/cod.js"></script>
    <script>
        document.getElementById("search-remove")?.remove();
        document.getElementById("left-menu")?.remove();
    </script>
    </body>
    </html>';
    }

    private function processPaymentMethod($payment_method)
    {
        switch ($payment_method) {
            case 'card':
                return [
                    'name' => 'Platba kartou',
                    'price' => 0,
                    'type' => 'card'
                ];
            case 'sepa':
                return [
                    'name' => 'On-line bankovní převod',
                    'price' => 0,
                    'type' => 'sepa'
                ];
            case 'cod':
                return [
                    'name' => 'Dobírka',
                    'price' => 45,
                    'type' => 'cod'
                ];
            default:
                return [
                    'name' => 'Neznámý způsob platby',
                    'price' => 0,
                    'type' => 'unknown'
                ];
        }
    }

    private function getShippingCost($isFreeShipping)
    {
        return $isFreeShipping ? 0 : 79;
    }
}