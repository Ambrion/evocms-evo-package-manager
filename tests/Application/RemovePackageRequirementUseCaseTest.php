<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Application;

use EvolutionCMS\EvoPackageManager\Application\RemovePackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ServiceProviderRemoverInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RemovePackageRequirementUseCaseTest extends TestCase
{
    #[Test]
    public function removes_package_with_providers_from_all_locations(): void
    {
        // Arrange: моки зависимостей
        $manifestRepo = $this->createMock(ComposerManifestRepositoryInterface::class);
        $runner = $this->createMock(ComposerRunnerInterface::class);
        $registry = $this->createMock(PackageRegistryRepositoryInterface::class);
        $parser = $this->createMock(InstalledJsonParser::class);
        $providerRemover = $this->createMock(ServiceProviderRemoverInterface::class);

        // Настройка парсера: возвращает данные с провайдерами
        $parser->method('findPackageData')
            ->with('vendor/test-pkg')
            ->willReturn([
                'name' => 'vendor/test-pkg',
                'version' => '1.0.0',
                'extra' => [
                    'laravel' => [
                        'providers' => ['Vendor\\TestPkg\\TestPkgServiceProvider'],
                    ],
                ],
            ]);

        // Настройка манифеста
        $manifestRepo->method('loadRequirements')
            ->willReturn(['vendor/test-pkg' => '^1.0', 'other/pkg' => '^2.0']);
        $manifestRepo->expects(self::once())
            ->method('saveRequirements')
            ->with(['other/pkg' => '^2.0']);

        // Настройка runner
        $runner->expects(self::once())
            ->method('update')
            ->with(true); // noScripts: true

        // Настройка реестра
        $registry->expects(self::once())
            ->method('remove')
            ->with(new PackageName('vendor/test-pkg'));

        // ✅ Настройка сервиса удаления провайдеров
        $providerRemover->expects(self::once())
            ->method('remove')
            ->with(['Vendor\\TestPkg\\TestPkgServiceProvider']);

        // Act
        $useCase = new RemovePackageRequirementUseCase(
            $manifestRepo,
            $runner,
            $registry,
            $parser,
            $providerRemover
        );
        $requirement = PackageRequirement::fromStrings('vendor/test-pkg', '^1.0');
        $useCase->execute($requirement);

        // Assert: все ожидания проверены моками
        self::assertTrue(true);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function removes_package_without_providers(): void
    {
        $manifestRepo = $this->createMock(ComposerManifestRepositoryInterface::class);
        $runner = $this->createMock(ComposerRunnerInterface::class);
        $registry = $this->createMock(PackageRegistryRepositoryInterface::class);
        $parser = $this->createMock(InstalledJsonParser::class);
        $providerRemover = $this->createMock(ServiceProviderRemoverInterface::class);

        // Пакет без провайдеров
        $parser->method('findPackageData')
            ->willReturn([
                'name' => 'vendor/simple-pkg',
                'extra' => [],
            ]);

        $manifestRepo->method('loadRequirements')
            ->willReturn(['vendor/simple-pkg' => '*']);
        $manifestRepo->expects(self::once())
            ->method('saveRequirements')
            ->with([]);

        $runner->expects(self::once())->method('update')->with(true);
        $registry->expects(self::once())->method('remove')
            ->with(new PackageName('vendor/simple-pkg'));

        // ✅ Сервис не должен вызываться, если нет провайдеров
        $providerRemover->expects(self::once())
            ->method('remove')
            ->with([]);

        $useCase = new RemovePackageRequirementUseCase(
            $manifestRepo,
            $runner,
            $registry,
            $parser,
            $providerRemover
        );
        $useCase->execute(PackageRequirement::fromStrings('vendor/simple-pkg', '*'));

        self::assertTrue(true);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function throws_when_package_not_in_manifest(): void
    {
        $manifestRepo = $this->createMock(ComposerManifestRepositoryInterface::class);
        $manifestRepo->method('loadRequirements')->willReturn(['other/pkg' => '^2.0']);

        $runner = $this->createMock(ComposerRunnerInterface::class);
        $registry = $this->createMock(PackageRegistryRepositoryInterface::class);
        $parser = $this->createMock(InstalledJsonParser::class);
        $providerRemover = $this->createMock(ServiceProviderRemoverInterface::class);

        $useCase = new RemovePackageRequirementUseCase(
            $manifestRepo,
            $runner,
            $registry,
            $parser,
            $providerRemover
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Package vendor/missing not found in composer.json');

        $requirement = PackageRequirement::fromStrings('vendor/missing', '^1.0');
        $useCase->execute($requirement);
    }
}
