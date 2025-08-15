<?php

define('APP_ACCESS', true);

session_start();

include "../gopay_config.php";
include "../lib/analytics.php";
include "../config.php";
include "../lib/function_action.php";

use GoPay\Api;
use GoPay\Definition\Payment\Currency;
use GoPay\Definition\Payment\PaymentInstrument;
use GoPay\Definition\Payment\BankSwiftCode;

if(!validateSessionUserInfo() || !validateSessionCart() || !isset($_SESSION['bought_items'])){
    header('location: ../cart.php');
    exit();
}

$analytics_script = '';
if (validateSessionCart()) {
    $aggregated_cart = aggregateCart();
    $total_value = calculateTotalPrice($aggregated_cart, $pdo);
    $cart_items = $_SESSION['cart'];
    
    $analytics_script = "<script>
    if (localStorage.getItem('cookie_consent') === 'accepted') {
        trackBeginCheckout(" . json_encode($cart_items) . ", " . $total_value . ");
    }
    </script>";
}

if(isset($_SESSION['user_info']) and isset($_SESSION['bought_items'])){
    if($_SESSION['user_info']['payment_shortcode'] == 'card'){
        $defaultPaymentInstrument = PaymentInstrument::PAYMENT_CARD;
    }else{
        $defaultPaymentInstrument = PaymentInstrument::BANK_ACCOUNT;
    }    
    
    $totalPrice = $_SESSION['user_info']['total_price'] + $_SESSION['user_info']['shipping_price'];
    
    $bought_items = [];
    foreach ($_SESSION['bought_items'] as $item) {
        $bought_items[] = [
            'type' => 'ITEM',
            'name' => $item['name'],
            'amount' => $item['price']
        ];	
    }

    $fullPayment = [
        'amount' => $totalPrice * 100 + 4,
        'currency' => Currency::CZECH_CROWNS,
        'lang' => 'CS',
        'order_number' => $_SESSION['user_info']['order_number'],
        'order_description' => 'Objednávka č. ' . $_SESSION['user_info']['order_number'],
        'payer' => [
            'allowed_payment_instruments' => [
                PaymentInstrument::PAYMENT_CARD,
                PaymentInstrument::BANK_ACCOUNT,
                PaymentInstrument::GPAY,
                PaymentInstrument::APPLE_PAY
            ],
            'default_payment_instrument' => $defaultPaymentInstrument,
            'allowed_swifts' => [
                BankSwiftCode::CESKA_SPORITELNA,
                BankSwiftCode::CSOB,
                BankSwiftCode::FIO_BANKA,
                BankSwiftCode::MONETA_MONEY_BANK,
                BankSwiftCode::AIRBANK
            ],
            'default_swift' => BankSwiftCode::CESKA_SPORITELNA,
            'contact' => [
                'first_name' => $_SESSION['user_info']['name'],
                'last_name' => $_SESSION['user_info']['surname'],
                'email' => $_SESSION['user_info']['email'],
                'phone_number' => '+420'.$_SESSION['user_info']['phone'],
                'city' => $_SESSION['user_info']['city'],
                'street' => $_SESSION['user_info']['street'],
                'postal_code' => $_SESSION['user_info']['zipcode'],
                'country_code' => 'CZE'
            ]
        ],
        'callback' =>  [
            'return_url' =>  'https://touchthemagic.com/action/thankyou.php',
            'notification_url' =>  'https://touchthemagic.com/payment/notify.php'
        ],
        'items' => $bought_items,
    ];

    $response = $gopay->createPayment($fullPayment);

    if ($response->hasSucceed()) {
        $paymentId = $response->json['id'];
        $gatewayUrl = $response->json['gw_url'];
        
        $_SESSION['gopay_payment_id'] = $paymentId;
        
        header("Location: " . $gatewayUrl);
        exit();
    } else {
        $error_message = "Chyba při vytváření platby: " . (isset($response->json['error']) ? $response->json['error'] : 'Neznámá chyba');
    }
}
?>