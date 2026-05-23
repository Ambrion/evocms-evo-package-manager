<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Application;

use EvolutionCMS\EvoPackageManager\Application\ListPackagesUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageVersion;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ListPackagesUseCaseTest extends TestCase
{
    #[Test]
    public function returns_all_packages_without_filters(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'limit' => 25,
                'offset' => 0,
                'sort' => 'name',
                'dir' => 'asc',
            ])
            ->willReturn([
                new InstalledPackage(
                    name: new PackageName('vendor/pkg-a'),
                    version: new PackageVersion('1.0.0')
                ),
                new InstalledPackage(
                    name: new PackageName('vendor/pkg-b'),
                    version: new PackageVersion('2.0.0')
                ),
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $result = $useCase->execute();

        self::assertCount(2, $result);
        self::assertSame('vendor/pkg-a', $result[0]->name()->toString());
    }

    #[Test]
    public function applies_filters_and_limits(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'status' => 'active',
                'type' => 'library',
                'limit' => 10,
                'offset' => 0,
                'sort' => 'name',
                'dir' => 'asc',
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $useCase->execute([
            'status' => 'active',
            'type' => 'library',
            'limit' => 10,
            'offset' => 0,
        ]);
    }

    #[Test]
    public function clamps_limit_to_minimum_when_too_low(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        // ✅ limit 0 → зажимается до 1
        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'limit' => 1,
                'offset' => 0,
                'sort' => 'name',
                'dir' => 'asc',
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $useCase->execute(['limit' => 0]);
    }

    #[Test]
    public function clamps_limit_to_maximum_when_too_high(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        // ✅ limit 200 → зажимается до 100
        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'limit' => 100,
                'offset' => 0,
                'sort' => 'name',
                'dir' => 'asc',
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $useCase->execute(['limit' => 200]);
    }

    #[Test]
    public function applies_default_sort_when_not_specified(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'limit' => 25,
                'offset' => 0,
                'sort' => 'name',
                'dir' => 'asc',
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $useCase->execute([]);
    }

    #[Test]
    public function applies_desc_sort_when_specified(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'limit' => 25,
                'offset' => 0,
                'sort' => 'version',
                'dir' => 'desc',
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $useCase->execute([
            'sort' => 'version',
            'dir' => 'desc',
        ]);
    }

    #[Test]
    public function applies_search_filter(): void
    {
        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        $repo->expects(self::once())
            ->method('findAll')
            ->with([
                'search' => 'feature',
                'limit' => 25,
                'offset' => 0,
                'sort' => 'name',
                'dir' => 'asc',
            ]);

        $useCase = new ListPackagesUseCase($repo);
        $useCase->execute(['search' => 'feature']);
    }
}
