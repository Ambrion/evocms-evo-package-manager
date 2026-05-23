<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\ValueObject;

use InvalidArgumentException;

readonly class PackageVersion
{
    public function __construct(private string $version)
    {
        if ($this->version === '') {
            throw new InvalidArgumentException('Package version cannot be empty.');
        }
    }

    public function toString(): string
    {
        return $this->version;
    }
}
