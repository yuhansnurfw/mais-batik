<?php

declare(strict_types=1);

namespace MaisBatik\Models;

use PDO;

final class OrderRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, product_id, product_name, customer_name, customer_email, customer_phone,
                    variant, quantity, unit_price, status, notes, created_at
             FROM orders
             ORDER BY created_at DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, product_id, product_name, customer_name, customer_email, customer_phone,
                    variant, quantity, unit_price, status, notes
             FROM orders WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();

        return $order === false ? null : $order;
    }

    public function create(array $order): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO orders (
                product_id, product_name, customer_name, customer_email, customer_phone,
                variant, quantity, unit_price, status, notes
             ) VALUES (
                :product_id, :product_name, :customer_name, :customer_email, :customer_phone,
                :variant, :quantity, :unit_price, :status, :notes
             )'
        );
        $statement->execute($order);
    }

    public function update(int $id, array $order): void
    {
        $order['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE orders
             SET product_id = :product_id, product_name = :product_name,
                 customer_name = :customer_name, customer_email = :customer_email,
                 customer_phone = :customer_phone, variant = :variant,
                 quantity = :quantity, unit_price = :unit_price,
                 status = :status, notes = :notes
             WHERE id = :id'
        );
        $statement->execute($order);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM orders WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
