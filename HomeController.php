<?php declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
        $products = Product::all();
        $featured = array_values(array_filter($products, static fn (array $product): bool => (int) $product['featured'] === 1));
        $offers = array_values(array_filter($products, static fn (array $product): bool => $product['promotional_price'] !== null));
        $bestSellers = array_slice(array_values(array_filter($products, static fn (array $product): bool => (int) $product['popularity'] > 0)), 0, 4);
        $categories = Product::categories();

        render('home', [
            'products' => $products,
            'featured' => $featured,
            'offers' => $offers,
            'bestSellers' => $bestSellers,
            'categories' => $categories,
            'pageTitle' => 'Vitrine Digital',
        ]);
    }

    public function perfumaria(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $brand = trim((string) ($_GET['brand'] ?? ''));
        $priceMax = (float) ($_GET['price_max'] ?? 0);
        $sort = trim((string) ($_GET['sort'] ?? 'popularity'));

        $filters = compact('search', 'category', 'brand', 'priceMax', 'sort');
        $products = Product::all([
            'search' => $search,
            'category' => $category,
            'brand' => $brand,
            'sort' => $sort,
        ]);

        if ($priceMax > 0) {
            $products = array_values(array_filter($products, static fn (array $product): bool => ((float) $product['promotional_price'] ?? (float) $product['price']) <= $priceMax));
        }

        render('perfumaria', [
            'products' => $products,
            'categories' => Product::categories(),
            'brands' => Product::brands(),
            'filters' => $filters,
            'pageTitle' => 'Perfumaria',
        ]);
    }

    public function cart(): void
    {
        if (isset($_GET['add'])) {
            $productId = (int) $_GET['add'];
            $product = Product::find($productId);
            $quantityInCart = (int) (current_cart()[$productId] ?? 0);
            if ($product !== null && $quantityInCart < min(99, (int) $product['stock'])) {
                add_to_cart($productId, 1);
            }
        }

        if (isset($_GET['remove'])) {
            remove_from_cart((int) $_GET['remove']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
            foreach ($_POST['qty'] ?? [] as $productId => $quantity) {
                $productId = (int) $productId;
                $product = Product::find($productId);
                $quantity = max(0, min((int) $quantity, 99, (int) ($product['stock'] ?? 0)));
                update_cart_quantity($productId, $quantity);
            }
        }

        $cart = current_cart();
        $items = [];
        $total = 0.0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find((int) $productId);
            if (!$product) {
                continue;
            }

            $price = !empty($product['promotional_price']) ? (float) $product['promotional_price'] : (float) $product['price'];
            $subtotal = $price * (int) $quantity;
            $items[] = [
                'product' => $product,
                'quantity' => (int) $quantity,
                'price' => $price,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        render('cart', [
            'items' => $items,
            'total' => $total,
            'pageTitle' => 'Carrinho',
        ]);
    }

    public function checkout(): void
    {
        $customer = require_customer();
        header('Cache-Control: no-store, private');
        $cart = current_cart();
        if ($cart === []) {
            redirect('/cart');
        }

        $items = [];
        $total = 0.0;
        foreach ($cart as $productId => $quantity) {
            $product = Product::find((int) $productId);
            if (!$product) {
                continue;
            }

            $price = !empty($product['promotional_price']) ? (float) $product['promotional_price'] : (float) $product['price'];
            $subtotal = $price * (int) $quantity;
            $items[] = [
                'product' => $product,
                'quantity' => (int) $quantity,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        $pixCode = 'FARMACIA-' . strtoupper(bin2hex(random_bytes(8)));

        render('checkout', [
            'items' => $items,
            'total' => $total,
            'pixCode' => $pixCode,
            'customer' => $customer,
            'pageTitle' => 'Checkout',
        ]);
    }

    public function finalize(): void
    {
        verify_csrf();
        $customer = require_customer();

        $cart = current_cart();
        if ($cart === []) {
            redirect('/cart');
        }

        $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'pix'));
        if (!in_array($paymentMethod, ['pix', 'credit', 'debit'], true)) {
            $_SESSION['account_error'] = 'Selecione uma forma de pagamento válida.';
            redirect('/checkout');
        }

        try {
            $order = Database::transaction(static function (PDO $pdo) use ($cart, $paymentMethod, $customer): array {
                ksort($cart, SORT_NUMERIC);
                $selectProduct = $pdo->prepare('SELECT id, name, price, promotional_price, stock FROM products WHERE id = :id AND status = :status FOR UPDATE');
                $updateStock = $pdo->prepare('UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :available_quantity');
                $orderItems = [];
                $total = 0.0;
                foreach ($cart as $productId => $quantity) {
                    $productId = (int) $productId;
                    $quantity = (int) $quantity;
                    if ($quantity < 1 || $quantity > 99) {
                        throw new DomainException('Um produto do carrinho não está mais disponível.');
                    }

                    $selectProduct->execute(['id' => $productId, 'status' => 'active']);
                    $product = $selectProduct->fetch();
                    if (!$product) {
                        throw new DomainException('Um produto do carrinho não está mais disponível.');
                    }
                    if ((int) $product['stock'] < $quantity) {
                        throw new DomainException('O estoque mudou. Atualize seu carrinho antes de continuar.');
                    }

                    $price = !empty($product['promotional_price']) ? (float) $product['promotional_price'] : (float) $product['price'];
                    $subtotal = $price * $quantity;
                    $total += $subtotal;
                    $orderItems[] = [
                        'product_id' => $productId,
                        'product_name' => (string) $product['name'],
                        'quantity' => $quantity,
                        'unit_price' => $price,
                        'subtotal' => $subtotal,
                    ];
                }

                if ($orderItems === []) {
                    throw new DomainException('Seu carrinho não possui produtos disponíveis.');
                }

                $orderNumber = 'PED-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders (order_number, customer_id, total, payment_method, status)
                     VALUES (:order_number, :customer_id, :total, :payment_method, :status)'
                );
                $insertOrder->execute([
                    'order_number' => $orderNumber,
                    'customer_id' => (int) $customer['id'],
                    'total' => $total,
                    'payment_method' => $paymentMethod,
                    'status' => 'pending',
                ]);
                $orderId = (int) $pdo->lastInsertId();

                foreach ($orderItems as $item) {
                    $updateStock->execute([
                        'quantity' => $item['quantity'],
                        'id' => $item['product_id'],
                        'available_quantity' => $item['quantity'],
                    ]);
                    if ($updateStock->rowCount() !== 1) {
                        throw new DomainException('O estoque mudou. Atualize seu carrinho antes de continuar.');
                    }
                }

                $insertItem = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
                     VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :subtotal)'
                );
                foreach ($orderItems as $item) {
                    $insertItem->execute(['order_id' => $orderId] + $item);
                }

                $providerRef = $paymentMethod === 'pix' ? 'PIX-' . strtoupper(bin2hex(random_bytes(6))) : 'GATEWAY-' . strtoupper(bin2hex(random_bytes(6)));
                $insertPayment = $pdo->prepare(
                    'INSERT INTO payments (order_id, method, amount, status, provider_reference)
                     VALUES (:order_id, :method, :amount, :status, :provider_reference)'
                );
                $insertPayment->execute([
                    'order_id' => $orderId,
                    'method' => $paymentMethod,
                    'amount' => $total,
                    'status' => 'pending',
                    'provider_reference' => $providerRef,
                ]);

                return [
                    'order_number' => $orderNumber,
                    'total' => $total,
                    'payment_method' => $paymentMethod,
                    'provider_reference' => $providerRef,
                ];
            });
        } catch (DomainException $exception) {
            $_SESSION['account_error'] = $exception->getMessage();
            redirect('/cart');
        }

        empty_cart();
        $_SESSION['last_order'] = $order;

        redirect('/cart?success=1');
    }

    public function apiProducts(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(Product::all(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function apiChat(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $message = strtolower(trim((string) ($_POST['message'] ?? '')));
        $products = Product::all();

        if ($message === '') {
            echo json_encode(['reply' => 'Como posso ajudar hoje?'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (str_contains($message, 'doença') || str_contains($message, 'tratamento') || str_contains($message, 'remédio') || str_contains($message, 'dosagem')) {
            echo json_encode([
                'reply' => 'Posso ajudar com produtos e informações de vendas, mas não realizo diagnósticos nem prescrevo tratamentos. Para qualquer dúvida sobre saúde, procure um farmacêutico ou profissional habilitado.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        foreach ($products as $product) {
            $haystack = strtolower((string) $product['name'] . ' ' . (string) $product['brand'] . ' ' . (string) $product['category']);
            if (str_contains($haystack, $message)) {
                $priceText = !empty($product['promotional_price']) ? ' por R$ ' . number_format((float) $product['promotional_price'], 2, ',', '.') : ' por R$ ' . number_format((float) $product['price'], 2, ',', '.');
                echo json_encode([
                    'reply' => sprintf('Encontrei %s. %s está %s e há %d unidades em estoque. Posso te ajudar a adicionar ao carrinho ou finalizar pelo WhatsApp.', $product['name'], $product['name'], $priceText, (int) $product['stock']),
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $categoryMatches = array_filter($products, static fn (array $product): bool => str_contains(strtolower((string) $product['category']), $message));
        if ($categoryMatches) {
            $names = array_map(static fn (array $product): string => $product['name'], array_slice($categoryMatches, 0, 3));
            echo json_encode([
                'reply' => 'Tenho ótimas opções nessa categoria: ' . implode(', ', $names) . '. Posso te mostrar preços e disponibilidade.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (str_contains($message, 'oferta') || str_contains($message, 'promo')) {
            $promos = array_values(array_filter($products, static fn (array $product): bool => $product['promotional_price'] !== null && $product['promotional_price'] !== ''));
            $names = array_map(static fn (array $product): string => $product['name'] . ' (R$ ' . number_format((float) $product['promotional_price'], 2, ',', '.') . ')', array_slice($promos, 0, 3));
            echo json_encode([
                'reply' => 'As principais promoções hoje são: ' . implode('; ', $names) . '.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (str_contains($message, 'perfum') || str_contains($message, 'frag')) {
            $fragrances = array_filter($products, static fn (array $product): bool => (int) $product['is_perfume'] === 1);
            $names = array_map(static fn (array $product): string => $product['name'], array_slice($fragrances, 0, 3));
            echo json_encode([
                'reply' => 'Na perfumaria, as sugestões mais populares são: ' . implode(', ', $names) . '.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'reply' => 'Posso te ajudar com produtos, preços, ofertas e perfumaria. Tente pesquisar por uma marca, categoria ou nome de produto.',
        ], JSON_UNESCAPED_UNICODE);
    }
}
