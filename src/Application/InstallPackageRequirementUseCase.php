<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Application;

use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistrySyncerInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;

readonly class InstallPackageRequirementUseCase
{
    public function __construct(
        private ComposerManifestRepositoryInterface $repository,
        private ComposerRunnerInterface $runner,
        private ?PackageRegistrySyncerInterface $registrySyncer = null,
    ) {}

    /**
     * Устанавливает пакет: добавляет в composer.json и запускает composer update
     */
    public function execute(
        PackageRequirement $requirement,
        bool $shouldRunComposer = true
    ): void {
        // 1. Добавляем требование в composer.json
        $requirements = $this->repository->loadRequirements();
        $requirements[$requirement->package()] = $requirement->version();
        $this->repository->saveRequirements($requirements);

        // 2. Запускаем composer update, если нужно
        if ($shouldRunComposer) {
            $this->runner->update();

            // 3. Синхронизируем реестр
            $this->registrySyncer?->sync($requirement->package());
        }
    }
}
