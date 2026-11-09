<?php declare(strict_types=1);

class Product
{
    public static function all(array $filters = []): array
    {
        Database::boot();
        $pdo = Database::pdo();
        $sql = 'SELECT * FROM products WHERE status = :status';
        $params = ['status' => 'active'];

        if (!empty($filters['category'])) {
            $sql .= ' AND category = :category';
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['brand'])) {
            $sql .= ' AND brand = :brand';
            $params['brand'] = $filters['brand'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (name LIKE :search_name OR brand LIKE :search_brand OR category LIKE :search_category)';
            $search = '%' . trim((string) $filters['search']) . '%';
            $params['search_name'] = $search;
            $params['search_brand'] = $search;
            $params['search_category'] = $search;
        }

        $sort = match ($filters['sort'] ?? 'popularity') {
            'price_asc' => 'COALESCE(promotional_price, price) ASC',
            'price_desc' => 'COALESCE(promotional_price, price) DESC',
            default => 'popularity DESC, created_at DESC',
        };
        $sql .= ' ORDER BY ' . $sort;

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public static function find(int $id): ?array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT * FROM products WHERE id = :id AND status = :status LIMIT 1');
        $statement->execute(['id' => $id, 'status' => 'active']);
        $product = $statement->fetch();
        return $product ?: null;
    }

    public static function create(array $data): int
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'INSERT INTO products (name, brand, category, description, image_url, price, promotional_price, stock, featured, popularity, is_perfume, status)
             VALUES (:name, :brand, :category, :description, :image_url, :price, :promotional_price, :stock, :featured, :popularity, :is_perfume, :status)'
        );
        $statement->execute(self::payload($data) + ['status' => 'active']);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        Database::boot();
        $statement = Database::pdo()->prepare(
            'UPDATE products SET name = :name, brand = :brand, category = :category, description = :description,
             image_url = :image_url, price = :price, promotional_price = :promotional_price, stock = :stock,
             featured = :featured, popularity = :popularity, is_perfume = :is_perfume
             WHERE id = :id AND status = :status'
        );
        return $statement->execute(self::payload($data) + ['id' => $id, 'status' => 'active']);
    }

    public static function delete(int $id): bool
    {
        Database::boot();
        $statement = Database::pdo()->prepare('UPDATE products SET status = :deleted WHERE id = :id AND status = :active');
        $statement->execute(['deleted' => 'deleted', 'active' => 'active', 'id' => $id]);
        return $statement->rowCount() > 0;
    }

    public static function categories(): array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT DISTINCT category FROM products WHERE status = :status ORDER BY category');
        $statement->execute(['status' => 'active']);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function brands(): array
    {
        Database::boot();
        $statement = Database::pdo()->prepare('SELECT DISTINCT brand FROM products WHERE status = :status ORDER BY brand');
        $statement->execute(['status' => 'active']);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function payload(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'brand' => trim((string) $data['brand']),
            'category' => trim((string) $data['category']),
            'description' => trim((string) $data['description']),
            'image_url' => trim((string) ($data['image_url'] ?? '')) ?: 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=900&q=80',
            'price' => (float) ($data['price'] ?? 0),
            'promotional_price' => isset($data['promotional_price']) && $data['promotional_price'] !== '' ? (float) $data['promotional_price'] : null,
            'stock' => max(0, (int) ($data['stock'] ?? 0)),
            'featured' => !empty($data['featured']) ? 1 : 0,
            'popularity' => max(0, (int) ($data['popularity'] ?? 0)),
            'is_perfume' => !empty($data['is_perfume']) ? 1 : 0,
        ];
    }
}
