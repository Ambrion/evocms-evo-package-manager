<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Infrastructure;

use EvolutionCMS\EvoPackageManager\Domain\Port\ServiceProviderRemoverInterface;

readonly class FileSystemServiceProviderRemover implements ServiceProviderRemoverInterface
{
    public function __construct(
        private string $providersDirectory,
    ) {}

    public function remove(array $providerClasses): void
    {
        foreach ($providerClasses as $providerClass) {
            $fileName = $this->providerClassToFileName($providerClass);
            $providerPath = $this->providersDirectory.'/'.$fileName;

            if (file_exists($providerPath)) {
                unlink($providerPath);
            }
        }
    }

    /**
     * Преобразует класс в имя файла: EvolutionCMS\FeatureFlags\FeatureFlagsServiceProvider → FeatureFlagsServiceProvider.php
     */
    private function providerClassToFileName(string $providerClass): string
    {
        $parts = explode('\\', $providerClass);

        return end($parts).'.php';
    }

    /**
     * Возвращает путь к папке провайдеров
     */
    public static function getDefaultDirectory(): string
    {
        $corePath = defined('EVO_CORE_PATH') ? EVO_CORE_PATH : base_path('core/');

        return rtrim($corePath, '/\\').'/custom/config/app/providers';
    }
}
