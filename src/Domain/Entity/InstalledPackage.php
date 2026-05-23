<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\Entity;

use DateMalformedStringException;
use DateTimeImmutable;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageVersion;

readonly class InstalledPackage
{
    /**
     * @param  int|string|null  $id  Идентификатор из БД (null для новых сущностей)
     */
    public function __construct(
        public PackageName $name,
        public PackageVersion $version,
        public string $status = 'active',
        public string $type = 'library',
        public string $source = 'packagist',
        public array $requirements = [],
        public array $autoload = [],
        public array $extra = [],
        public array $metadata = [],
        public ?DateTimeImmutable $installedAt = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public int|string|null $id = null, // Опциональный параметр в конце
    ) {}

    public function name(): PackageName
    {
        return $this->name;
    }

    public function version(): PackageVersion
    {
        return $this->version;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Возвращает новую immutable-копию с обновлённой версией и timestamp
     */
    public function updateVersion(PackageVersion $newVersion): self
    {
        return new self(
            name: $this->name,
            version: $newVersion,
            status: $this->status,
            type: $this->type,
            source: $this->source,
            requirements: $this->requirements,
            autoload: $this->autoload,
            extra: $this->extra,
            metadata: $this->metadata,
            installedAt: $this->installedAt,
            createdAt: $this->createdAt ?? new DateTimeImmutable,
            updatedAt: new DateTimeImmutable,
            id: $this->id
        );
    }

    /**
     * Фабрика: создание сущности из данных composer/installed.json
     *
     * @param array{
     *     name: string,
     *     version: string,
     *     source?: array{type: string},
     *     require?: array<string, string>,
     *     autoload?: array,
     *     extra?: array,
     *     license?: list<string>,
     *     authors?: list<array{name: string, email?: string}>,
     *     description?: string,
     *     homepage?: string,
     *     time?: string,
     *     type?: string
     * } $data
     *
     * @throws DateMalformedStringException
     */
    public static function fromComposerInstalledJson(array $data): self
    {
        $now = new DateTimeImmutable;

        // Извлекаем источник
        $source = $data['source']['type'] ?? $data['dist']['type'] ?? 'unknown';

        // Извлекаем метаданные
        $metadata = [
            'author' => $data['authors'][0]['name'] ?? null,
            'email' => $data['authors'][0]['email'] ?? null,
            'description' => $data['description'] ?? null,
            'homepage' => $data['homepage'] ?? null,
            'license' => $data['license'][0] ?? null,
        ];
        $metadata = array_filter($metadata); // Убираем null

        // Парсим время установки из Composer 'time' поля
        $installedAt = isset($data['time'])
            ? new DateTimeImmutable($data['time'])
            : $now;

        return new self(
            name: new PackageName($data['name']),
            version: new PackageVersion($data['version']),
            status: 'active',
            type: $data['type'] ?? 'library',
            source: $source,
            requirements: $data['require'] ?? [],
            autoload: $data['autoload'] ?? [],
            extra: $data['extra'] ?? [],
            metadata: $metadata,
            installedAt: $installedAt,
            createdAt: $now,
            updatedAt: $now
        );
    }

    /**
     * Фабрика: гидратация сущности из записи БД
     *
     * Примечание: при использовании через Eloquent-модель с $casts,
     * JSON-поля (requirements, autoload, extra, metadata) уже будут массивами.
     * Этот метод также безопасен для сырых массивов из тестов/интеграций.
     *
     * @param array{
     *     id?: int|string,
     *     name: string,
     *     version: string,
     *     status: string,
     *     type: string,
     *     source: string,
     *     requirements?: array|string|null,
     *     autoload?: array|string|null,
     *     extra?: array|string|null,
     *     metadata?: array|string|null,
     *     installed_at?: string|DateTimeImmutable|null,
     *     created_at?: string|DateTimeImmutable|null,
     *     updated_at?: string|DateTimeImmutable|null
     * } $record
     *
     * @throws DateMalformedStringException
     */
    public static function fromDatabaseRecord(array $record): self
    {
        // Безопасное декодирование: если $casts сработал — уже массив, иначе декодируем
        $decode = fn ($value): array => match (true) {
            is_array($value) => $value,
            is_string($value) && $value !== '' => json_decode($value, true) ?: [],
            default => [],
        };

        // Безопасное преобразование дат
        $toDateTime = fn ($value): ?DateTimeImmutable => match (true) {
            $value instanceof DateTimeImmutable => $value,
            is_string($value) && $value !== '' => new DateTimeImmutable($value),
            default => null,
        };

        return new self(
            name: new PackageName($record['name']),
            version: new PackageVersion($record['version']),
            status: $record['status'] ?? 'active',
            type: $record['type'] ?? 'library',
            source: $record['source'] ?? 'packagist',
            requirements: $decode($record['requirements'] ?? null),
            autoload: $decode($record['autoload'] ?? null),
            extra: $decode($record['extra'] ?? null),
            metadata: $decode($record['metadata'] ?? null),
            installedAt: $toDateTime($record['installed_at'] ?? null),
            createdAt: $toDateTime($record['created_at'] ?? null),
            updatedAt: $toDateTime($record['updated_at'] ?? null),
            id: $record['id'] ?? null
        );
    }
}
