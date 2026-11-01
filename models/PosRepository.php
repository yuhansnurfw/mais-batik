<?php

declare(strict_types=1);

namespace MaisBatik\Models;

use DomainException;
use PDO;
use Throwable;

final class PosRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createSale(array $items): array
    {
        if ($items === []) {
            throw new DomainException('Tambahkan setidaknya satu produk ke transaksi.');
        }

        $this->pdo->beginTransaction();

        try {
            $saleItems = [];
            $total = 0;
            $findProduct = $this->pdo->prepare(
                'SELECT id, name, price_min, stock
                 FROM products
                 WHERE id = :id AND status = :status
                 FOR UPDATE'
            );
            $updateStock = $this->pdo->prepare(
                'UPDATE products
                 SET stock = stock - :quantity
                 WHERE id = :id AND stock >= :available_quantity'
            );

            foreach ($items as $item) {
                $findProduct->execute(['id' => $item['product_id'], 'status' => 'active']);
                $product = $findProduct->fetch();

                if ($product === false) {
                    throw new DomainException('Produk tidak ditemukan atau sedang nonaktif.');
                }

                $quantity = $item['quantity'];
                if ($quantity > (int) $product['stock']) {
                    throw new DomainException(sprintf('Stok %s tidak mencukupi.', $product['name']));
                }

                $unitPrice = (int) $product['price_min'];
                if ($unitPrice > intdiv(PHP_INT_MAX, $quantity)) {
                    throw new DomainException('Nilai transaksi melebihi batas yang didukung.');
                }

                $subtotal = $unitPrice * $quantity;
                if ($subtotal > PHP_INT_MAX - $total) {
                    throw new DomainException('Nilai transaksi melebihi batas yang didukung.');
                }

                $updateStock->execute([
                    'quantity' => $quantity,
                    'id' => $product['id'],
                    'available_quantity' => $quantity,
                ]);
                if ($updateStock->rowCount() !== 1) {
                    throw new DomainException(sprintf('Stok %s berubah. Muat ulang POS lalu coba lagi.', $product['name']));
                }

                $saleItems[] = [
                    'product_id' => (int) $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }

            $createSale = $this->pdo->prepare(
                'INSERT INTO sales_transactions (total_amount)
                 VALUES (:total_amount)'
            );
            $createSale->execute(['total_amount' => $total]);
            $saleId = (int) $this->pdo->lastInsertId();

            $createItem = $this->pdo->prepare(
                'INSERT INTO sales_transaction_items (
                    transaction_id, product_id, product_name, quantity, unit_price, subtotal
                 ) VALUES (
                    :transaction_id, :product_id, :product_name, :quantity, :unit_price, :subtotal
                 )'
            );
            foreach ($saleItems as $item) {
                $createItem->execute([
                    'transaction_id' => $saleId,
                    ...$item,
                ]);
            }

            $this->pdo->commit();

            return ['id' => $saleId, 'total_amount' => $total, 'items' => $saleItems];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function findActiveProducts(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, price_min, stock
             FROM products
             WHERE status = :status AND stock > 0
             ORDER BY name ASC, id ASC'
        );
        $statement->execute(['status' => 'active']);

        return $statement->fetchAll();
    }

    public function findRecentSales(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id, t.total_amount, t.created_at, COUNT(i.id) AS item_count
             FROM sales_transactions AS t
             LEFT JOIN sales_transaction_items AS i ON i.transaction_id = t.id
             GROUP BY t.id, t.total_amount, t.created_at
             ORDER BY t.created_at DESC, t.id DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
