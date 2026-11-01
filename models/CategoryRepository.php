<?php

declare(strict_types=1);

namespace MaisBatik\Models;

use PDO;

final class CategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, slug, created_at
             FROM categories
             ORDER BY name ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, slug FROM categories WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, slug FROM categories WHERE slug = :slug'
        );
        $statement->execute(['slug' => $slug]);
        $category = $statement->fetch();

        return $category === false ? null : $category;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM categories
             WHERE slug = :slug AND (:except_id IS NULL OR id <> :excluded_id)
             LIMIT 1'
        );
        $statement->execute([
            'slug' => $slug,
            'except_id' => $exceptId,
            'excluded_id' => $exceptId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM categories
             WHERE name = :name AND (:except_id IS NULL OR id <> :excluded_id)
             LIMIT 1'
        );
        $statement->execute([
            'name' => $name,
            'except_id' => $exceptId,
            'excluded_id' => $exceptId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $name, string $slug): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (name, slug) VALUES (:name, :slug)'
        );
        $statement->execute(['name' => $name, 'slug' => $slug]);
    }

    public function update(int $id, string $name, string $slug): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE categories SET name = :name, slug = :slug WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'name' => $name, 'slug' => $slug]);

        return $statement->rowCount() > 0;
    }

    public function deleteIfUnused(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM categories
             WHERE id = :id
               AND NOT EXISTS (
                   SELECT 1 FROM products WHERE category_id = :category_id
               )'
        );
        $statement->execute(['id' => $id, 'category_id' => $id]);

        return $statement->rowCount() > 0;
    }
}
