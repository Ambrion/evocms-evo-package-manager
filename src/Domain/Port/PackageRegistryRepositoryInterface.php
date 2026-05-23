<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\Port;

use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;

interface PackageRegistryRepositoryInterface
{
    /**
     * Находит пакет из реестра по имени.
     */
    public function findByName(PackageName $name): ?InstalledPackage;

    /**
     * Возвращает список установленных пакетов с опциональной фильтрацией
     *
     * @param  array{status?: string, type?: string, source?: string, limit?: int, offset?: int}  $filters
     * @return list<InstalledPackage>
     */
    public function findAll(array $filters = []): array;

    /**
     * Сохраняет пакет в реестре.
     */
    public function save(InstalledPackage $package): void;

    /**
     * Удаляет пакет из реестра по имени
     */
    public function remove(PackageName $name): void;

    public function count(array $filters = []): int;

    public function getDistinctValues(string $column): array;
}
