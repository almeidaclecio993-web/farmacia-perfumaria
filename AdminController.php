<?php

declare(strict_types=1);

class AdminController
{
    public function loginForm(): void
    {
        if (!empty($_SESSION['admin_logged_in'])) {
            redirect('/admin');
        }

        render('admin/login', [
            'pageTitle' => 'Login do Administrador',
        ]);
    }

    public function login(): void
    {
        verify_csrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) > 200) {
            $password = '';
        }

        $user = User::authenticate($email, $password);
        if ($user === null || !in_array($user['role'] ?? '', ['admin', 'gerente', 'funcionario'], true)) {
            $_SESSION['admin_error'] = 'Credenciais inválidas.';
            redirect('/admin/login');
        }

        session_regenerate_id(true);
        unset($_SESSION['customer_id'], $_SESSION['customer_name']);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
        unset($_SESSION['admin_error']);
        redirect('/admin');
    }

    public function logout(): void
    {
        verify_csrf();
        session_unset();
        session_destroy();
        redirect('/admin/login');
    }

    public function dashboard(): void
    {
        require_admin();

        $products = Product::all();
        $totalProducts = count($products);
        $stockTotal = array_sum(array_map(static fn (array $product): int => (int) $product['stock'], $products));
        $promotions = array_values(array_filter($products, static fn (array $product): bool => $product['promotional_price'] !== null));
        $perfumes = array_values(array_filter($products, static fn (array $product): bool => (int) $product['is_perfume'] === 1));

        render('admin/dashboard', [
            'products' => $products,
            'totalProducts' => $totalProducts,
            'stockTotal' => $stockTotal,
            'promotions' => $promotions,
            'perfumes' => $perfumes,
            'pageTitle' => 'Painel Administrativo',
        ]);
    }

    public function saveProduct(): void
    {
        require_admin();
        verify_csrf();

        $productId = (int) ($_POST['id'] ?? 0);
        $payload = [
            'name' => (string) ($_POST['name'] ?? ''),
            'brand' => (string) ($_POST['brand'] ?? ''),
            'category' => (string) ($_POST['category'] ?? ''),
            'description' => (string) ($_POST['description'] ?? ''),
            'image_url' => (string) ($_POST['image_url'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'promotional_price' => isset($_POST['promotional_price']) && $_POST['promotional_price'] !== '' ? (float) $_POST['promotional_price'] : null,
            'stock' => (int) ($_POST['stock'] ?? 0),
            'featured' => !empty($_POST['featured']) ? 1 : 0,
            'is_perfume' => !empty($_POST['is_perfume']) ? 1 : 0,
            'popularity' => (int) ($_POST['popularity'] ?? 0),
        ];

        if ($productId > 0) {
            Product::update($productId, $payload);
        } else {
            Product::create($payload);
        }

        redirect('/admin');
    }

    public function deleteProduct(): void
    {
        require_admin();
        verify_csrf();
        $productId = (int) ($_POST['id'] ?? 0);
        if ($productId > 0) {
            Product::delete($productId);
        }

        redirect('/admin');
    }
}
