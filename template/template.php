<?php

    if (!defined('APP_ACCESS')) {
        http_response_code(403);
        header('location: ../template/not-found-page.php');
        exit();
    }

    if (!defined('APP_ACCESS')) {
        define('APP_ACCESS', true);
    }
    include($_SERVER['DOCUMENT_ROOT'] . '/lib/analytics.php');

    function header_html($aggregated_cart, $location, $print = 1){
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $depth = substr_count($scriptPath, '/') - 1;
        $relativePath = str_repeat('../', max(0, $depth));
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
    <link rel="stylesheet" href="'.$relativePath.'css/styles.css">
    <link rel="stylesheet" href="'.$relativePath.'css/toggle_button.css">
    <link rel="stylesheet" href="'.$relativePath.'css/phones.css">
    <script src="'.$relativePath.'js/loading.js"></script>
    <link rel="icon" href="'.$relativePath.'web_images/logo/lighticon.png" type="image/png" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-FPWDC70FMW"></script>
    <script src="'.$relativePath.'js/nav.js"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag("js", new Date());
        gtag("config", "G-FPWDC70FMW", {
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
            <a href="'.$relativePath.'index.php">
                <img src="'.$relativePath.'web_images/logo/darklogo.svg">
            </a>
        </div>
        <div id="form">
            <form action="search.php" method="get" autocomplete="off">
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
                <a href="'.$relativePath.'account/my_account.php" class="nav-link log" id="loggedBtn">
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
                    <form id="loginForm" action="/action/login.php" method="POST">
                        <h2>Přihlášení</h2>
                        <input type="text" id="email" name="email" class="log-input fo" placeholder="Email" autocomplete="off" required>
                        <input type="password" id="password" name="password" class="log-input fo" placeholder="Heslo" autocomplete="off" required>
                        <div class="login_div"><button type="submit">Přihlásit se</button></div>
                        <a href="'.$relativePath.'action/forgot_password.php">Zapoměli jste heslo?</a>
                        <a href="'.$relativePath.'action/register.php">Nemáte účet?</a>
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
                    <a href="'.$relativePath.'cart.php" class="nav-link cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart-fill" viewBox="0 0 16 16">
                            <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                        </svg>
                        <span class="cart-count">');
                    print( getCartItemCount());
                    print('</span>
                </a>');
                    print('<div class="cart-popup">
                        <h3>Můj košík</h3>');
                    foreach ($aggregated_cart as $cartItem):
                        global $db; 

                        $id = $cartItem['id'];
                        $sql = "SELECT * FROM items WHERE id = :id";
                        $stmt = $db->prepare($sql);
                        $stmt->execute(['id' => $id]);
                        $item = $stmt->fetch();
                        $actualprice = 0;
                        $actualprice += $item['price'] * $cartItem['quantity'];
                        $endprice = $endprice - $actualprice;

                        if ($item):
                            print('<div class="cart-item">
                                <a href="product.php?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                    <img src="' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                                </a>
                                <div class="item-details">
                                    <a class="cart-a-small" href="product.php?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                        ' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '
                                    </a>
                                    <p>' . htmlspecialchars($item['price'] * $cartItem['quantity'], ENT_QUOTES, 'UTF-8') . ' Kč</p>
                                    <form method="post" action="" class="form-cart">
                                        <input type="hidden" name="remove_item_id" value="'.htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8').'">
                                        <button type="submit" class="trash-cart">
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
            <a class="checkout-btn" href="cart.php">Pokračovat do košíku</a>
            
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
            <form action="search.php" method="get" autocomplete="off">
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
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $depth = substr_count($scriptPath, '/') - 1;
        $relativePath = str_repeat('../', max(0, $depth));
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
    <link rel="stylesheet" href="'.$relativePath.'css/styles.css">
    <link rel="stylesheet" href="'.$relativePath.'css/toggle_button.css">
    <link rel="stylesheet" href="'.$relativePath.'css/phones.css">
    <link rel="icon" href="'.$relativePath.'web_images/logo/lighticon.png" type="image/png" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-FPWDC70FMW"></script>
    <script src="'.$relativePath.'js/nav.js"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag("js", new Date());
        gtag("config", "G-FPWDC70FMW", {
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
            <a href="'.$relativePath.'index.php">
                <img src="'.$relativePath.'web_images/logo/darklogo.svg">
            </a>
        </div>
        <div id="form">
            <form action="search.php" method="get" autocomplete="off">
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
                <a href="'.$relativePath.'account/my_account.php" class="nav-link log" id="loggedBtn">
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
                    <form id="loginForm" action="action/login.php" method="POST">
                        <h2>Přihlášení</h2>
                        <input type="text" id="email" name="email" class="log-input fo" placeholder="Email" autocomplete="off" required>
                        <input type="password" id="password" name="password" class="log-input fo" placeholder="Heslo" autocomplete="off" required>
                        <div class="login_div"><button type="submit">Přihlásit se</button></div>
                        <a href="'.$relativePath.'action/forgot_password.php">Zapoměli jste heslo?</a>
                        <a href="'.$relativePath.'action/register.php">Nemáte účet?</a>
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
                    <a href="'.$relativePath.'cart.php" class="nav-link cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-heart-fill" viewBox="0 0 16 16">
                            <path d="M11.5 4v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m0 6.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                        </svg>
                        <span class="cart-count">');
                    print( getCartItemCount());
                    print('</span>
                </a>');
                    print('<div class="cart-popup">
                        <h3>Můj košík</h3>');
                    foreach ($aggregated_cart as $cartItem):
                        global $db; 

                        $id = $cartItem['id'];
                        $sql = "SELECT * FROM items WHERE id = :id";
                        $stmt = $db->prepare($sql);
                        $stmt->execute(['id' => $id]);
                        $item = $stmt->fetch();
                        $actualprice = 0;
                        $actualprice += $item['price'] * $cartItem['quantity'];
                        $endprice = $endprice - $actualprice;

                        if ($item):
                            print('<div class="cart-item">
                                <a href="product.php?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                    <img src="' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                                </a>
                                <div class="item-details">
                                    <a class="cart-a-small" href="product.php?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                        ' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '
                                    </a>
                                    <p>' . htmlspecialchars($item['price'] * $cartItem['quantity'], ENT_QUOTES, 'UTF-8') . ' Kč</p>
                                    <form method="post" action="" class="form-cart">
                                        <input type="hidden" name="remove_item_id" value="'.htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8').'">
                                        <button type="submit" class="trash-cart">
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
            <a class="checkout-btn" href="cart.php">Pokračovat do košíku</a>
            
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
            <form action="search.php" method="get" autocomplete="off">
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
        global $db;
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
        global $db;
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
            $adminLink = '<a href="/admin/dashboard.php" class="a-footer-info">Admin</a>';
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
                    <a href="cart.php" id="go-to-cart">Přejít do košíku</a>
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
                echo '<div class="product-cart-main">
                        <div class="product-cart-img">
                            <a href="product.php?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                <img class="image-cart-product" src="' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                            </a>
                        </div>
                        <div class="product-cart-name">
                            <a href="product.php?id=' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                                <h3>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</h3>
                            </a>
                        </div>
                        <div class="product-cart">
                            <p class="price-cart">' . htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') . ' Kč</p>
                        </div>';

                if ($item['stock'] === 'Není skladem') {
                    echo '<div class="btn btn-unavailable">
                            <p class="add-to-cart">Brzy skladem</p>
                        </div>';
                } elseif ($item['stock'] === 'Předobjednat') {
                    echo '<form class="add-to-cart-form" method="post" action="">
                            <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                            <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
                            <button type="submit" class="btn btn-preorder">
                                <p class="add-to-cart">Předobjednat</p>
                            </button>
                            <div class="preorder-note">
                                *Dodání může trvat až měsíc
                            </div>
                        </form>';
                } else {
                    echo '<form class="add-to-cart-form" method="post" action="">
                            <input type="hidden" name="item_id" value="' . htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') . '">
                            <input type="hidden" name="item_name" value="' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '">
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
    echo '
    <!-- Google Analytics s GDPR -->
    <script>
        // Kontrola souhlasu s cookies
        function hasAnalyticsConsent() {
            return localStorage.getItem("analytics_consent") === "true";
        }
        
        // Inicializace GA pouze s consent
        function initAnalytics() {
            if (hasAnalyticsConsent()) {
                // Google Analytics
                (function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,"script","https://www.googletagmanager.com/gtag/js?id=G-FPWDC70FMW","gtag");
                
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag("js", new Date());
                gtag("config", "G-FPWDC70FMW", {
                    anonymize_ip: true,
                    cookie_flags: "secure;samesite=strict"
                });
            }
        }
        
        // Spuštění při načtení stránky
        document.addEventListener("DOMContentLoaded", initAnalytics);
        
        // Funkce pro udělení souhlasu
        function grantAnalyticsConsent() {
            localStorage.setItem("analytics_consent", "true");
            initAnalytics();
        }
        
        // Funkce pro odvolání souhlasu
        function revokeAnalyticsConsent() {
            localStorage.setItem("analytics_consent", "false");
            // Vymazání GA cookies
            document.cookie = "_ga=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "_ga_G-FPWDC70FMW=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    </script>';
}

function renderCookieBanner() {
    echo '
    <div id="cookie-banner">
        <div class="cookie-inner">
            <div class="container">
            <div class="cookie-content">
                <!-- Ikona a text -->
                <div class="cookie-text">
                <div class="cookie-icon">
                    <svg width="20" height="20" fill="white" viewBox="0 0 24 24">
                    <path
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>
                </div>
                <div>
                    <h4>Cookies a soukromí</h4>
                    <p>
                    Tento web používá cookies pro zlepšení uživatelského zážitku a analytics.
                    <a href="./page.php?page=podminky-ochrany-osobnich-udaju">Více informací</a>
                    </p>
                </div>
                </div>

                <!-- Tlačítka -->
                <div class="cookie-buttons">
                <button onclick="rejectAnalytics()" class="btn-reject">
                    Pouze nezbytné
                </button>
                <button onclick="acceptCookies()" class="btn-accept">
                    Přijmout vše
                </button>
                </div>
            </div>
            </div>
        </div>
    </div>

    
    <style>
        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            #cookie-banner .container > div {
                flex-direction: column !important;
                text-align: center !important;
            }
            
            #cookie-banner .container > div > div:first-child {
                min-width: auto !important;
                margin-bottom: 15px;
            }
            
            #cookie-banner .container > div > div:last-child {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    
    <script>
        // Zobrazení banneru
        if (!localStorage.getItem("cookie_consent")) {
            document.getElementById("cookie-banner").style.display = "block";
        }
        
        function acceptCookies() {
            localStorage.setItem("cookie_consent", "accepted");
            grantAnalyticsConsent();
            
            // Animace při zavírání
            const banner = document.getElementById("cookie-banner");
            banner.style.animation = "slideDown 0.3s ease-in";
            setTimeout(() => {
                banner.style.display = "none";
            }, 300);
        }
        
        function rejectAnalytics() {
            localStorage.setItem("cookie_consent", "rejected");
            revokeAnalyticsConsent();
            
            // Animace při zavírání
            const banner = document.getElementById("cookie-banner");
            banner.style.animation = "slideDown 0.3s ease-in";
            setTimeout(() => {
                banner.style.display = "none";
            }, 300);
        }
        
        // Přidání animace pro zavírání
        const style = document.createElement("style");
        style.textContent = `
            @keyframes slideDown {
                from {
                    transform: translateY(0);
                    opacity: 1;
                }
                to {
                    transform: translateY(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>';
}

function render_product_filters($location, $current_filters = []) {
    // Získání aktuálních hodnot filtrů
    $current_sort = isset($_GET['sort']) ? $_GET['sort'] : '';
    $current_price = isset($_GET['price']) ? $_GET['price'] : '';
    $current_stock = isset($_GET['stock']) ? $_GET['stock'] : '';
    
    // Počet produktů (můžete nahradit skutečným počtem)
    $product_count = isset($current_filters['count']) ? $current_filters['count'] : 10;
    
    $html = '
    <div class="filter-section">
        <div class="filter-container">
            <div class="filter-left">
                <span class="filter-label">FILTR:</span>
                
                <!-- Filtr ceny -->
                <div class="filter-dropdown">
                    <select name="price" id="price-filter" class="filter-select">
                        <option value="">CENA</option>
                        <option value="0-500"' . ($current_price == '0-500' ? ' selected' : '') . '>0 - 500 Kč</option>
                        <option value="500-1000"' . ($current_price == '500-1000' ? ' selected' : '') . '>500 - 1000 Kč</option>
                        <option value="1000-2000"' . ($current_price == '1000-2000' ? ' selected' : '') . '>1000 - 2000 Kč</option>
                        <option value="2000-5000"' . ($current_price == '2000-5000' ? ' selected' : '') . '>2000 - 5000 Kč</option>
                        <option value="5000+"' . ($current_price == '5000+' ? ' selected' : '') . '>5000+ Kč</option>
                    </select>
                </div>
                
                <!-- Filtr dostupnosti -->
                <div class="filter-dropdown">
                    <select name="stock" id="stock-filter" class="filter-select">
                        <option value="">PODLE DOSTUPNOSTI</option>
                        <option value="skladem"' . ($current_stock == 'skladem' ? ' selected' : '') . '>Skladem</option>
                        <option value="predobjednat"' . ($current_stock == 'predobjednat' ? ' selected' : '') . '>K předobjednání</option>
                        <option value="brzy"' . ($current_stock == 'brzy' ? ' selected' : '') . '>Brzy skladem</option>
                    </select>
                </div>
            </div>
            
            <!-- Řazení produktů -->
            <div class="filter-right">
                <div class="sort-tabs">
                    <a href="' . build_filter_url($location, ['sort' => 'recommended']) . '" class="sort-tab' . ($current_sort == 'recommended' || $current_sort == '' ? ' active' : '') . '">DOPORUČUJEME</a>
                    <a href="' . build_filter_url($location, ['sort' => 'cheapest']) . '" class="sort-tab' . ($current_sort == 'cheapest' ? ' active' : '') . '">NEJLEVNĚJŠÍ</a>
                    <a href="' . build_filter_url($location, ['sort' => 'expensive']) . '" class="sort-tab' . ($current_sort == 'expensive' ? ' active' : '') . '">NEJDRAŽŠÍ</a>
                    <a href="' . build_filter_url($location, ['sort' => 'bestselling']) . '" class="sort-tab' . ($current_sort == 'bestselling' ? ' active' : '') . '">NEJPRODÁVANĚJŠÍ</a>
                    <a href="' . build_filter_url($location, ['sort' => 'alphabetical']) . '" class="sort-tab' . ($current_sort == 'alphabetical' ? ' active' : '') . '">ABECEDNĚ</a>
                </div>
                
                <div class="product-count">
                    <span>' . $product_count . ' položek celkem</span>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .filter-section {
        margin: 20px 0;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 20px;
    }
    
    .filter-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .filter-left {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .filter-label {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }
    
    .filter-dropdown {
        position: relative;
    }
    
    .filter-select {
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 8px 30px 8px 12px;
        font-size: 14px;
        color: #666;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\'><path fill=\'%23666\' d=\'M8 12L3 7h10l-5 5z\'/></svg>");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 12px;
        min-width: 180px;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #007bff;
    }
    
    .filter-right {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .sort-tabs {
        display: flex;
        gap: 0;
        background: #f8f9fa;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .sort-tab {
        padding: 8px 16px;
        text-decoration: none;
        color: #666;
        font-size: 13px;
        font-weight: 500;
        border-right: 1px solid #ddd;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .sort-tab:last-child {
        border-right: none;
    }
    
    .sort-tab:hover {
        background: #e9ecef;
        color: #333;
    }
    
    .sort-tab.active {
        background: #dc3545;
        color: white;
    }
    
    .product-count {
        color: #999;
        font-size: 14px;
        white-space: nowrap;
    }
    
    /* Responzivní design */
    @media (max-width: 768px) {
        .filter-container {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-left {
            justify-content: space-between;
        }
        
        .filter-select {
            min-width: 140px;
            font-size: 13px;
        }
        
        .sort-tabs {
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .sort-tab {
            font-size: 12px;
            padding: 6px 12px;
        }
        
        .filter-right {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        
        .product-count {
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .filter-left {
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-select {
            width: 100%;
            min-width: auto;
        }
        
        .sort-tab {
            font-size: 11px;
            padding: 6px 8px;
        }
    }
    </style>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Obsluha změny filtru ceny
        const priceFilter = document.getElementById("price-filter");
        if (priceFilter) {
            priceFilter.addEventListener("change", function() {
                updateFilter("price", this.value);
            });
        }
        
        // Obsluha změny filtru dostupnosti
        const stockFilter = document.getElementById("stock-filter");
        if (stockFilter) {
            stockFilter.addEventListener("change", function() {
                updateFilter("stock", this.value);
            });
        }
        
        function updateFilter(filterType, value) {
            const url = new URL(window.location.href);
            
            if (value === "") {
                url.searchParams.delete(filterType);
            } else {
                url.searchParams.set(filterType, value);
            }
            
            window.location.href = url.toString();
        }
    });
    </script>';
    
    return $html;
}

function build_filter_url($base_location, $new_params = []) {
    $current_params = $_GET;
    
    foreach ($new_params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($current_params[$key]);
        } else {
            $current_params[$key] = $value;
        }
    }
    
    $url = rtrim($base_location, '?&');
    if (!empty($current_params)) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($current_params);
    }
    
    return $url;
}

function get_filter_conditions() {
    $conditions = [];
    $params = [];
    
    if (isset($_GET['price']) && $_GET['price'] !== '') {
        $price_range = $_GET['price'];
        switch ($price_range) {
            case '0-500':
                $conditions[] = "price <= 500";
                break;
            case '500-1000':
                $conditions[] = "price > 500 AND price <= 1000";
                break;
            case '1000-2000':
                $conditions[] = "price > 1000 AND price <= 2000";
                break;
            case '2000-5000':
                $conditions[] = "price > 2000 AND price <= 5000";
                break;
            case '5000+':
                $conditions[] = "price > 5000";
                break;
        }
    }
    
    if (isset($_GET['stock']) && $_GET['stock'] !== '') {
        $stock_filter = $_GET['stock'];
        switch ($stock_filter) {
            case 'skladem':
                $conditions[] = "stock NOT IN ('Není skladem', 'Předobjednat')";
                break;
            case 'predobjednat':
                $conditions[] = "stock = 'Předobjednat'";
                break;
            case 'brzy':
                $conditions[] = "stock = 'Není skladem'";
                break;
        }
    }
    
    return ['conditions' => $conditions, 'params' => $params];
}

function get_sort_order() {
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'recommended';
    
    switch ($sort) {
        case 'cheapest':
            return "ORDER BY price ASC";
        case 'expensive':
            return "ORDER BY price DESC";
        case 'alphabetical':
            return "ORDER BY name ASC";
        case 'bestselling':
            return "ORDER BY sales_count DESC";
        case 'recommended':
        default:
            return "ORDER BY RAND()"; 
    }
}

?>