<?php

    if (!defined('APP_ACCESS')) {
        http_response_code(403);
        header('location: /');
        exit();
    }

    if (!defined('APP_ACCESS')) {
        define('APP_ACCESS', true);
    }

    if (!function_exists('config')) {
        function config(string $key, mixed $default = null): mixed {
            return \App\Services\ConfigurationService::getInstance()->get($key, $default);
        }
    }

    function header_html($aggregated_cart, $location, $print = 1){
        $baseUrl = '';
        $googleAnalyticsId = config('analytics.google_id');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_id'])) {
            $remove_item_id = $_POST['remove_item_id'];
            unset($_SESSION['cart'][$remove_item_id]);
            header('Location: ' . $location);
            exit();
        }
        print('<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Touchthemagic – Dárky, které okouzlí</title>
    <meta name="description" content="Touchthemagic – magické dárky a dekorace pro radost. Vyberte originální ručně vyrobený dárek pro každou příležitost. Doprava zdarma od 1500 Kč.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="author" content="Beáta Fraibišová">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="/public/css/toggle_button.css">
    <link rel="stylesheet" href="/public/css/phones.css">
    <script src="/public/js/loading.js"></script>
    <script src="/public/js/nav.js"></script>
    <link rel="icon" href="/public/images/logo/lighticon.png" type="image/png" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=' . $googleAnalyticsId . '"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag("js", new Date());
        gtag("config", "' . $googleAnalyticsId . '", {
            cookie_flags: "secure;samesite=strict",
            anonymize_ip: true
        });
    </script>');
    renderAnalyticsScript();
    renderCookieBanner();
print('</head>
        <body>
    <div class="loader"></div>
    <nav class="navbar" id="navbar">
        <div class="nav-branding logo">
            <a href="/public/">
                <img src="/public/images/logo/darklogo.svg">
            </a>
        </div>
        <div id="form">
            <form action="/public/shop/search" method="get" autocomplete="off">
                <div class="search-container">');
                if (isset($_GET['query'])) {
                    print('<input type="text" name="query" class="search-input" value="'.$_GET['query'].'" required>');
                }else{
                    print('<input type="text" name="query" class="search-input" placeholder="Napište, co hledáte" required>');
                }
                print('<input type="submit" value="Hledat" class="search-button">
                </div>
            </form>
        </div>
        <div class="left" id="left-menu">');
            print('<ul class="nav-menu">');
            if(isset($_SESSION['user_id'])){
                print('
                <li>
                <a href="/account/profile" class="nav-link log" id="loggedBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                </svg>');
                print('<p class="log-text-logged" id="acc">Můj účet</p></a></li>');
            }else {
                print('<button class="nav-link log" id="loginBtn">
                <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                </svg>');
                print('<p class="log-text" id="acc">Přihlášení</p></button></li>');
            }
            print(get_section_content('mobile_nav'));
            print('<div id="loginModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <form id="loginForm" action="/public/auth/login" method="POST">
                        <h2>Přihlášení</h2>
                        <input type="text" id="email" name="email" class="log-input fo" placeholder="Email" autocomplete="off" required>
                        <input type="password" id="password" name="password" class="log-input fo" placeholder="Heslo" autocomplete="off" required>
                        <div class="login_div"><button type="submit">Přihlásit se</button></div>
                        <a href="/public/auth/forgot-password">Zapoměli jste heslo?</a>
                        <a href="/public/auth/register">Nemáte účet?</a>
                    </form>
                </div>
                <div id="ppl-parcelshop-map" data-lat="50" data-lng="15" datamode="static"></div>
            </div>
            <li><a href="" class="nav-link instagram">
            </a></li></ul>');
            print('<div class="search" id="search-remove"></div>');
                $endprice = 1500;
                if($print == 1){
                    print('<div class="cart-hover">
                    <a href="/public/shop/cart" class="nav-link cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart-fill" viewBox="0 0 16 16">
                            <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                        </svg>
                        <span class="cart-count">');
                    print( getCartItemCount());
                    print('</span>
                </a>');
                    print('<div class="cart-popup">
                        <h3>Můj košík</h3>');
                    foreach ($aggregated_cart as $cartKey => $cartItem):
                        $db = getDb(); 

                        $id = $cartItem['id'];
                        $is_variant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
                        
                        if ($is_variant) {
                            $display_name = $cartItem['name'];
                            $item_price = $cartItem['price'];
                            $variant_id = $cartItem['variant_id'];
                            $image = $cartItem['image'] ?? '';
                            
                            if (empty($image)) {
                                $sql = "SELECT image FROM items WHERE id = :id";
                                $stmt = $db->prepare($sql);
                                $stmt->execute(['id' => $id]);
                                $item = $stmt->fetch();
                                $image = $item['image'] ?? '';
                            }
                        } else {
                            $sql = "SELECT * FROM items WHERE id = :id";
                            $stmt = $db->prepare($sql);
                            $stmt->execute(['id' => $id]);
                            $item = $stmt->fetch();
                            $display_name = $item['name'];
                            $sale_percentage = (int)($item['sale'] ?? 0);
                            $item_price = $sale_percentage > 0 ? $item['price'] * (1 - $sale_percentage / 100) : $item['price'];
                            $image = $item['image'] ?? '';
                            $variant_id = null;
                        }
                        
                        $actualprice = $item_price * $cartItem['quantity'];
                        $endprice = $endprice - $actualprice;

                        if ($item || $is_variant):
                            print('<div class="cart-item">
                                <a href="/shop/product?id=' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">
                                    <img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '">
                                </a>
                                <div class="item-details">
                                    <a class="cart-a-small" href="/shop/product?id=' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">
                                        ' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '
                                    </a>
                                    <p>' . number_format($actualprice, 0, ',', ' ') . ' Kč</p>
                                    <form method="post" action="" class="form-cart">
                                        <input type="hidden" name="remove_item_id" value="'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'">');
                                        if ($variant_id) {
                                            print('<input type="hidden" name="variant_id" value="'.htmlspecialchars($variant_id, ENT_QUOTES, 'UTF-8').'">');
                                        }
                                        print('<button type="submit" class="trash-cart">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>');
                    endif; 
                endforeach;
                
                    print('<div class="free-shipping">');
                    if($endprice <= 0){
                        print('<p>Máte dopravu zdarma!</p>');
                    }else{
                        print('<p>Nakupte ještě za <strong>' . htmlspecialchars($endprice, ENT_QUOTES, 'UTF-8') . ' Kč</strong> a dopravu máte <strong>ZDARMA!</strong></p>');
                    }
                    print('</div>
            <a class="checkout-btn" href="/shop/cart">Pokračovat do košíku</a>
            
        </div>');
            }
            print('
    </div>
  <div class="hamburger">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </div>
    </nav><div id="form-phone">
            <form action="/public/shop/search" method="get" autocomplete="off">
                <div class="search-container">');
                if (isset($_GET['query'])) {
                    print('<input type="text" name="query" class="search-input" value="'.$_GET['query'].'" required>');
                }else{
                    print('<input type="text" name="query" class="search-input" placeholder="Napište, co hledáte" required>');
                }
                print('<input type="submit" value="Hledat" class="search-button">
                </div>
            </form>
        </div>');
    }

    function header_html_search($aggregated_cart, $location, $print = 1){
        $googleAnalyticsId = config('analytics.google_id');
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $scriptName = basename($scriptPath);
        $scriptDir = dirname($scriptPath);
        
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            $relativePath = '';
        } else {
            $depth = substr_count(trim($scriptDir, '/\\'), '/') + substr_count(trim($scriptDir, '/\\'), '\\');
            $relativePath = str_repeat('../', $depth);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item_id'])) {
            $remove_item_id = $_POST['remove_item_id'];
            unset($_SESSION['cart'][$remove_item_id]); 
            header('Location: ' . $location);
            exit();
        }
        print('<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Touchthemagic – Dárky, které okouzlí</title>
    <meta name="description" content="Touchthemagic – magické dárky a dekorace pro radost. Vyberte originální ručně vyrobený dárek pro každou příležitost. Doprava zdarma od 1500 Kč.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="author" content="Beáta Fraibišová">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="/public/css/toggle_button.css">
    <link rel="stylesheet" href="/public/css/phones.css">
    <script src="/public/js/loading.js"></script>
    <script src="/public/js/nav.js"></script>
    <link rel="icon" href="/public/images/logo/lighticon.png" type="image/png" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=' . $googleAnalyticsId . '"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag("js", new Date());
        gtag("config", "' . $googleAnalyticsId . '", {
            cookie_flags: "secure;samesite=strict",
            anonymize_ip: true
        });
    </script>');
    renderAnalyticsScript();
    renderCookieBanner();
print('</head>
        <body>
    <div class="loader"></div>
    <nav class="navbar" id="navbar">
        <div class="nav-branding logo">
            <a href="/">
                <img src="/public/images/logo/darklogo.svg">
            </a>
        </div>
        <div id="form">
            <form action="/public/shop/search" method="get" autocomplete="off">
                <div class="search-container">');
                if (isset($_GET['query'])) {
                    print('<input type="text" name="query" class="search-input" value="'.$_GET['query'].'" required>');
                }else{
                    print('<input type="text" name="query" class="search-input" placeholder="Napište, co hledáte" required>');
                }
                print('<input type="submit" value="Hledat" class="search-button">
                </div>
            </form>
        </div>
        <div class="left" id="left-menu">');
            print('<ul class="nav-menu">');
            if(isset($_SESSION['user_id'])){
                print('
                <li>
                <a href="/account/profile" class="nav-link log" id="loggedBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                </svg>');
                print('<p class="log-text-logged" id="acc">Můj účet</p></a></li>');
            }else {
                print('<button class="nav-link log" id="loginBtn">
                <li>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                </svg>');
                print('<p class="log-text" id="acc">Přihlášení</p></button></li>');
            }
            print(get_section_content('mobile_nav'));
            print('<div id="loginModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <form id="loginForm" action="/public/auth/login" method="POST">
                        <h2>Přihlášení</h2>
                        <input type="text" id="email" name="email" class="log-input fo" placeholder="Email" autocomplete="off" required>
                        <input type="password" id="password" name="password" class="log-input fo" placeholder="Heslo" autocomplete="off" required>
                        <div class="login_div"><button type="submit">Přihlásit se</button></div>
                        <a href="/public/auth/forgot-password">Zapoměli jste heslo?</a>
                        <a href="/public/auth/register">Nemáte účet?</a>
                    </form>
                </div>
                <div id="ppl-parcelshop-map" data-lat="50" data-lng="15" datamode="static"></div>
            </div>
            <li><a href="" class="nav-link instagram">
            </a></li></ul>');
            print('<div class="search" id="search-remove"></div>');
                $endprice = 1500;
                if($print == 1){
                    print('<div class="cart-hover">
                    <a href="/public/shop/cart" class="nav-link cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart-fill" viewBox="0 0 16 16">
                            <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                        </svg>
                        <span class="cart-count">');
                    print( getCartItemCount());
                    print('</span>
                </a>');
                    print('<div class="cart-popup">
                        <h3>Můj košík</h3>');
                    foreach ($aggregated_cart as $cartKey => $cartItem):
                        $db = getDb(); 

                        $id = $cartItem['id'];
                        $is_variant = isset($cartItem['type']) && $cartItem['type'] === 'variant';
                        
                        if ($is_variant) {
                            $display_name = $cartItem['name'];
                            $item_price = $cartItem['price'];
                            $variant_id = $cartItem['variant_id'];
                            $image = $cartItem['image'] ?? '';
                            
                            if (empty($image)) {
                                $sql = "SELECT image FROM items WHERE id = :id";
                                $stmt = $db->prepare($sql);
                                $stmt->execute(['id' => $id]);
                                $item = $stmt->fetch();
                                $image = $item['image'] ?? '';
                            }
                        } else {
                            $sql = "SELECT * FROM items WHERE id = :id";
                            $stmt = $db->prepare($sql);
                            $stmt->execute(['id' => $id]);
                            $item = $stmt->fetch();
                            $display_name = $item['name'];
                            $item_price = $item['price'];
                            $image = $item['image'] ?? '';
                            $variant_id = null;
                        }
                        
                        $actualprice = $item_price * $cartItem['quantity'];
                        $endprice = $endprice - $actualprice;

                        if ($item || $is_variant):
                            print('<div class="cart-item">
                                <a href="/shop/product?id=' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">
                                    <img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '">
                                </a>
                                <div class="item-details">
                                    <a class="cart-a-small" href="/shop/product?id=' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">
                                        ' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '
                                    </a>
                                    <p>' . number_format($actualprice, 0, ',', ' ') . ' Kč</p>
                                    <form method="post" action="" class="form-cart">
                                        <input type="hidden" name="remove_item_id" value="'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'">');
                                        if ($variant_id) {
                                            print('<input type="hidden" name="variant_id" value="'.htmlspecialchars($variant_id, ENT_QUOTES, 'UTF-8').'">');
                                        }
                                        print('<button type="submit" class="trash-cart">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>');
                    endif; 
                endforeach;
                
                    print('<div class="free-shipping">');
                    if($endprice <= 0){
                        print('<p>Máte dopravu zdarma!</p>');
                    }else{
                        print('<p>Nakupte ještě za <strong>' . htmlspecialchars($endprice, ENT_QUOTES, 'UTF-8') . ' Kč</strong> a dopravu máte <strong>ZDARMA!</strong></p>');
                    }
                    print('</div>
            <a class="checkout-btn" href="/shop/cart">Pokračovat do košíku</a>
            
        </div>');
            }
            print('
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
    </div>
    </nav><div id="form-phone">
            <form action="/public/shop/search" method="get" autocomplete="off">
                <div class="search-container">');
                if (isset($_GET['query'])) {
                    print('<input type="text" name="query" class="search-input" value="'.$_GET['query'].'" required>');
                }else{
                    print('<input type="text" name="query" class="search-input" placeholder="Napište, co hledáte" required>');
                }
                print('<input type="submit" value="Hledat" class="search-button">
                </div>
            </form>
        </div>');
    }
    function category($cat){
        if($cat == "candle"){
            return "Svíčky";
        }elseif($cat == "jewellery"){
            return "Šperky";
        }elseif($cat == "sale"){
            return "Akce";
        }else{
            return "Doporučujeme";
        }
    }
    function get_section_content($section) {
        $db = getDb();
        $sql = "SELECT content FROM homepage_content WHERE section = :section LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['section' => $section]);
        $result = $stmt->fetch();
        return $result ? $result['content'] : '';
    }

    
    function getCartItemCount() {
        $count = 0;
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
    function left_menu_html_special(){
        print('<section class="other-category">
            
        </section>');
        }
    function left_menu_html(){
        print('<section class="other-category" id="other-category">
                '.get_section_content('left_menu').'
                <section class="info-section">
                <div class="info">
                    <a href="" class="one-info ba">
                        <div class="flex be">
                            <div class="pic">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-suit-heart" viewBox="0 0 16 16">
                                    <path d="M8 7.982C9.664 6.309 13.825 9.236 8 13 2.175 9.236 6.336 6.31 8 7.982"/>
                                    <path d="M3.75 0a1 1 0 0 0-.8.4L.1 4.2a.5.5 0 0 0-.1.3V15a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V4.5a.5.5 0 0 0-.1-.3L13.05.4a1 1 0 0 0-.8-.4zm0 1H7.5v3h-6zM8.5 4V1h3.75l2.25 3zM15 5v10H1V5z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="flex">Poštovné</p>
                        <p class="flex">od 60kč</p>
                    </a>
                    <a href="" class="one-info ba">
                        <div class="flex be">
                            <div class="pic">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-suit-heart" viewBox="0 0 16 16">
                                    <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                                </svg>
                            </div>
                        </div>
                        <p class="flex">Doprava zdarma</p>
                        <p class="flex">od 1500kč</p>
                    </a>
                    <a href="" class="one-info ba">
                        <div class="flex be">
                            <div class="pic">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-suit-heart" viewBox="0 0 16 16">
                                    <path d="m8 6.236-.894-1.789c-.222-.443-.607-1.08-1.152-1.595C5.418 2.345 4.776 2 4 2 2.324 2 1 3.326 1 4.92c0 1.211.554 2.066 1.868 3.37.337.334.721.695 1.146 1.093C5.122 10.423 6.5 11.717 8 13.447c1.5-1.73 2.878-3.024 3.986-4.064.425-.398.81-.76 1.146-1.093C14.446 6.986 15 6.131 15 4.92 15 3.326 13.676 2 12 2c-.777 0-1.418.345-1.954.852-.545.515-.93 1.152-1.152 1.595zm.392 8.292a.513.513 0 0 1-.784 0c-1.601-1.902-3.05-3.262-4.243-4.381C1.3 8.208 0 6.989 0 4.92 0 2.755 1.79 1 4 1c1.6 0 2.719 1.05 3.404 2.008.26.365.458.716.596.992a7.6 7.6 0 0 1 .596-.992C9.281 2.049 10.4 1 12 1c2.21 0 4 1.755 4 3.92 0 2.069-1.3 3.288-3.365 5.227-1.193 1.12-2.642 2.48-4.243 4.38z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="flex">Ručně vyrobeno</p>
                        <p class="flex">Každý kus je originál</p>
                    </a>
                </div>
            </section>
            </section>');
    }
    function before_footer_html(){
        $db = getDb();
        $role_id = 0;
        if(isset($_SESSION['user_id'])){
            $id = $_SESSION['user_id'];
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();
            $role_id = $user['role_level'];
        }
        $content = get_section_content('before_footer');
    
        $adminPlaceholder = '###ADMIN-LINK###';
        $status = '###STATUS###';

        if ($role_id == 10) {
            $adminLink = '<a href="/admin/dashboard" class="a-footer-info">Admin</a>';
        } else {
            $adminLink = '';
        }
    
        $content = str_replace($adminPlaceholder, $adminLink, $content);
        if (isset($_SESSION['newsletter_status'])) {
            $status_info = '<p>' . htmlspecialchars($_SESSION['newsletter_status']) . '</p>';
            unset($_SESSION['newsletter_status']);
        }else{
            $status_info = '';
        }
        $content = str_replace($status, $status_info, $content);

        print($content);
    }

    function footer_html(){
        print(get_section_content('footer'));
    }

    function big_popup($items, $endprice, $name) {
        echo '<div id="cart-popup-big" class="cart-popup-big">
            <div class="cart-popup-content-big">
                <span class="close-btn-big">&times;</span>
                <h2 class="cart-headline">Produkt byl přidán do košíku!</h2>
                <p class="cart-flex"><strong>'.$name.'</strong></p>
                <div class="cart-flex-div">
                    <button id="continue-shopping">Pokračovat v nákupu</button>
                    <span class="space-between"></span>
                    <a href="/shop/cart" id="go-to-cart">Přejít do košíku</a>
                </div>
                <div class="free-shipping">';
        
        if ($endprice <= 0) {
            echo '<p>Máte dopravu zdarma!</p>';
        } else {
            echo '<p>Nakupte ještě za <strong>' . htmlspecialchars($endprice, ENT_QUOTES, 'UTF-8') . ' Kč</strong> a dopravu máte <strong>ZDARMA!</strong></p>';
        }
    
        echo '</div>
                <h3 class="other-recomended">Ostatní zákazníci také nakoupili</h3>
                <div class="recommended-products">';
        
        if (!empty($items)) {
            $count = 0; 
            foreach ($items as $item) { 
                if ($count >= 4) break; 
                    $sale_percentage = (int)($item['sale'] ?? 0);
                    $original_price = (float)$item['price'];
                    $discounted_price = $sale_percentage > 0 ? $original_price * (1 - $sale_percentage / 100) : $original_price;

                    echo '<div class="product-cart-main">
                            <div class="product-cart-img">
                                <a href="/shop/product?id=' . htmlspecialchars($item['id']) . '">
                                    <img class="image-cart-product" src="' . htmlspecialchars($item['image']) . '" alt="' . htmlspecialchars($item['name']) . '">
                                </a>
                            </div>
                            <div class="product-cart-name">
                                <a href="/shop/product?id=' . htmlspecialchars($item['id']) . '">
                                    <h3>' . htmlspecialchars($item['name']) . '</h3>
                                </a>
                            </div>
                            <div class="product-cart">';

                    if ($sale_percentage > 0) {
                        echo '<div class="price-cart">
                                <span class="original-price-small">' . number_format($original_price, 0, ",", " ") . ' Kč</span>
                                <span class="sale-price-cart">' . number_format($discounted_price, 0, ",", " ") . ' Kč</span>
                            </div>';
                    } else {
                        echo '<p class="price-cart">' . number_format($original_price, 0, ",", " ") . ' Kč</p>';
                    }

                    echo '</div>';


                    if ($item['stock'] === 'Není skladem') {
                        echo '<div class="btn btn-unavailable">
                                <p class="add-to-cart">Brzy skladem</p>
                            </div>';
                    } elseif ($item['stock'] === 'Předobjednat') {
                        echo '<form class="add-to-cart-form" method="post">
                                <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id']) . '">
                                <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name']) . '">
                                <button type="submit" class="btn btn-preorder">
                                    <p class="add-to-cart">Předobjednat</p>
                                </button>
                                <div class="preorder-note">*Dodání může trvat až měsíc</div>
                            </form>';
                    } else {
                        echo '<form class="add-to-cart-form" method="post">
                                <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id']) . '">
                                <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name']) . '">
                                <button type="submit" class="btn">
                                    <p class="add-to-cart">Přidat do košíku</p>
                                </button>
                            </form>';
                    }

                    echo '</div>'; 

                $count++; 
            }
        } else {
            echo '<p>Již brzy.. 😃</p>';
        }
    
        echo '</div>
            </div>
        </div>';
    }

    function clear_sessions_except_user_id() {
        foreach ($_SESSION as $key => $value) {
            if ($key !== 'user_id') {
                unset($_SESSION[$key]);
            }
        }
    }
    
    function renderAnalyticsScript() {
        $googleAnalyticsId = config('analytics.google_id');
    echo '
    <script>
        function hasAnalyticsConsent() {
            return localStorage.getItem("analytics_consent") === "true";
        }
        
        function initAnalytics() {
            if (hasAnalyticsConsent()) {
                (function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,"script","https://www.googletagmanager.com/gtag/js?id=' . $googleAnalyticsId . '","gtag");
                
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag("js", new Date());
                gtag("config", "' . $googleAnalyticsId . '", {
                    anonymize_ip: true,
                    cookie_flags: "secure;samesite=strict"
                });
            }
        }
        
        document.addEventListener("DOMContentLoaded", initAnalytics);
        
        function grantAnalyticsConsent() {
            localStorage.setItem("analytics_consent", "true");
            initAnalytics();
        }
        
        function revokeAnalyticsConsent() {
            localStorage.setItem("analytics_consent", "false");
            document.cookie = "_ga=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "_ga_' . $googleAnalyticsId . '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    </script>';
}

?>
