<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Infrastructure\Repository;

$fakeBuilderPath = __DIR__.'/FakeQueryBuilder.php';
if (file_exists($fakeBuilderPath)) {
    require_once $fakeBuilderPath;
}

use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageVersion;
use EvolutionCMS\EvoPackageManager\Infrastructure\Database\Models\EvoPackage;
use EvolutionCMS\EvoPackageManager\Infrastructure\Repository\EloquentPackageRegistryRepository;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EloquentPackageRegistryRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    #[Test]
    public function finds_package_by_name(): void
    {
        $fakeBuilder = new FakeQueryBuilder;

        $modelStub = (new EvoPackage)->setRawAttributes([
            'id' => 42,
            'name' => 'vendor/pkg',
            'version' => '1.0.0',
            'status' => 'active',
            'type' => 'library',
            'source' => 'packagist',
            'requirements' => json_encode(['php' => '^8.1']),
            'autoload' => json_encode([]),
            'extra' => json_encode([]),
            'metadata' => json_encode([]),
            'installed_at' => null,
            'created_at' => null,
            'updated_at' => null,
        ], true);

        $fakeBuilder->returnFirst = $modelStub;

        $modelQueryStub = $this->createStub(EvoPackage::class);
        $modelQueryStub->method('newQuery')->willReturn($fakeBuilder);

        $repository = new EloquentPackageRegistryRepository($modelQueryStub);
        $package = $repository->findByName(new PackageName('vendor/pkg'));

        self::assertNotNull($package);
        self::assertSame('vendor/pkg', $package->name()->toString());
        self::assertSame('1.0.0', $package->version()->toString());
        self::assertSame(['php' => '^8.1'], $package->requirements);
        self::assertTrue($fakeBuilder->hasWhere('name', 'vendor/pkg'));
    }

    #[Test]
    public function returns_null_when_not_found(): void
    {
        $fakeBuilder = new FakeQueryBuilder;
        $fakeBuilder->returnFirst = null;

        $modelStub = $this->createStub(EvoPackage::class);
        $modelStub->method('newQuery')->willReturn($fakeBuilder);

        $repository = new EloquentPackageRegistryRepository($modelStub);
        $package = $repository->findByName(new PackageName('non/existent'));

        self::assertNull($package);
    }

    #[Test]
    public function saves_new_package_with_update_or_insert(): void
    {
        $fakeBuilder = new FakeQueryBuilder;

        $modelStub = $this->createStub(EvoPackage::class);
        $modelStub->method('newQuery')->willReturn($fakeBuilder);

        $repository = new EloquentPackageRegistryRepository($modelStub);

        $package = new InstalledPackage(
            name: new PackageName('vendor/new'),
            version: new PackageVersion('2.0.0'),
            source: 'packagist',
            requirements: ['php' => '^8.2', 'ext-json' => '*']
        );

        $repository->save($package);

        self::assertSame('vendor/new', $fakeBuilder->updateData['search']['name']);
        self::assertSame('2.0.0', $fakeBuilder->updateData['values']['version']);
        $expectedRequirements = json_encode(['php' => '^8.2', 'ext-json' => '*'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        self::assertSame($expectedRequirements, $fakeBuilder->updateData['values']['requirements']);
    }

    #[Test]
    public function updates_existing_package_on_save(): void
    {
        $fakeBuilder = new FakeQueryBuilder;

        $modelStub = $this->createStub(EvoPackage::class);
        $modelStub->method('newQuery')->willReturn($fakeBuilder);

        $repository = new EloquentPackageRegistryRepository($modelStub);

        $package = new InstalledPackage(
            name: new PackageName('vendor/existing'),
            version: new PackageVersion('1.0.0')
        );
        $updated = $package->updateVersion(new PackageVersion('1.1.0'));

        $repository->save($updated);

        self::assertSame('vendor/existing', $fakeBuilder->updateData['search']['name']);
        self::assertSame('1.1.0', $fakeBuilder->updateData['values']['version']);
        self::assertNotNull($fakeBuilder->updateData['values']['updated_at']);
    }
}
