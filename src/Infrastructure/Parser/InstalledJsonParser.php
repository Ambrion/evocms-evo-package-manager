<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Infrastructure\Parser;

use RuntimeException;

/**
 * Парсер composer/installed.json для извлечения данных установленного пакета.
 *
 * Это инфраструктурный сервис: знает о формате installed.json Composer,
 * но не зависит от доменных сущностей.
 */
readonly class InstalledJsonParser
{
    public function __construct(private string $corePath) {}

    /**
     * Находит данные пакета в installed.json по имени
     *
     * @return array<string, mixed>|null Данные пакета или null, если не найден
     *
     * @throws RuntimeException Если файл не найден или невалидный JSON
     */
    public function findPackageData(string $packageName): ?array
    {
        $packages = $this->loadPackages();

        foreach ($packages as $package) {
            if (($package['name'] ?? '') === $packageName) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Возвращает ВСЕ пакеты из installed.json для массовой синхронизации
     *
     * @return list<array<string, mixed>>
     *
     * @throws RuntimeException Если файл не найден или невалидный JSON
     */
    public function findAllPackages(): array
    {
        return $this->loadPackages();
    }

    /**
     * Приватный хелпер: загружает и валидирует installed.json
     * Единый источник истины для чтения файла
     *
     * @return list<array<string, mixed>>
     *
     * @throws RuntimeException
     */
    private function loadPackages(): array
    {
        $installedJsonPath = $this->corePath.'/vendor/composer/installed.json';

        if (! file_exists($installedJsonPath)) {
            throw new RuntimeException("Composer installed.json not found at {$installedJsonPath}");
        }

        $content = file_get_contents($installedJsonPath);
        if ($content === false) {
            throw new RuntimeException("Failed to read installed.json at {$installedJsonPath}");
        }

        if (! json_validate($content)) {
            throw new RuntimeException('Invalid JSON in installed.json');
        }

        $installed = json_decode($content, true);
        if (! is_array($installed)) {
            throw new RuntimeException('installed.json must contain an object');
        }

        // Composer может хранить packages как объект или массив (разные версии)
        $packages = $installed['packages'] ?? $installed['installed'] ?? [];

        // Нормализуем в список (array_values гарантирует индексацию)
        return array_is_list($packages) ? $packages : array_values($packages);
    }
}
