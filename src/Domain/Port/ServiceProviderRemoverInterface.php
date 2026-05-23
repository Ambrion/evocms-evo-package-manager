<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\Port;

/**
 * Контракт для удаления файлов сервис-провайдеров
 */
interface ServiceProviderRemoverInterface
{
    /**
     * Удаляет файлы провайдеров по их класс-неймам
     *
     * @param  string[]  $providerClasses  Например: ['EvolutionCMS\FeatureFlags\FeatureFlagsServiceProvider']
     */
    public function remove(array $providerClasses): void;
}
