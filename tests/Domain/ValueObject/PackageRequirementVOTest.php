<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Domain\ValueObject;

use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageVersion;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PackageRequirementVOTest extends TestCase
{
    #[Test]
    public function creates_from_strings_via_factory(): void
    {
        $req = PackageRequirement::fromStrings('evolutioncms/manager', '^3.0');

        self::assertSame('evolutioncms/manager', $req->package());
        self::assertSame('^3.0', $req->version());
        self::assertSame(['evolutioncms/manager' => '^3.0'], $req->toComposerArray());
    }

    #[Test]
    public function creates_directly_from_vo(): void
    {
        $name = new PackageName('vendor/pkg');
        $version = new PackageVersion('1.0.0');

        $req = new PackageRequirement($name, $version);
        self::assertInstanceOf(PackageName::class, $req->name);
        self::assertInstanceOf(PackageVersion::class, $req->version);
    }

    #[Test]
    public function rejects_invalid_package_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PackageRequirement::fromStrings('invalid-name', '^1.0');
    }

    #[Test]
    public function rejects_empty_version(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PackageRequirement::fromStrings('vendor/pkg', '');
    }
}
