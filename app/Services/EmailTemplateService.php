<?php

namespace App\Services;

class EmailTemplateService
{
    public function getOrderConfirmationTemplate(array $userInfo, array $boughtItems, float $totalPrice, int $orderNumber): string
    {
        $emailTemplate = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Potvrzení objednávky</title>
        <style type="text/css">
            /* Reset styles */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            
            /* Main styles */
            body {
                font-family: Arial, sans-serif !important;
                background-color: #ffffff;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-width: 100% !important;
            }
            
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
            }
            
            .header {
                background-color: #6f42c1;
                padding: 20px;
                text-align: center;
                border-top: 2px solid #dee2e6;
                border-bottom: 2px solid #dee2e6;
            }
            
            .header h1 {
                color: #ffffff;
                font-size: 18px;
                margin: 0;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 3px;
            }
            
            .status-section {
                background-color: #6f42c1;
                color: #ffffff;
                text-align: center;
                padding: 15px;
                margin: 20px 0;
            }
            
            .status-section h2 {
                font-size: 15px;
                text-transform: uppercase;
                font-weight: 500;
                letter-spacing: 4px;
                margin: 0;
            }
            
            .status-icon {
                font-size: 50px;
                color: #6f42c1;
                text-align: center;
                padding: 20px 0;
            }
            
            .content {
                padding: 30px 20px;
                background-color: #f8f9fa;
            }
            
            .order-info {
                background-color: #e5d7ee;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 20px;
            }
            
            .order-info h3 {
                color: #251d3c;
                margin-top: 0;
                font-size: 18px;
                font-weight: bold;
            }
            
            .order-info p {
                margin: 12px 0;
                font-size: 14px;
                line-height: 1.6;
                color: #333333;
            }
            
            .order-number {
                background-color: #251d3c;
                color: #ffffff;
                padding: 8px 12px;
                border-radius: 4px;
                font-weight: bold;
                display: inline-block;
            }
            
            .customer-section {
                background-color: #ffffff;
                border-left: 4px solid #6f42c1;
                padding: 15px;
                margin: 15px 0;
                border-radius: 0 5px 5px 0;
            }
            
            .customer-section strong {
                color: #251d3c;
            }
            
            .products-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                background-color: #ffffff;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .products-table-header {
                background-color: #251d3c;
                color: #ffffff;
                padding: 12px 15px;
                font-weight: bold;
                font-size: 14px;
            }
            
            .product-row {
                padding: 12px 15px;
                border-bottom: 1px solid #f8f9fa;
                display: table-row;
            }
            
            .product-row:last-child {
                border-bottom: none;
            }
            
            .product-cell-left {
                display: table-cell;
                vertical-align: top;
                width: 70%;
            }
            
            .product-cell-right {
                display: table-cell;
                vertical-align: top;
                text-align: right;
                width: 30%;
            }
            
            .product-name {
                font-weight: bold;
                color: #251d3c;
                font-size: 14px;
                margin-bottom: 5px;
            }
            
            .product-details {
                color: #6c757d;
                font-size: 13px;
            }
            
            .product-price {
                font-weight: bold;
                color: #251d3c;
                font-size: 14px;
            }
            
            .total-section {
                background-color: #6f42c1;
                color: #ffffff;
                padding: 15px;
                text-align: right;
                font-weight: bold;
                font-size: 16px;
            }
            
            .next-steps {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .next-steps h4 {
                color: #856404;
                margin-top: 0;
                font-size: 16px;
                font-weight: bold;
            }
            
            .next-steps p {
                color: #856404;
                margin: 8px 0;
                font-size: 14px;
            }
            
            .social-section {
                text-align: center;
                padding: 30px 20px;
            }
            
            .social-section h3 {
                color: #251d3c;
                margin-bottom: 20px;
                font-size: 18px;
            }
            
            .social-links {
                text-align: center;
            }
            
            .social-links img {
                width: 40px;
                height: 40px;
                margin: 0 8px;
                border-radius: 50%;
            }
            
            .footer {
                background-color: #e5d7ee;
                padding: 25px 20px;
                text-align: center;
                font-size: 13px;
            }
            
            .footer a, .footer p {
                color: #251d3c;
                text-decoration: none;
                margin: 0 8px;
                display: inline-block;
            }
            
            .footer a:hover {
                color: #6f42c1;
            }
            
            .footer p {
                display: inline;
            }
            
            /* Responsive */
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                .order-info {
                    padding: 15px !important;
                }
                .products-table {
                    font-size: 12px !important;
                }
            }
        </style>
    </head>
    <body>
        <table cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa;">
            <tr>
                <td align="center">
                    <div class="email-container">
                        <!-- Logo -->
                        <div style="text-align: center; padding: 20px;">
                            <img alt="Touch The Magic logo" src="https://touchthemagic.com/web_images/logo/darklogo.png" style="max-width: 200px; height: auto;">
                        </div>
                        
                        <!-- Header -->
                        <div class="header">
                            <h1>POTVRZENÍ OBJEDNÁVKY</h1>
                        </div>
                        
                        <!-- Status -->
                        <div class="status-section">
                            <h2>Máte objednáno</h2>
                        </div>
                        
                        <div class="status-icon">
                            📋
                        </div>
                        
                        <!-- Content -->
                        <div class="content">
                            <div class="order-info">
                                <h3>Děkujeme za vaši objednávku!</h3>
                                <p>Vaše objednávka číslo <span class="order-number">{{order_number}}</span> byla úspěšně vytvořena a přijata ke zpracování.</p>
                                
                                <div class="customer-section">
                                    <p><strong>Datum objednávky:</strong> {{order_date}}</p>
                                    <p><strong>Způsob platby:</strong> {{payment_method}}</p>
                                    <p><strong>Způsob doručení:</strong> {{delivery_method}}</p>
                                </div>

                                <div class="customer-section">
                                    <p><strong>Dodací adresa:</strong><br>
                                    {{delivery_name}}<br>
                                    {{delivery_address}}<br>
                                    {{delivery_city}}, {{delivery_zip}}</p>
                                </div>

                                <!-- Products -->
                                <div style="margin: 20px 0;">
                                    <div class="products-table-header">
                                        Objednané produkty
                                    </div>
                                    <div style="background-color: #ffffff; border: 1px solid #dee2e6; border-top: none;">
                                        {{items_rows}}
                                        <div class="total-section">
                                            Celkem k úhradě: {{order_total}} Kč
                                        </div>
                                    </div>
                                </div>

                                <div class="next-steps">
                                    <h4>Další kroky:</h4>
                                    <p>1. Dokončete platbu</p>
                                    <p>2. Po zaplacení vám zašleme potvrzení o přijetí platby</p>
                                    <p>3. Objednávku připravíme a zašleme vám informace o expedici</p>
                                    <p>4. Zásilka bude doručena na uvedenou adresu</p>
                                </div>
                                
                                <p>Stav objednávky můžete sledovat ve vašem účtu. V případě jakýchkoli dotazů nás neváhejte kontaktovat.</p>
                            </div>
                        </div>
                        
                        <!-- Social Section -->
                        <div class="social-section">
                            <h3>Zůstaňte s námi v kontaktu</h3>
                            <div class="social-links">
                                <a href=""><img alt="instagram" src="https://touchthemagic.com/web_images/logo/instagram.png"></a>
                                <a href=""><img alt="facebook" src="https://touchthemagic.com/web_images/logo/facebook.png"></a>
                                <a href=""><img alt="tiktok" src="https://touchthemagic.com/web_images/logo/tiktok.png"></a>
                                <a href=""><img alt="pinterest" src="https://touchthemagic.com/web_images/logo/pinterest.png"></a>
                                <a href=""><img alt="youtube" src="https://touchthemagic.com/web_images/logo/youtube.png"></a>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="footer">
                            <p>Beáta Fraibišová</p>
                            <a href="tel:+420747473938">+420 747 473 938</a>
                            <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                            <a href="https://touchthemagic.com/page.php?page=obchodni-podminky">Podmínky</a>
                            <a href="https://touchthemagic.com/page.php?page=kontakty">Kontakt</a>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>';

        $emailTemplate = str_replace('{{order_number}}', $orderNumber, $emailTemplate);
        $emailTemplate = str_replace('{{order_date}}', date('d.m.Y H:i'), $emailTemplate);
        $emailTemplate = str_replace('{{payment_method}}', $userInfo['payment_method'], $emailTemplate);
        $emailTemplate = str_replace('{{delivery_method}}', 'Zásilkovna', $emailTemplate);
        $emailTemplate = str_replace('{{order_total}}', number_format($totalPrice, 0, ',', ' '), $emailTemplate);
        
        $emailTemplate = str_replace('{{delivery_name}}', $userInfo['name'] . ' ' . $userInfo['surname'], $emailTemplate);
        $emailTemplate = str_replace('{{delivery_address}}', $userInfo['street'] . ' ' . $userInfo['housenumber'], $emailTemplate);
        $emailTemplate = str_replace('{{delivery_city}}', $userInfo['city'], $emailTemplate);
        $emailTemplate = str_replace('{{delivery_zip}}', $userInfo['zipcode'], $emailTemplate);
        
        $itemsRows = '';
        $itemCounts = [];
        
        foreach ($boughtItems as $item) {
            $itemKey = $item['name'] . '_' . $item['price'];
            if (!isset($itemCounts[$itemKey])) {
                $itemCounts[$itemKey] = [
                    'item' => $item,
                    'quantity' => 1,
                    'total' => $item['price']
                ];
            } else {
                $itemCounts[$itemKey]['quantity']++;
                $itemCounts[$itemKey]['total'] += $item['price'];
            }
        }
        
        foreach ($itemCounts as $itemData) {
            $item = $itemData['item'];
            $quantity = $itemData['quantity'];
            $total = $itemData['total'];
            
            $itemsRows .= '
            <div class="product-row">
                <div class="product-cell-left">
                    <div class="product-name">' . htmlspecialchars($item['name']) . '</div>
                    <div class="product-details">' . $quantity . '× ' . number_format($item['price'], 0, ',', ' ') . ' Kč</div>
                </div>
                <div class="product-cell-right">
                    <div class="product-price">' . number_format($total, 0, ',', ' ') . ' Kč</div>
                </div>
            </div>';
        }
        
        $emailTemplate = str_replace('{{items_rows}}', $itemsRows, $emailTemplate);
        
        return $emailTemplate;
    }

    public function getPaymentConfirmationTemplate(int $order_number, string $current_date, string $total_amount, string $payment_method, array $orderItems = []): string
    {
        $items_html = '';
        
        if (!empty($orderItems)) {
            $items_html = '<h3>Přehled objednávky:</h3><div style="margin: 20px 0;">';
            foreach ($orderItems as $item) {
                $items_html .= '
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                        <span style="flex: 1;">' . htmlspecialchars($item['name']) . '</span>
                        <span style="margin: 0 10px;">' . $item['count'] . 'ks</span>
                        <span style="font-weight: bold;">' . number_format($item['total_price'], 0, ',', ' ') . ' Kč</span>
                    </div>';
            }
            $items_html .= '</div>';
        }
        
        return <<<END
<!DOCTYPE html>
<html lang="cs">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table, td {
                mso-table-lspace: 0pt;
                mso-table-rspace: 0pt;
            }
            img {
                -ms-interpolation-mode: bicubic;
                border: 0;
                height: auto;
                line-height: 100%;
                outline: none;
                text-decoration: none;
            }
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                padding: 30px 20px;
                border-radius: 8px 8px 0 0;
            }
            .content {
                padding: 30px;
            }
            .success-icon {
                font-size: 48px;
                margin-bottom: 20px;
            }
            .order-details {
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <div class="success-icon">✅</div>
                <h1>Platba úspěšně dokončena!</h1>
                <p>Děkujeme za váš nákup v Touch The Magic</p>
            </div>
            
            <div class="content">
                <p>Vaše platba byla úspěšně zpracována a objednávka je připravena k expedici.</p>
                
                <div class="order-details">
                    <h3>Detaily objednávky:</h3>
                    <p><strong>Číslo objednávky:</strong> $order_number</p>
                    <p><strong>Datum:</strong> $current_date</p>
                    <p><strong>Částka:</strong> $total_amount Kč</p>
                    <p><strong>Způsob platby:</strong> $payment_method</p>
                </div>
                
                $items_html
                
                <p>Vaší objednávku připravíme k odeslání co nejdříve. O expedici vás budeme informovat emailem.</p>
                
                <p>Máte-li jakékoliv dotazy, neváhejte nás kontaktovat na <a href="mailto:info@touchthemagic.com">info@touchthemagic.com</a></p>
                
                <p>Děkujeme za důvěru!<br>
                Tým Touch The Magic</p>
            </div>
        </div>
    </body>
</html>
END;
    }

    public function getShippingEmailTemplate(int $order_number, string $tracking_number = "CZ123456789", string $carrier_name = "Zásilkovna", string $delivery_name = "", string $delivery_address = "", string $delivery_city = "", string $delivery_zip = "", ?\PDO $pdo = null): string
    {
        $shipping_date = date('d.m.Y');
        $estimated_delivery = date('d.m.Y', strtotime('+3 days'));
        $tracking_link = "https://tracking.packeta.com/en";
        
        $items_html = '';
        if ($pdo) {
            $orderService = new OrderService();
            $orderItems = $orderService->getOrderItemsWithVariants($order_number);
            if (!empty($orderItems)) {
                $items_html = '<h3>Odeslané produkty:</h3><div style="margin: 20px 0;">';
                foreach ($orderItems as $item) {
                    $items_html .= '
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                            <span style="flex: 1;">' . htmlspecialchars($item['name']) . '</span>
                            <span style="margin: 0 10px;">' . $item['count'] . 'ks</span>
                            <span style="font-weight: bold;">' . number_format($item['total_price'], 0, ',', ' ') . ' Kč</span>
                        </div>';
                }
                $items_html .= '</div>';
            }
        }
        
        return '
        <!DOCTYPE html>
        <html lang="cs">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style type="text/css">
                    body{
                        font-family: Arial, sans-serif !important;
                        background-color: white;
                        margin: 0;
                        padding: 0;
                        -webkit-text-size-adjust: 100%;
                        -ms-text-size-adjust: 100%;
                    }
                    .main {
                        width: 100% !important;
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: white;
                    }
                    .row{
                        width: 100%;
                        text-align: center;
                    }
                    .title{
                        border-top: 2px solid lightgray;
                        border-bottom: 2px solid lightgray;
                        margin: 20px 0;
                        text-transform: uppercase;
                        font-size: 12px;
                        font-weight: 700;
                        padding: 15px 0;
                    }
                    h1{
                        margin: 0;
                        font-size: 16px;
                    }
                    h2{
                        font-size: 16px;
                        text-transform: uppercase;
                        background-color: #17a2b8;
                        color: white !important;
                        font-weight: 500;
                        padding: 15px 30px;
                        letter-spacing: 2px;
                        margin: 40px 0 0 0;
                        display: inline-block;
                    }
                    .status-icon{
                        font-size: 48px;
                        color: #17a2b8;
                        margin: 30px 0;
                        line-height: 1;
                    }
                    .order-info{
                        background: #f8f9fa;
                        margin: 30px 20px;
                        padding: 25px;
                        text-align: left;
                        border-radius: 8px;
                        border: 1px solid #e9ecef;
                    }
                    .order-info h3{
                        color: #251d3c;
                        margin-top: 0;
                        font-size: 18px;
                        font-weight: 600;
                    }
                    .order-info p{
                        margin: 15px 0;
                        font-size: 14px;
                        line-height: 1.6;
                        color: #333;
                    }
                    .order-number{
                        background-color: #251d3c;
                        color: white !important;
                        padding: 6px 12px;
                        border-radius: 4px;
                        font-weight: 600;
                        text-decoration: none;
                    }
                    .tracking-number{
                        background-color: #17a2b8;
                        color: white !important;
                        padding: 8px 12px;
                        border-radius: 4px;
                        font-weight: 600;
                        font-family: monospace;
                        font-size: 16px;
                        display: inline-block;
                        margin: 5px 0;
                    }
                    .shipping-info{
                        background: white;
                        border-left: 4px solid #17a2b8;
                        padding: 20px;
                        margin: 20px 0;
                        border-radius: 0 4px 4px 0;
                    }
                    .shipping-info strong{
                        color: #251d3c;
                    }
                    .link{
                        text-align: center;
                        margin: 30px 0;
                    }
                    .link a{
                        background-color: #251d3c !important;
                        color: white !important;
                        font-weight: 600;
                        text-transform: uppercase;
                        font-size: 14px;
                        text-decoration: none;
                        padding: 15px 40px;
                        display: inline-block;
                        border-radius: 4px;
                        border: none;
                    }
                    .footer{
                        width: 100%;
                        margin: 40px 0 0 0;
                        background-color: #f8f9fa;
                        border-top: 1px solid #e9ecef;
                    }
                    .inside_footer{
                        padding: 25px 20px;
                        text-align: center;
                    }
                    .inside_footer a, .inside_footer p{
                        color: #251d3c !important;
                        text-decoration: none;
                        font-size: 13px;
                        margin: 0 8px;
                        display: inline-block;
                    }
                    .inside_footer p{
                        font-weight: 600;
                    }
                    /* Gmail specific fixes */
                    u + .body .main { width: 600px !important; }
                    @media only screen and (max-width: 600px) {
                        .main { width: 100% !important; }
                        .order-info { margin: 20px 10px !important; padding: 20px !important; }
                        h2 { padding: 12px 20px !important; font-size: 14px !important; }
                    }
                </style>
            </head>
            <body class="body">
                <div class="main">
                    <div class="row logo_main">
                        <img alt="Touch The Magic Logo" src="https://touchthemagic.com/web_images/logo/darklogo.png" style="max-width: 200px; height: auto; margin: 20px 0;">
                    </div>
                    <div class="row title">
                        <h1>EXPEDICE OBJEDNÁVKY</h1>
                    </div>
                    <div class="row">
                        <h2>Zabaleno a odesláno</h2>
                    </div>
                    <div class="row">
                        <div class="status-icon">📦</div>
                    </div>
                    <div class="order-info">
                        <h3>Vaše objednávka je na cestě!</h3>
                        <p>Objednávka číslo <span class="order-number">' . htmlspecialchars($order_number) . '</span> byla úspěšně zabalena a předána dopravci k doručení.</p>
                        
                        <div class="shipping-info">
                            <p><strong>Sledovací číslo:</strong><br><span class="tracking-number">' . htmlspecialchars($tracking_number) . '</span></p>
                            <p><strong>Dopravce:</strong> ' . htmlspecialchars($carrier_name) . '</p>
                            <p><strong>Datum odeslání:</strong> ' . $shipping_date . '</p>
                            <p><strong>Předpokládané doručení:</strong> ' . $estimated_delivery . '</p>
                        </div>
                        
                        <p><strong>Adresa doručení:</strong><br>
                        ' . htmlspecialchars($delivery_name) . '<br>
                        ' . htmlspecialchars($delivery_address) . '<br>
                        ' . htmlspecialchars($delivery_city) . ', ' . htmlspecialchars($delivery_zip) . '</p>
                        
                        <p>Zásilku můžete sledovat pomocí sledovacího čísla na webu dopravce. V případě jakýchkoli problémů s doručením nás neváhejte kontaktovat.</p>
                    </div>
                    
                    ' . $items_html . '
                    
                    <div class="row link">
                        <a href="' . $tracking_link . '">Sledovat zásilku</a>
                    </div>
                    <div class="footer">
                        <div class="inside_footer">
                            <p>Beáta Fraibišová</p>
                            <a href="tel:+420747473938">+420 747 473 938</a>
                            <a href="mailto:info@touchthemagic.cz">info@touchthemagic.cz</a>
                        </div>
                    </div>
                </div>
            </body>
        </html>';
    }
}