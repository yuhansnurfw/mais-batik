<?php

declare(strict_types=1);

namespace MaisBatik\Models;

use PDO;

final class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.name, c.id AS category_id, c.name AS category_name, c.slug AS category,
                    p.variant, p.price_min, p.price_max, p.stock, p.image_path, p.status, p.created_at
             FROM products AS p
             INNER JOIN categories AS c ON c.id = p.category_id
             ORDER BY p.updated_at DESC, p.id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.name, c.id AS category_id, c.name AS category_name, c.slug AS category,
                    p.variant, p.price_min, p.price_max, p.stock, p.image_path, p.status
             FROM products AS p
             INNER JOIN categories AS c ON c.id = p.category_id
             WHERE p.id = :id'
        );
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function create(array $product): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO products (name, category_id, variant, price_min, price_max, stock, image_path, status)
             VALUES (:name, :category_id, :variant, :price_min, :price_max, :stock, :image_path, :status)'
        );
        $statement->execute($product);
    }

    public function update(int $id, array $product): void
    {
        $product['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE products
             SET name = :name, category_id = :category_id, variant = :variant,
                 price_min = :price_min, price_max = :price_max, stock = :stock,
                 image_path = :image_path, status = :status
             WHERE id = :id'
        );
        $statement->execute($product);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
