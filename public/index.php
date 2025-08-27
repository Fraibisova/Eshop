<?php

define('APP_ACCESS', true);

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

require_once BASE_PATH . '/config/bootstrap/autoload.php';

session_start();

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

$route = trim(str_replace('/public', '', $path), '/');

if (strpos($route, '?') !== false) {
    $route = substr($route, 0, strpos($route, '?'));
}

$route = rtrim($route, '/');

if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $path)) {
    $filePath = __DIR__ . str_replace('/public', '', $path);
    if (file_exists($filePath)) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];
        
        if (isset($contentTypes[$extension])) {
            header('Content-Type: ' . $contentTypes[$extension]);
        }
        
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        echo "File not found: " . htmlspecialchars($path);
        exit;
    }
}

if ($route === 'index.php') {
    $route = '';
} elseif ($route === 'product.php') {
    $route = 'shop/product';
} elseif ($route === 'cart.php') {
    $route = 'shop/cart';
} elseif ($route === 'checkout.php') {
    $route = 'shop/checkout';
} elseif ($route === 'search.php') {
    $route = 'shop/search';
} elseif ($route === 'login.php') {
    $route = 'auth/login';
} elseif ($route === 'register.php') {
    $route = 'auth/register';
} elseif ($route === 'logout.php') {
    $route = 'auth/logout';
} elseif ($route === 'my_account.php') {
    $route = 'account/profile';
} elseif ($route === 'page.php') {
    $route = 'pages/page';
} elseif ($route === 'admin.php') {
    $route = 'admin';
}

switch ($route) {
    case '':
    case 'index':
    case 'home':
        $controller = new App\Controllers\HomeController();
        $controller->index();
        break;
        
    case 'auth/login':
        $controller = new App\Controllers\AuthController();
        $controller->login();
        break;
        
    case 'auth/register':
        $controller = new App\Controllers\Actions\RegisterController();
        $controller->register();
        break;
        
    case 'auth/logout':
        $controller = new App\Controllers\AuthController();
        $controller->logout();
        break;
        
    case 'auth/forgot-password':
        $controller = new App\Controllers\AuthController();
        $controller->forgotPassword();
        break;
        
    case 'shop/cart':
        $controller = new App\Controllers\Shop\CartController();
        $controller->show();
        break;
        
    case 'shop/checkout':
        $controller = new App\Controllers\Actions\CheckoutController();
        $controller->checkout();
        break;
        
    case 'shop/product':
        $controller = new App\Controllers\ProductController();
        $controller->show();
        break;
        
    case 'product':
        $controller = new App\Controllers\ProductController();
        $controller->show();
        break;
        
    case 'cart':
        $controller = new App\Controllers\Shop\CartController();
        $controller->show();
        break;
        
    case 'shop/search':
        $controller = new App\Controllers\SearchController();
        $controller->search();
        break;
        
    case 'account/profile':
    case 'account/my-account':
        $controller = new App\Controllers\Account\ProfileController();
        $controller->myAccount();
        break;
        
    case 'account/billing':
        $controller = new App\Controllers\Account\ProfileController();
        $controller->billingAddress();
        break;
        
    case 'account/password':
        $controller = new App\Controllers\Account\ProfileController();
        $controller->changePassword();
        break;
        
    case 'admin':
        $controller = new App\Controllers\Admin\DashboardController();
        $controller->index();
        break;
        
    case 'admin/dashboard':
        $controller = new App\Controllers\AdminController();
        $controller->dashboard();
        break;
        
    case 'admin/products':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->listProductsWithVariants();
        break;
        
    case 'admin/products/edit_product.php':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->listProductsWithVariants();
        break;
        
    case 'admin/products/add':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->addProduct();
        break;
        
    case 'admin/add-product':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->addProduct();
        break;
        
    case 'admin/products/edit':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->editProduct();
        break;
        
    case 'admin/edit-product':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->editProduct();
        break;
        
    case 'admin/products/delete':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->deleteProduct();
        break;
        
    case 'admin/products/description/edit':
        $controller = new App\Controllers\Admin\ProductManagementController();
        $controller->editDescription();
        break;
        
    case 'admin/products/variants':
        $controller = new App\Controllers\Admin\ProductVariantController();
        $controller->index();
        break;
        
    case 'admin/products/variants/add':
        $controller = new App\Controllers\Admin\ProductVariantController();
        $controller->add();
        break;
        
    case 'admin/products/variants/edit':
        $controller = new App\Controllers\Admin\ProductVariantController();
        $controller->edit();
        break;
        
    case 'admin/products/variants/delete':
        $controller = new App\Controllers\Admin\ProductVariantController();
        $controller->delete();
        break;
        
    case 'admin/orders':
        $controller = new App\Controllers\Admin\OrderController();
        $controller->index();
        break;
        
    case 'admin/orders/detail':
        $controller = new App\Controllers\Admin\OrderController();
        $controller->detail();
        break;
        
    case 'admin/orders/export':
        echo '<h1>Export Orders</h1>';
        echo '<p>Export functionality will be implemented later.</p>';
        echo '<a href="/admin/orders">Back to Orders</a>';
        break;
        
    case 'admin/newsletter':
        $controller = new App\Controllers\Admin\NewsletterController();
        $controller->index();
        break;
        
    case 'admin/newsletter/edit':
        $controller = new App\Controllers\Admin\NewsletterController();
        $controller->edit();
        break;
        
    case 'admin/newsletter/delete':
        $controller = new App\Controllers\Admin\NewsletterController();
        $controller->delete();
        break;
        
    case 'admin/newsletter/create':
        $controller = new App\Controllers\Admin\NewsletterController();
        $controller->create();
        break;
        
    case 'admin/upload':
        $controller = new App\Controllers\Admin\UploadController();
        $controller->index();
        break;
        
    case 'admin/pages':
        $controller = new App\Controllers\Admin\PageController();
        if (isset($_GET['id']) || isset($_GET['action'])) {
            $controller->edit();
        } else {
            $controller->list();
        }
        break;
        
    case 'admin/website':
        $controller = new App\Controllers\Admin\WebsiteController();
        $controller->editWebsite();
        break;
        
    case 'action/shipping-and-payment':
        $controller = new App\Controllers\Actions\ShippingController();
        $controller->shippingAndPayment();
        break;
        
    case 'action/billing-and-address':
        $controller = new App\Controllers\Actions\BillingController();
        $controller->billingAndAddress();
        break;
        
    case 'pages/page':
        $controller = new App\Controllers\PageController();
        $controller->show();
        break;
        
    case 'pages/sitemap':
        $controller = new App\Controllers\PageController();
        $controller->sitemap();
        break;
        
    default:
        http_response_code(404);
        if (file_exists(BASE_PATH . '/app/Views/not-found-page.php')) {
            include BASE_PATH . '/app/Views/not-found-page.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
        break;
}
?>