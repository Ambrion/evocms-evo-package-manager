<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\ValueObject;

readonly class PackageRequirement
{
    public function __construct(
        public PackageName $name,
        public PackageVersion $version,
    ) {}

    public function package(): string
    {
        return $this->name->toString();
    }

    public function version(): string
    {
        return $this->version->toString();
    }

    public function toComposerArray(): array
    {
        return [$this->package() => $this->version()];
    }

    /**
     * Фабричный метод для обратной совместимости с CLI/UseCase
     */
    public static function fromStrings(string $package, string $version): self
    {
        return new self(new PackageName($package), new PackageVersion($version));
    }
}
