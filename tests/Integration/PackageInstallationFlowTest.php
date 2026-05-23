<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Integration;

use EvolutionCMS\EvoPackageManager\Application\InstallPackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistrySyncerInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PackageInstallationFlowTest extends TestCase
{
    #[Test]
    public function full_installation_flow_updates_composer_and_registry(): void
    {
        // Arrange: моки всех портов
        $manifestRepo = $this->createMock(ComposerManifestRepositoryInterface::class);
        $manifestRepo->method('loadRequirements')->willReturn([]);
        $manifestRepo->expects(self::once())->method('saveRequirements')
            ->with(['vendor/pkg' => '^1.0']);

        $composerRunner = $this->createMock(ComposerRunnerInterface::class);
        $composerRunner->expects(self::once())->method('update');

        $registrySyncer = $this->createMock(PackageRegistrySyncerInterface::class);
        $registrySyncer->expects(self::once())->method('sync')
            ->with('vendor/pkg');

        // Act: создаём UseCase с внедрёнными зависимостями
        $useCase = new InstallPackageRequirementUseCase(
            $manifestRepo,
            $composerRunner,
            $registrySyncer
        );

        $requirement = PackageRequirement::fromStrings('vendor/pkg', '^1.0');
        $useCase->execute($requirement, shouldRunComposer: true);

        // Assert: все ожидания моков проверяются автоматически
        self::assertTrue(true);
    }
}
