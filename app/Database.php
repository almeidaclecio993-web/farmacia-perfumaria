<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $connection = null;
    private static bool $booted = false;

    public static function pdo(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = app_config()['db'];
        if (($config['driver'] ?? '') !== 'mysql') {
            throw new RuntimeException('Este projeto exige MySQL. Configure DB_DRIVER=mysql e as variáveis DB_*.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );
        self::$connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        self::$connection->exec("SET time_zone = '+00:00'");

        return self::$connection;
    }

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        $pdo = self::pdo();
        $schema = [
            'CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(254) NOT NULL,
                password_hash VARCHAR(255) NULL,
                role VARCHAR(24) NOT NULL DEFAULT "customer",
                google_sub VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY users_email_unique (email),
                UNIQUE KEY users_google_sub_unique (google_sub)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS products (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(180) NOT NULL,
                brand VARCHAR(120) NOT NULL,
                category VARCHAR(120) NOT NULL,
                description TEXT NOT NULL,
                image_url TEXT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                promotional_price DECIMAL(10,2) NULL,
                stock INT UNSIGNED NOT NULL DEFAULT 0,
                featured TINYINT(1) NOT NULL DEFAULT 0,
                popularity INT UNSIGNED NOT NULL DEFAULT 0,
                is_perfume TINYINT(1) NOT NULL DEFAULT 0,
                status VARCHAR(16) NOT NULL DEFAULT "active",
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY products_status_idx (status),
                KEY products_category_idx (category),
                KEY products_brand_idx (brand)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS orders (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(40) NOT NULL,
                customer_id BIGINT UNSIGNED NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(16) NOT NULL,
                status VARCHAR(24) NOT NULL DEFAULT "pending",
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY orders_number_unique (order_number),
                KEY orders_customer_created_idx (customer_id, created_at),
                CONSTRAINT orders_customer_fk FOREIGN KEY (customer_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS order_items (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                order_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NULL,
                product_name VARCHAR(180) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                KEY order_items_order_idx (order_id),
                CONSTRAINT order_items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                CONSTRAINT order_items_product_fk FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS payments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                order_id BIGINT UNSIGNED NOT NULL,
                method VARCHAR(16) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(24) NOT NULL DEFAULT "pending",
                provider_reference VARCHAR(100) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY payments_order_idx (order_id),
                CONSTRAINT payments_order_fk FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(80) NOT NULL PRIMARY KEY,
                `value` TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS login_attempts (
                attempt_key CHAR(64) NOT NULL PRIMARY KEY,
                attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                first_attempt_at DATETIME NOT NULL,
                KEY login_attempts_time_idx (first_attempt_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];

        foreach ($schema as $statement) {
            $pdo->exec($statement);
        }

        self::importJsonData($pdo);
        self::seedProducts($pdo);
        self::seedAdmin($pdo);
        self::seedSettings($pdo);
        self::$booted = true;
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function importJsonData(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) {
            return;
        }

        $path = dirname(__DIR__) . '/storage/database.json';
        if (!is_file($path)) {
            return;
        }
        $json = file_get_contents($path);
        $data = $json === false ? null : json_decode($json, true);
        if (!is_array($data)) {
            throw new RuntimeException('O arquivo storage/database.json existe, mas não contém JSON válido; os dados não foram importados.');
        }

        self::transaction(static function (PDO $pdo) use ($data): void {
            $users = $pdo->prepare(
                'INSERT INTO users (id, name, email, password_hash, role, google_sub, created_at)
                 VALUES (:id, :name, :email, :password_hash, :role, :google_sub, :created_at)'
            );
            foreach ($data['users'] ?? [] as $user) {
                if (!is_array($user) || empty($user['id']) || empty($user['email'])) {
                    continue;
                }
                $users->execute([
                    'id' => (int) $user['id'],
                    'name' => (string) ($user['name'] ?? 'Cliente'),
                    'email' => strtolower((string) $user['email']),
                    'password_hash' => $user['password_hash'] ?? null,
                    'role' => (string) ($user['role'] ?? 'customer'),
                    'google_sub' => $user['google_sub'] ?? null,
                    'created_at' => self::mysqlDate($user['created_at'] ?? null),
                ]);
            }

            $products = $pdo->prepare(
                'INSERT INTO products (id, name, brand, category, description, image_url, price, promotional_price, stock, featured, popularity, is_perfume, status, created_at)
                 VALUES (:id, :name, :brand, :category, :description, :image_url, :price, :promotional_price, :stock, :featured, :popularity, :is_perfume, :status, :created_at)'
            );
            foreach ($data['products'] ?? [] as $product) {
                if (!is_array($product) || empty($product['id']) || empty($product['name'])) {
                    continue;
                }
                $products->execute([
                    'id' => (int) $product['id'],
                    'name' => (string) $product['name'],
                    'brand' => (string) ($product['brand'] ?? ''),
                    'category' => (string) ($product['category'] ?? ''),
                    'description' => (string) ($product['description'] ?? ''),
                    'image_url' => $product['image_url'] ?? null,
                    'price' => (float) ($product['price'] ?? 0),
                    'promotional_price' => $product['promotional_price'] ?? null,
                    'stock' => max(0, (int) ($product['stock'] ?? 0)),
                    'featured' => !empty($product['featured']) ? 1 : 0,
                    'popularity' => max(0, (int) ($product['popularity'] ?? 0)),
                    'is_perfume' => !empty($product['is_perfume']) ? 1 : 0,
                    'status' => (string) ($product['status'] ?? 'active'),
                    'created_at' => self::mysqlDate($product['created_at'] ?? null),
                ]);
            }

            $orders = $pdo->prepare(
                'INSERT INTO orders (id, order_number, customer_id, total, payment_method, status, created_at)
                 VALUES (:id, :order_number, :customer_id, :total, :payment_method, :status, :created_at)'
            );
            foreach ($data['orders'] ?? [] as $order) {
                if (!is_array($order) || empty($order['id']) || empty($order['customer_id'])) {
                    continue;
                }
                $orders->execute([
                    'id' => (int) $order['id'],
                    'order_number' => (string) ($order['order_number'] ?? ('MIG-' . $order['id'])),
                    'customer_id' => (int) $order['customer_id'],
                    'total' => (float) ($order['total'] ?? 0),
                    'payment_method' => (string) ($order['payment_method'] ?? 'pix'),
                    'status' => (string) ($order['status'] ?? 'pending'),
                    'created_at' => self::mysqlDate($order['created_at'] ?? null),
                ]);
            }

            $items = $pdo->prepare(
                'INSERT INTO order_items (id, order_id, product_id, product_name, quantity, unit_price, subtotal)
                 VALUES (:id, :order_id, :product_id, :product_name, :quantity, :unit_price, :subtotal)'
            );
            foreach ($data['order_items'] ?? [] as $item) {
                if (!is_array($item) || empty($item['id']) || empty($item['order_id'])) {
                    continue;
                }
                $items->execute([
                    'id' => (int) $item['id'],
                    'order_id' => (int) $item['order_id'],
                    'product_id' => !empty($item['product_id']) ? (int) $item['product_id'] : null,
                    'product_name' => (string) ($item['product_name'] ?? 'Produto'),
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'subtotal' => (float) ($item['subtotal'] ?? 0),
                ]);
            }

            $payments = $pdo->prepare(
                'INSERT INTO payments (id, order_id, method, amount, status, provider_reference, created_at)
                 VALUES (:id, :order_id, :method, :amount, :status, :provider_reference, :created_at)'
            );
            foreach ($data['payments'] ?? [] as $payment) {
                if (!is_array($payment) || empty($payment['id']) || empty($payment['order_id'])) {
                    continue;
                }
                $payments->execute([
                    'id' => (int) $payment['id'],
                    'order_id' => (int) $payment['order_id'],
                    'method' => (string) ($payment['method'] ?? 'pix'),
                    'amount' => (float) ($payment['amount'] ?? 0),
                    'status' => (string) ($payment['status'] ?? 'pending'),
                    'provider_reference' => $payment['provider_reference'] ?? null,
                    'created_at' => self::mysqlDate($payment['created_at'] ?? null),
                ]);
            }

            $settings = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value)');
            foreach ($data['settings'] ?? [] as $key => $value) {
                $settings->execute(['key' => (string) $key, 'value' => is_scalar($value) ? (string) $value : '']);
            }
        });
    }

    private static function mysqlDate(mixed $date): string
    {
        if (!is_string($date) || $date === '') {
            return gmdate('Y-m-d H:i:s');
        }
        $timestamp = strtotime($date);
        return $timestamp === false ? gmdate('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s', $timestamp);
    }

    private static function seedProducts(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) {
            return;
        }

        $products = [
            ['Dermacare Creme Facial', 'Dermacare', 'Cuidados com a pele', 'Creme hidratante para pele seca com ácido hialurônico.', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=80', 89.90, 69.90, 35, 1, 92, 0],
            ['Essência Floral Masculina', 'Aurelia', 'Perfumes masculinos', 'Perfume intenso com notas de bergamota, sândalo e âmbar.', 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=900&q=80', 189.00, 159.00, 28, 1, 88, 1],
            ['Luna Perfume Feminino', 'Luna', 'Perfumes femininos', 'Fragrância delicada com flores brancas e notas cítricas.', 'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&w=900&q=80', 174.90, 149.90, 42, 1, 86, 1],
        ];
        $insert = $pdo->prepare(
            'INSERT INTO products (name, brand, category, description, image_url, price, promotional_price, stock, featured, popularity, is_perfume)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($products as $product) {
            $insert->execute($product);
        }
    }

    private static function seedAdmin(PDO $pdo): void
    {
        $config = app_config()['admin'];
        $email = strtolower(trim($config['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($config['password']) < 16 || strlen($config['password']) > 200) {
            return;
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $insert->execute(['Administrador', $email, password_hash($config['password'], PASSWORD_DEFAULT), 'admin']);
    }

    private static function seedSettings(PDO $pdo): void
    {
        $insert = $pdo->prepare('INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)');
        $insert->execute(['whatsapp_number', app_config()['whatsapp_number']]);
        $insert->execute(['store_name', app_config()['app_name']]);
        $insert->execute(['store_city', 'São Paulo']);
    }
}
