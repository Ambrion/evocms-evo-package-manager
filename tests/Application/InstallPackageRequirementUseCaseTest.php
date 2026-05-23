<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Application;

use EvolutionCMS\EvoPackageManager\Application\InstallPackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InstallPackageRequirementUseCaseTest extends TestCase
{
    #[Test]
    public function merges_requirement_and_triggers_composer_when_flag_enabled(): void
    {
        $repo = $this->createMock(ComposerManifestRepositoryInterface::class);
        $repo->method('loadRequirements')->willReturn(['evolutioncms/legacy' => '^2.0']);
        $repo->expects(self::once())->method('saveRequirements')
            ->with(['evolutioncms/legacy' => '^2.0', 'evolutioncms/manager' => '^3.0']);

        $runner = $this->createMock(ComposerRunnerInterface::class);
        $runner->expects(self::once())->method('update');

        $useCase = new InstallPackageRequirementUseCase($repo, $runner);

        // ✅ Используем фабрику для плавной миграции из строк
        $requirement = PackageRequirement::fromStrings('evolutioncms/manager', '^3.0');

        $useCase->execute($requirement, shouldRunComposer: true);
    }

    #[Test]
    public function saves_requirement_but_skips_composer_when_flag_disabled(): void
    {
        $repo = $this->createMock(ComposerManifestRepositoryInterface::class);
        $repo->method('loadRequirements')->willReturn([]);
        $repo->expects(self::once())->method('saveRequirements')
            ->with(['vendor/pkg' => '*']);

        $runner = $this->createMock(ComposerRunnerInterface::class);
        $runner->expects(self::never())->method('update');

        $useCase = new InstallPackageRequirementUseCase($repo, $runner);
        $useCase->execute(PackageRequirement::fromStrings('vendor/pkg', '*'), shouldRunComposer: false);
    }
}
