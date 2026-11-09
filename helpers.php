<?php

declare(strict_types=1);

function app_config(): array
{
    static $config;

    if (!isset($config)) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return $path === '' ? '/' : $path;
}

function current_route(): string
{
    return $_GET['route'] ?? current_path();
}

function redirect(string $location): void
{
    header('Location: ' . $location, true, 302);
    exit;
}

function format_money(float $amount): string
{
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        echo 'Token CSRF inválido.';
        exit;
    }
}

function current_cart(): array
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    return $_SESSION['cart'];
}

function add_to_cart(int $productId, int $quantity = 1): void
{
    $cart = current_cart();
    $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
    $_SESSION['cart'] = $cart;
}

function remove_from_cart(int $productId): void
{
    $cart = current_cart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function update_cart_quantity(int $productId, int $quantity): void
{
    $cart = current_cart();
    if ($quantity <= 0) {
        remove_from_cart($productId);
        return;
    }

    $cart[$productId] = $quantity;
    $_SESSION['cart'] = $cart;
}

function empty_cart(): void
{
    $_SESSION['cart'] = [];
}

function render(string $viewName, array $variables = []): void
{
    extract($variables, EXTR_OVERWRITE);
    $contentView = __DIR__ . '/views/' . $viewName . '.php';
    require __DIR__ . '/views/layout.php';
}

function require_admin(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        redirect('/admin/login');
    }
}

function current_customer(): ?array
{
    $customerId = (int) ($_SESSION['customer_id'] ?? 0);
    if ($customerId <= 0) {
        return null;
    }

    $customer = User::findCustomerById($customerId);
    if ($customer === null) {
        unset($_SESSION['customer_id'], $_SESSION['customer_name']);
        return null;
    }

    return $customer;
}

function require_customer(): array
{
    $customer = current_customer();
    if ($customer === null) {
        $_SESSION['return_after_login'] = current_path() === '/checkout' ? '/checkout' : '/account';
        redirect('/login');
    }

    return $customer;
}

function asset_url(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function safe_text(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
