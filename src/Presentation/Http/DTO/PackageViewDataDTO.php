<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Presentation\Http\DTO;

use ArrayAccess;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use LogicException;

/**
 * DTO для отображения пакета в админ-интерфейсе.
 * Преобразует доменную сущность с Value Objects в простые скалярные значения.
 */
readonly class PackageViewDataDTO implements ArrayAccess
{
    public function __construct(
        public string $name,
        public string $version,
        public string $status,
        public string $type,
        public string $source,
        public array $requirements,
        public array $metadata,
        public ?string $installedAt,
        public ?string $createdAt,
        public ?string $updatedAt,
        public int|string|null $id = null,
    ) {}

    /**
     * Фабрика: создание из доменной сущности
     */
    public static function fromEntity(InstalledPackage $entity): self
    {
        return new self(
            name: $entity->name->toString(),
            version: $entity->version->toString(),
            status: $entity->status,
            type: $entity->type,
            source: $entity->source,
            requirements: $entity->requirements,
            metadata: $entity->metadata,
            installedAt: $entity->installedAt?->format('Y-m-d H:i:s'),
            createdAt: $entity->createdAt?->format('Y-m-d H:i:s'),
            updatedAt: $entity->updatedAt?->format('Y-m-d H:i:s'),
            id: $entity->id,
        );
    }

    /**
     * Фабрика: создание из массива данных
     *
     * Полезно для:
     * - Тестирования (без создания полной сущности)
     * - Десериализации из кэша или API
     * - Гибкости при различных источниках данных
     *
     * @param array{
     *     name: string,
     *     version: string,
     *     status?: string,
     *     type?: string,
     *     source?: string,
     *     requirements?: array,
     *     metadata?: array,
     *     installedAt?: ?string,
     *     createdAt?: ?string,
     *     updatedAt?: ?string,
     *     id?: int|string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        // Валидация обязательных полей
        if (! isset($data['name']) || ! is_string($data['name']) || $data['name'] === '') {
            throw new LogicException('PackageViewDataDTO: required field "name" is missing or invalid');
        }
        if (! isset($data['version']) || ! is_string($data['version']) || $data['version'] === '') {
            throw new LogicException('PackageViewDataDTO: required field "version" is missing or invalid');
        }

        return new self(
            name: $data['name'],
            version: $data['version'],
            status: $data['status'] ?? 'active',
            type: $data['type'] ?? 'library',
            source: $data['source'] ?? 'packagist',
            requirements: $data['requirements'] ?? [],
            metadata: $data['metadata'] ?? [],
            installedAt: $data['installedAt'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    /**
     * Преобразование обратно в массив
     *
     * Полезно для:
     * - Сериализации в кэш или сессию
     * - Передачи данных в шаблоны с поддержкой обоих синтаксисов
     * - Тестирования (сравнение с ожидаемым массивом)
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'status' => $this->status,
            'type' => $this->type,
            'source' => $this->source,
            'requirements' => $this->requirements,
            'metadata' => $this->metadata,
            'installedAt' => $this->installedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'id' => $this->id,
        ];
    }

    /**
     * Поддержка доступа как к массиву: $dto['name']
     * Полезно для гибкости в шаблонах
     */
    public function offsetGet(mixed $offset): string|int|array|null
    {
        return match ($offset) {
            'name' => $this->name,
            'version' => $this->version,
            'status' => $this->status,
            'type' => $this->type,
            'source' => $this->source,
            'requirements' => $this->requirements,
            'metadata' => $this->metadata,
            'installedAt' => $this->installedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'id' => $this->id,
            default => null,
        };
    }

    /**
     * Проверка существования ключа для доступа как к массиву
     */
    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, [
            'name', 'version', 'status', 'type', 'source',
            'requirements', 'metadata', 'installedAt',
            'createdAt', 'updatedAt', 'id',
        ], true);
    }

    /**
     * Required by ArrayAccess (не используется, но нужен для интерфейса)
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // DTO immutable — не позволяем менять значения
        throw new LogicException('PackageViewDataDTO is immutable');
    }

    /**
     * Required by ArrayAccess
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('PackageViewDataDTO is immutable');
    }
}
