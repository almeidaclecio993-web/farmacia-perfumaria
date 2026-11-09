<?php

declare(strict_types=1);

class User
{
    public static function createCustomer(string $name, string $email, string $password): array
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );

        try {
            $statement->execute([
                'name' => trim($name),
                'email' => strtolower(trim($email)),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer',
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Já existe uma conta cadastrada com este e-mail.');
            }
            throw $exception;
        }

        return self::findCustomerById((int) Database::pdo()->lastInsertId()) ?? throw new RuntimeException('Não foi possível carregar a conta recém-criada.');
    }

    public static function createGoogleCustomer(string $name, string $email, string $googleSubject): array
    {
        Database::boot();
        $existing = self::findCustomerByEmail($email);
        if ($existing !== null) {
            throw new DomainException('Já existe uma conta com este e-mail. Entre nela e vincule o Google nas configurações da conta.');
        }

        $statement = Database::pdo()->prepare(
            'INSERT INTO users (name, email, google_sub, role) VALUES (:name, :email, :google_sub, :role)'
        );
        try {
            $statement->execute([
                'name' => trim($name),
                'email' => strtolower(trim($email)),
                'google_sub' => $googleSubject,
                'role' => 'customer',
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Esta conta Google já está vinculada a outro usuário.');
            }
            throw $exception;
        }

        return self::findCustomerById((int) Database::pdo()->lastInsertId()) ?? throw new RuntimeException('Não foi possível carregar a conta recém-criada.');
    }

    public static function authenticateCustomer(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if ($user === null || $user['role'] !== 'customer' || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            self::updatePasswordHash((int) $user['id'], $password);
        }
        unset($user['password_hash']);
        return $user;
    }

    public static function authenticate(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if ($user === null || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            self::updatePasswordHash((int) $user['id'], $password);
        }
        unset($user['password_hash']);
        return $user;
    }

    public static function findCustomerById(int $id): ?array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT id, name, email, google_sub, role FROM users WHERE id = :id AND role = :role LIMIT 1');
        $statement->execute(['id' => $id, 'role' => 'customer']);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public static function findCustomerByGoogleSubject(string $subject): ?array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT id, name, email, google_sub, role FROM users WHERE google_sub = :subject AND role = :role LIMIT 1');
        $statement->execute(['subject' => $subject, 'role' => 'customer']);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public static function linkGoogleToCustomer(int $customerId, string $email, string $subject): void
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'UPDATE users SET google_sub = :subject WHERE id = :id AND role = :role AND LOWER(email) = :email'
        );
        try {
            $statement->execute([
                'subject' => $subject,
                'id' => $customerId,
                'role' => 'customer',
                'email' => strtolower($email),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DomainException('Esta conta Google já está vinculada a outro usuário.');
            }
            throw $exception;
        }

        if ($statement->rowCount() === 0) {
            $customer = self::findCustomerById($customerId);
            if ($customer === null || strtolower((string) $customer['email']) !== strtolower($email) || (string) ($customer['google_sub'] ?? '') !== $subject) {
                throw new DomainException('O e-mail da conta Google deve ser o mesmo e-mail deste perfil.');
            }
        }
    }

    public static function customerOrders(int $customerId): array
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'SELECT id, order_number, total, payment_method, status, created_at FROM orders
             WHERE customer_id = :customer_id ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['customer_id' => $customerId]);
        $orders = $statement->fetchAll();
        if ($orders === []) {
            return [];
        }

        $items = Database::pdo()->prepare(
            'SELECT product_name, quantity, subtotal FROM order_items WHERE order_id = :order_id ORDER BY id'
        );
        foreach ($orders as &$order) {
            $items->execute(['order_id' => $order['id']]);
            $order['items'] = $items->fetchAll();
        }
        unset($order);
        return $orders;
    }

    private static function findCustomerByEmail(string $email): ?array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT id, name, email, google_sub, role FROM users WHERE LOWER(email) = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    private static function findByEmail(string $email): ?array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT id, name, email, password_hash, google_sub, role FROM users WHERE LOWER(email) = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    private static function updatePasswordHash(int $id, string $password): void
    {
        $statement = Database::pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }
}
