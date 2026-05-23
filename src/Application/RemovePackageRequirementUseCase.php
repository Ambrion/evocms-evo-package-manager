<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Application;

use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ServiceProviderRemoverInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use RuntimeException;

readonly class RemovePackageRequirementUseCase
{
    public function __construct(
        private ComposerManifestRepositoryInterface $manifestRepo,
        private ComposerRunnerInterface $runner,
        private PackageRegistryRepositoryInterface $registry,
        private InstalledJsonParser $installedJsonParser,
        private ServiceProviderRemoverInterface $providerRemover,
    ) {}

    /**
     * Удаляет пакет: из composer.json, vendor/, реестра БД и списка провайдеров
     *
     * Порядок операций КРИТИЧЕН:
     * 1. Читаем провайдеры из installed.json (пока пакет ещё на диске)
     * 2. Удаляем файлы провайдеров
     * 3. Удаляем из composer.json и запускаем composer update
     * 4. Удаляем запись из реестра БД
     */
    public function execute(PackageRequirement $requirement): void
    {
        $packageName = $requirement->package();

        // ШАГ 1: Читаем данные пакета из installed.json ПОКА он ещё существует
        $packageData = $this->installedJsonParser->findPackageData($packageName);
        $providerClasses = [];

        if ($packageData !== null) {
            // Извлекаем провайдеры из extra.laravel.providers
            $providerClasses = $packageData['extra']['laravel']['providers'] ?? [];
        }

        // ШАГ 2: Удаляем требование из composer.json
        $requirements = $this->manifestRepo->loadRequirements();
        if (! isset($requirements[$packageName])) {
            throw new RuntimeException("Package {$packageName} not found in composer.json requirements");
        }

        unset($requirements[$packageName]);
        $this->manifestRepo->saveRequirements($requirements);

        // ШАГ 3: Удаляем файлы провайдеров ДО composer update
        $this->providerRemover->remove($providerClasses);

        // ШАГ 4: Запускаем composer update для физического удаления из vendor/
        $this->runner->update(noScripts: true);

        // ШАГ 5: Удаляем пакет из реестра БД
        $this->registry->remove(new PackageName($packageName));
    }
}
