<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\Port;

/**
 * Порт для синхронизации данных пакета из composer/installed.json в реестр.
 * Выносится в отдельный интерфейс для удобного тестирования оркестрации.
 */
interface PackageRegistrySyncerInterface
{
    /**
     * Синхронизирует данные пакета по имени
     *
     * @param  string  $packageName  Composer-style name: vendor/package
     */
    public function sync(string $packageName): void;
}
