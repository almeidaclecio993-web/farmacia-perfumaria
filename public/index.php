<?php declare(strict_types=1);

$sessionConfig = require dirname(__DIR__) . '/app/config.php';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_name($sessionConfig['session_name']);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'httponly' => true,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'samesite' => 'Lax',
]);
session_start();

require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/models/Product.php';
require dirname(__DIR__) . '/app/models/User.php';
require dirname(__DIR__) . '/app/controllers/HomeController.php';
require dirname(__DIR__) . '/app/controllers/AdminController.php';
require dirname(__DIR__) . '/app/controllers/AccountController.php';

Database::boot();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '') {
    $path = '/';
}

$route = $_GET['route'] ?? $path;
$controller = new HomeController();
$adminController = new AdminController();
$accountController = new AccountController();

switch ($route) {
    case '/':
    case '/index.php':
        $controller->index();
        break;
    case '/perfumaria':
        $controller->perfumaria();
        break;
    case '/cart':
        $controller->cart();
        break;
    case '/checkout':
        $controller->checkout();
        break;
    case '/finalize':
        $controller->finalize();
        break;
    case '/login':
        $accountController->loginForm();
        break;
    case '/login/submit':
        $accountController->login();
        break;
    case '/register':
        $accountController->registerForm();
        break;
    case '/register/submit':
        $accountController->register();
        break;
    case '/account':
        $accountController->account();
        break;
    case '/account/logout':
        $accountController->logout();
        break;
    case '/auth/google':
        $accountController->googleStart();
        break;
    case '/auth/google/link':
        $accountController->googleLinkStart();
        break;
    case '/auth/google/callback':
        $accountController->googleCallback();
        break;
    case '/api/products':
        $controller->apiProducts();
        break;
    case '/api/chat':
        $controller->apiChat();
        break;
    case '/admin/login':
        $adminController->loginForm();
        break;
    case '/admin/auth':
        $adminController->login();
        break;
    case '/admin/logout':
        $adminController->logout();
        break;
    case '/admin':
        $adminController->dashboard();
        break;
    case '/admin/products/save':
        $adminController->saveProduct();
        break;
    case '/admin/products/delete':
        $adminController->deleteProduct();
        break;
    case '/admin/products/edit':
        require_admin();
        $productId = (int) ($_GET['id'] ?? 0);
        if ($productId <= 0) {
            redirect('/admin');
        }
        $product = Product::find($productId);
        if ($product === null) {
            redirect('/admin');
        }
        render('admin/dashboard', ['products' => Product::all(), 'pageTitle' => 'Editar produto']);
        break;
    default:
        $controller->index();
}
