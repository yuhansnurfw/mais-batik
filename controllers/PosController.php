<?php

declare(strict_types=1);

namespace MaisBatik\Controllers;

use InvalidArgumentException;
use MaisBatik\Models\PosRepository;

final class PosController
{
    public function __construct(private PosRepository $pos)
    {
    }

    public function createSale(mixed $submittedItems): array
    {
        if (!is_array($submittedItems) || $submittedItems === [] || count($submittedItems) > 50) {
            throw new InvalidArgumentException('Transaksi harus berisi 1 hingga 50 baris produk.');
        }

        $itemsByProduct = [];
        foreach ($submittedItems as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('Baris produk transaksi tidak valid.');
            }

            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 1000000],
            ]);
            if ($productId === false || $quantity === false) {
                throw new InvalidArgumentException('Pilih produk dan jumlah yang valid.');
            }

            $itemsByProduct[$productId] = ($itemsByProduct[$productId] ?? 0) + $quantity;
            if ($itemsByProduct[$productId] > 1000000) {
                throw new InvalidArgumentException('Jumlah produk dalam transaksi terlalu besar.');
            }
        }

        ksort($itemsByProduct, SORT_NUMERIC);
        $items = [];
        foreach ($itemsByProduct as $productId => $quantity) {
            $items[] = ['product_id' => $productId, 'quantity' => $quantity];
        }

        return $this->pos->createSale($items);
    }
}
