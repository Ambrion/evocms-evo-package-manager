<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\Port;

interface ComposerManifestRepositoryInterface
{
    /**
     * @return array<string, string> Массив вида ['vendor/pkg' => '^1.0']
     */
    public function loadRequirements(): array;

    /**
     * @param  array<string, string>  $requirements
     */
    public function saveRequirements(array $requirements): void;
}
