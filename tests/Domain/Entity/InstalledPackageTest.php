<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Domain\Entity;

use DateTimeImmutable;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageVersion;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InstalledPackageTest extends TestCase
{
    #[Test]
    public function creates_immutable_installed_package(): void
    {
        $name = new PackageName('vendor/pkg');
        $version = new PackageVersion('1.2.3');
        $installedAt = new DateTimeImmutable;

        // Используем именованные параметры для читаемости
        $package = new InstalledPackage(
            name: $name,
            version: $version,
            status: 'active',
            type: 'library',
            source: 'packagist',
            installedAt: $installedAt,
            id: 1
        );

        self::assertSame(1, $package->id);
        self::assertSame('active', $package->status);
        self::assertSame($installedAt, $package->installedAt);
        self::assertNull($package->updatedAt);
    }

    #[Test]
    public function creates_new_package_without_id(): void
    {
        $name = new PackageName('vendor/new-pkg');
        $version = new PackageVersion('1.0.0');

        // Новые сущности создаются без ID
        $package = new InstalledPackage(
            name: $name,
            version: $version
        );

        self::assertNull($package->id);
        self::assertSame('active', $package->status); // Default value
        self::assertSame('library', $package->type);   // Default value
    }

    #[Test]
    public function update_version_returns_new_instance_and_preserves_original(): void
    {
        $name = new PackageName('vendor/pkg');
        $v1 = new PackageVersion('1.0.0');
        $v2 = new PackageVersion('1.1.0');

        $original = new InstalledPackage(name: $name, version: $v1, id: 1);
        $updated = $original->updateVersion($v2);

        self::assertNotSame($original, $updated, 'Must return new instance');
        self::assertSame('1.0.0', $original->version()->toString(), 'Original must be unchanged');
        self::assertSame('1.1.0', $updated->version()->toString(), 'New version applied');
        self::assertInstanceOf(DateTimeImmutable::class, $updated->updatedAt);
        self::assertNull($original->updatedAt);
        self::assertSame(1, $updated->id, 'ID must be preserved');
    }

    #[Test]
    public function creates_from_composer_installed_json(): void
    {
        $composerData = [
            'name' => 'evolutioncms/feature-flags',
            'version' => '1.2.3',
            'version_normalized' => '1.2.3.0',
            'source' => [
                'type' => 'git',
                'url' => 'https://github.com/evolutioncms/feature-flags.git',
                'reference' => 'abc123',
            ],
            'require' => ['php' => '^8.1', 'evolutioncms/evolution' => '^3.0'],
            'autoload' => ['psr-4' => ['Evo\\FeatureFlags\\' => 'src/']],
            'extra' => [
                'laravel' => [
                    'providers' => ['EvolutionCMS\\FeatureFlags\\FeatureFlagsServiceProvider'],
                ],
            ],
            'license' => ['MIT'],
            'authors' => [['name' => 'Igor Brikov', 'email' => 'igor@example.com']],
            'description' => 'Feature flags for EvolutionCMS',
            'homepage' => 'https://evolutioncms.com',
            'time' => '2024-05-21T10:00:00+00:00',
            'type' => 'library',
        ];

        $package = InstalledPackage::fromComposerInstalledJson($composerData);

        self::assertSame('evolutioncms/feature-flags', $package->name()->toString());
        self::assertSame('1.2.3', $package->version()->toString());
        self::assertSame('git', $package->source);
        self::assertArrayHasKey('php', $package->requirements);
        self::assertArrayHasKey('psr-4', $package->autoload);
        self::assertArrayHasKey('laravel', $package->extra);
        self::assertSame('MIT', $package->metadata['license']);
        self::assertSame('Igor Brikov', $package->metadata['author']);
        self::assertInstanceOf(DateTimeImmutable::class, $package->installedAt);
    }

    #[Test]
    public function creates_from_database_record(): void
    {
        $record = [
            'id' => 42,
            'name' => 'vendor/pkg',
            'version' => '2.0.0',
            'status' => 'active',
            'type' => 'evo-plugin',
            'source' => 'packagist',
            'requirements' => json_encode(['php' => '^8.2']),
            'autoload' => json_encode(['psr-4' => ['Vendor\\Pkg\\' => 'src/']]),
            'extra' => json_encode(['providers' => ['Vendor\\Pkg\\ServiceProvider']]),
            'metadata' => json_encode(['license' => 'MIT', 'author' => 'John']),
            'installed_at' => '2024-01-15 10:30:00',
            'created_at' => '2024-01-15 10:30:00',
            'updated_at' => '2024-05-21 12:00:00',
        ];

        $package = InstalledPackage::fromDatabaseRecord($record);

        self::assertSame(42, $package->id);
        self::assertSame('vendor/pkg', $package->name()->toString());
        self::assertSame('2.0.0', $package->version()->toString());
        self::assertSame('evo-plugin', $package->type);
        self::assertSame(['php' => '^8.2'], $package->requirements);
        self::assertInstanceOf(DateTimeImmutable::class, $package->installedAt);
        self::assertSame('2024-05-21 12:00:00', $package->updatedAt?->format('Y-m-d H:i:s'));
    }
}
