<?php

declare(strict_types=1);

namespace MaisBatik\Controllers;

use InvalidArgumentException;
use MaisBatik\Models\CategoryRepository;

final class CategoryController
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    public function create(array $input): void
    {
        [$name, $slug] = $this->validatedInput($input);
        if ($this->categories->slugExists($slug)) {
            throw new InvalidArgumentException('Slug kategori sudah digunakan.');
        }
        if ($this->categories->nameExists($name)) {
            throw new InvalidArgumentException('Nama kategori sudah digunakan.');
        }

        $this->categories->create($name, $slug);
    }

    public function update(int $id, array $input): void
    {
        if ($this->categories->findById($id) === null) {
            throw new InvalidArgumentException('Kategori tidak ditemukan.');
        }

        [$name, $slug] = $this->validatedInput($input);
        if ($this->categories->slugExists($slug, $id)) {
            throw new InvalidArgumentException('Slug kategori sudah digunakan.');
        }
        if ($this->categories->nameExists($name, $id)) {
            throw new InvalidArgumentException('Nama kategori sudah digunakan.');
        }

        $this->categories->update($id, $name, $slug);
    }

    public function delete(int $id): void
    {
        if (!$this->categories->deleteIfUnused($id)) {
            throw new InvalidArgumentException(
                'Kategori tidak ditemukan atau masih digunakan produk.'
            );
        }
    }

    private function validatedInput(array $input): array
    {
        $name = $input['name'] ?? null;
        $slug = $input['slug'] ?? null;

        if (!is_string($name) || !mb_check_encoding($name, 'UTF-8')) {
            throw new InvalidArgumentException('Nama kategori tidak valid.');
        }
        $name = trim($name);
        if (
            $name === ''
            || mb_strlen($name, 'UTF-8') > 120
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $name) === 1
        ) {
            throw new InvalidArgumentException('Nama kategori wajib diisi (maksimal 120 karakter).');
        }

        if (!is_string($slug)) {
            throw new InvalidArgumentException('Slug kategori tidak valid.');
        }
        $slug = strtolower(trim($slug));
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) !== 1 || strlen($slug) > 60) {
            throw new InvalidArgumentException(
                'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.'
            );
        }

        return [$name, $slug];
    }
}
