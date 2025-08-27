<?php

namespace App\Controllers\Actions;

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ValidationService;
use App\Services\TemplateService;
use Exception;

class CheckoutController
{
    private CartService $cartService;
    private OrderService $orderService;
    private ValidationService $validationService;
    private TemplateService $templateService;

    public function __construct()
    {
        $this->cartService = new CartService();
        $this->orderService = new OrderService();
        $this->validationService = new ValidationService();
        $this->templateService = new TemplateService();
    }

    public function checkout(): void
    {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            header("Location: /shop/cart");
            exit();
        }

        $errors = [];
        $formData = [];

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $formData = [
                'name' => htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'),
                'surname' => htmlspecialchars($_POST['surname'] ?? '', ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'),
                'phone' => htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'),
                'street' => htmlspecialchars($_POST['street'] ?? '', ENT_QUOTES, 'UTF-8'),
                'housenumber' => htmlspecialchars($_POST['housenumber'] ?? '', ENT_QUOTES, 'UTF-8'),
                'city' => htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES, 'UTF-8'),
                'zipcode' => htmlspecialchars($_POST['zipcode'] ?? '', ENT_QUOTES, 'UTF-8'),
                'country' => htmlspecialchars($_POST['country'] ?? 'Česká republika', ENT_QUOTES, 'UTF-8'),
                'shipping_method' => $_POST['shipping_method'] ?? '',
                'payment_method' => $_POST['payment_method'] ?? '',
                'terms' => isset($_POST['terms']) ? 1 : 0,
                'newsletter' => isset($_POST['newsletter']) ? 1 : 0
            ];

            $requiredFields = ['name', 'surname', 'email', 'phone', 'street', 'housenumber', 'city', 'zipcode'];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field])) {
                    $errors[] = "Pole '$field' je povinné.";
                }
            }

            if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Neplatný formát e-mailu.";
            }

            if (empty($formData['shipping_method'])) {
                $errors[] = "Vyberte způsob dopravy.";
            }

            if (empty($formData['payment_method'])) {
                $errors[] = "Vyberte způsob platby.";
            }

            if (!$formData['terms']) {
                $errors[] = "Musíte souhlasit s obchodními podmínkami.";
            }

            if (empty($errors)) {
                try {
                    $_SESSION['user_info'] = $formData;
                    $_SESSION['user_info']['timestamp'] = date('Y-m-d H:i:s');
                    
                    header("Location: summary.php");
                    exit();
                } catch (Exception $e) {
                    $errors[] = "Chyba při zpracování objednávky: " . $e->getMessage();
                }
            }
        }

        $this->renderCheckoutForm($errors, $formData);
    }

    private function renderCheckoutForm(array $errors, array $formData): void
    {
        $aggregatedCart = $this->cartService->aggregateCart();
        $totalPrice = $this->cartService->calculateCartPrice($aggregatedCart);
        
        $this->templateService->renderHeader($aggregatedCart, "checkout.php");

        echo '<div class="container">
                <section class="checkout-section">
                    <h1>Dokončení objednávky</h1>';

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<p class="error">' . htmlspecialchars($error) . '</p>';
            }
        }

        echo '<div class="checkout-content">
                <div class="checkout-form">
                    <form method="post" class="order-form">
                        <h2>Dodací údaje</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Jméno *:</label>
                                <input type="text" id="name" name="name" value="' . htmlspecialchars($formData['name'] ?? '') . '" required>
                            </div>
                            <div class="form-group">
                                <label for="surname">Příjmení *:</label>
                                <input type="text" id="surname" name="surname" value="' . htmlspecialchars($formData['surname'] ?? '') . '" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *:</label>
                            <input type="email" id="email" name="email" value="' . htmlspecialchars($formData['email'] ?? '') . '" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Telefon *:</label>
                            <input type="tel" id="phone" name="phone" value="' . htmlspecialchars($formData['phone'] ?? '') . '" required>
                        </div>

                        <h3>Adresa</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="street">Ulice *:</label>
                                <input type="text" id="street" name="street" value="' . htmlspecialchars($formData['street'] ?? '') . '" required>
                            </div>
                            <div class="form-group">
                                <label for="housenumber">Číslo popisné *:</label>
                                <input type="text" id="housenumber" name="housenumber" value="' . htmlspecialchars($formData['housenumber'] ?? '') . '" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Město *:</label>
                                <input type="text" id="city" name="city" value="' . htmlspecialchars($formData['city'] ?? '') . '" required>
                            </div>
                            <div class="form-group">
                                <label for="zipcode">PSČ *:</label>
                                <input type="text" id="zipcode" name="zipcode" value="' . htmlspecialchars($formData['zipcode'] ?? '') . '" required>
                            </div>
                        </div>

                        <h3>Doprava a platba</h3>
                        <div class="shipping-methods">
                            <h4>Způsob dopravy:</h4>
                            <label><input type="radio" name="shipping_method" value="post" ' . (($formData['shipping_method'] ?? '') == 'post' ? 'checked' : '') . '> Česká pošta (60 Kč)</label>
                            <label><input type="radio" name="shipping_method" value="zasilkovna" ' . (($formData['shipping_method'] ?? '') == 'zasilkovna' ? 'checked' : '') . '> Zásilkovna (90 Kč)</label>
                        </div>

                        <div class="payment-methods">
                            <h4>Způsob platby:</h4>
                            <label><input type="radio" name="payment_method" value="card" ' . (($formData['payment_method'] ?? '') == 'card' ? 'checked' : '') . '> Platební karta</label>
                            <label><input type="radio" name="payment_method" value="bank_transfer" ' . (($formData['payment_method'] ?? '') == 'bank_transfer' ? 'checked' : '') . '> Bankovní převod</label>
                        </div>

                        <div class="form-checkboxes">
                            <label><input type="checkbox" name="terms" ' . (($formData['terms'] ?? 0) ? 'checked' : '') . ' required> Souhlasím s obchodními podmínkami *</label>
                            <label><input type="checkbox" name="newsletter" ' . (($formData['newsletter'] ?? 0) ? 'checked' : '') . '> Chci dostávat newsletter</label>
                        </div>

                        <button type="submit" class="btn-checkout">Pokračovat k souhrnu</button>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h2>Souhrn objednávky</h2>
                    <div class="cart-items">';

        foreach ($aggregatedCart as $item) {
            echo '<div class="cart-item">
                    <h4>' . htmlspecialchars($item['name']) . '</h4>
                    <p>Množství: ' . $item['quantity'] . '</p>
                    <p>Cena: ' . number_format($item['price'], 0, ',', ' ') . ' Kč</p>
                  </div>';
        }

        echo '    </div>
                    <div class="total">
                        <strong>Celkem: ' . number_format($totalPrice, 0, ',', ' ') . ' Kč</strong>
                    </div>
                </div>
              </div>
              </section>
              </div>';

        $this->templateService->renderFooter();
    }
}