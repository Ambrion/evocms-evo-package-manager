<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\ValueObject;

use InvalidArgumentException;

readonly class PackageName
{
    private const string REGEX = '/^[a-z0-9]([a-z0-9\.\-]*[a-z0-9])?\/[a-z0-9]([a-z0-9\.\-]*[a-z0-9])?$/';

    public function __construct(private string $name)
    {
        if (! preg_match(self::REGEX, $this->name)) {
            throw new InvalidArgumentException("Invalid package name format: {$this->name}");
        }
    }

    public function toString(): string
    {
        return $this->name;
    }
}
