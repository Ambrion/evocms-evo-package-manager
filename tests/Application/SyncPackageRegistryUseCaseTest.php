<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Application;

use EvolutionCMS\EvoPackageManager\Application\SyncPackageRegistryUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SyncPackageRegistryUseCaseTest extends TestCase
{
    #[Test]
    public function syncs_package_when_found_in_installed_json(): void
    {
        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->expects(self::once())->method('findPackageData')
            ->with('vendor/pkg')
            ->willReturn([
                'name' => 'vendor/pkg',
                'version' => '1.2.3',
                'source' => ['type' => 'git'],
                'require' => ['php' => '^8.1'],
                'autoload' => ['psr-4' => ['Vendor\\Pkg\\' => 'src/']],
                'extra' => ['laravel' => ['providers' => ['...']]],
                'license' => ['MIT'],
                'authors' => [['name' => 'Author']],
                'type' => 'library',
            ]);

        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);
        $repo->expects(self::once())->method('save')
            ->with(self::callback(function (InstalledPackage $pkg) {
                return $pkg->name()->toString() === 'vendor/pkg'
                    && $pkg->version()->toString() === '1.2.3'
                    && $pkg->source === 'git';
            }));

        $useCase = new SyncPackageRegistryUseCase($parser, $repo);
        $useCase->sync('vendor/pkg');
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function skips_sync_when_package_not_in_installed_json(): void
    {
        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->method('findPackageData')->willReturn(null);

        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);
        $repo->expects(self::never())->method('save'); // Явно указываем, что save не должен вызываться

        $useCase = new SyncPackageRegistryUseCase($parser, $repo);

        // Явная ассерция вместо expectNotToPerformAssertions()
        $useCase->sync('vendor/not-installed-yet');
        self::assertTrue(true, 'Sync completed without errors when package not found');
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function throws_when_parser_fails(): void
    {
        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->method('findPackageData')
            ->willThrowException(new RuntimeException('installed.json corrupted'));

        $repo = $this->createMock(PackageRegistryRepositoryInterface::class);

        $useCase = new SyncPackageRegistryUseCase($parser, $repo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('installed.json corrupted');

        $useCase->sync('vendor/pkg');
    }
}
