<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Infrastructure\Repository;

use DateMalformedStringException;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Infrastructure\Database\Models\EvoPackage;
use RuntimeException;

readonly class EloquentPackageRegistryRepository implements PackageRegistryRepositoryInterface
{
    public function __construct(private EvoPackage $model) {}

    /**
     * @throws DateMalformedStringException
     */
    public function findByName(PackageName $name): ?InstalledPackage
    {
        $eloquent = $this->model->newQuery()
            ->where('name', $name->toString())
            ->first();

        if ($eloquent === null) {
            return null;
        }

        // getAttributes() возвращает сырые данные БЕЗ применения кастов
        $raw = $eloquent->getAttributes();

        return InstalledPackage::fromDatabaseRecord([
            'id' => (string) $raw['id'] ?? null,
            'name' => (string) $raw['name'] ?? '',
            'version' => (string) $raw['version'] ?? '',
            'status' => (string) $raw['status'] ?? 'active',
            'type' => (string) $raw['type'] ?? 'library',
            'source' => (string) $raw['source'] ?? 'packagist',
            // Безопасное декодирование: если уже массив — оставляем, иначе декодируем JSON
            'requirements' => self::decodeJsonField($raw['requirements'] ?? null),
            'autoload' => self::decodeJsonField($raw['autoload'] ?? null),
            'extra' => self::decodeJsonField($raw['extra'] ?? null),
            'metadata' => self::decodeJsonField($raw['metadata'] ?? null),
            'installed_at' => $raw['installed_at'] ?? null,
            'created_at' => $raw['created_at'] ?? null,
            'updated_at' => $raw['updated_at'] ?? null,
        ]);
    }

    public function save(InstalledPackage $package): void
    {
        // КРИТИЧНО: вручную кодируем массивы в JSON для Query Builder
        // updateOrInsert() не применяет $casts модели, поэтому делаем это явно
        $values = [
            'version' => $package->version()->toString(),
            'status' => $package->status,
            'type' => $package->type,
            'source' => $package->source,
            'requirements' => self::encodeJsonField($package->requirements),
            'autoload' => self::encodeJsonField($package->autoload),
            'extra' => self::encodeJsonField($package->extra),
            'metadata' => self::encodeJsonField($package->metadata),
            'installed_at' => $package->installedAt?->format('Y-m-d H:i:s'),
            'created_at' => $package->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $package->updatedAt?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
        ];

        // updateOrInsert на Query Builder — быстро, без загрузки модели в память
        $this->model->newQuery()->updateOrInsert(
            ['name' => $package->name()->toString()],
            $values
        );
    }

    /**
     * Кодирует массив в JSON-строку для сохранения в БД
     * Пустой массив → null (экономия места)
     */
    private static function encodeJsonField(array $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode package data to JSON');
        }

        return $encoded;
    }

    /**
     * Декодирует JSON-строку из БД в массив.
     * Работает как с сырыми данными (из БД), так и с уже декодированными (из тестов/$casts)
     */
    private static function decodeJsonField(mixed $value): array
    {
        return match (true) {
            is_array($value) => $value,
            is_string($value) && $value !== '' => json_decode($value, true) ?: [],
            default => [],
        };
    }

    public function findAll(array $filters = []): array
    {
        $query = $this->model->newQuery();

        // Фильтрация по статусу, типу, источнику
        foreach (['status', 'type', 'source'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        // Поиск по имени: частичное совпадение с экранированием
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);

            // Экранируем спецсимволы LIKE: %, _, \
            $escaped = $this->escapeLike($search);

            // Частичное совпадение в любом месте строки
            $query->where('name', 'LIKE', '%'.$escaped.'%');
        }

        // Сортировка
        $sort = $filters['sort'] ?? 'name';
        $dir = ($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        // Пагинация
        $limit = $filters['limit'] ?? 25;
        $offset = $filters['offset'] ?? 0;
        $query->limit($limit)->offset($offset);

        return $query->get()->map(fn ($record) => InstalledPackage::fromDatabaseRecord($record->toArray()))->all();
    }

    /**
     * Экранирует спецсимволы для безопасного использования в LIKE
     */
    private function escapeLike(string $value): string
    {
        // Порядок важен: сначала экранируем обратный слэш
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function remove(PackageName $name): void
    {
        $this->model->newQuery()
            ->where('name', $name->toString())
            ->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->model->newQuery();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['search'])) {
            $query->where('name', 'LIKE', '%'.$filters['search'].'%');
        }

        return $query->count();
    }

    public function getDistinctValues(string $column): array
    {
        $allowed = ['status', 'type', 'source'];
        if (! in_array($column, $allowed, true)) {
            return [];
        }

        return $this->model->newQuery()
            ->distinct()
            ->pluck($column)
            ->filter()
            ->sort()
            ->values()
            ->all();
    }
}
