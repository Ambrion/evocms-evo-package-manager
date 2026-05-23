<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Infrastructure\Repository;

use Illuminate\Support\Collection;

/**
 * Легковесный дубль QueryBuilder для тестов репозитория.
 * Имитирует флюент-интерфейс Eloquent без зависимостей БД.
 */
class FakeQueryBuilder
{
    public array $wheres = [];

    public array $updateData = [];

    public array $insertData = [];

    public ?Collection $returnCollection = null;

    public mixed $returnFirst = null;

    public int $returnCount = 0;

    public function __construct(?Collection $collection = null)
    {
        $this->returnCollection = $collection ?? new Collection;
    }

    // Fluent interface
    public function where(...$args): self
    {
        $this->wheres[] = ['where', $args];

        return $this;
    }

    public function whereIn(...$args): self
    {
        $this->wheres[] = ['whereIn', $args];

        return $this;
    }

    // Query methods
    public function first(): mixed
    {
        return $this->returnFirst;
    }

    public function get(): Collection
    {
        return $this->returnCollection;
    }

    public function count(): int
    {
        return $this->returnCount;
    }

    // Mutating methods
    public function updateOrInsert(array $search, array $values): bool
    {
        $this->updateData = ['search' => $search, 'values' => $values];

        return true;
    }

    public function create(array $data): mixed
    {
        $this->insertData = $data;

        return null;
    }

    // Helpers for test assertions
    public function hasWhere(string $column, mixed $value = null): bool
    {
        foreach ($this->wheres as [$method, $args]) {
            if ($method === 'where' && $args[0] === $column) {
                return $value === null || ($args[1] ?? null) === $value;
            }
        }

        return false;
    }
}
