<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Application;

use DateMalformedStringException;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistrySyncerInterface;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Синхронизирует данные установленного пакета из composer/installed.json в реестр БД.
 *
 * Этот UseCase вызывается ПОСЛЕ успешного выполнения composer update.
 * Если пакет не найден в installed.json — это не ошибка (пакет может быть ещё не установлен).
 */
readonly class SyncPackageRegistryUseCase implements PackageRegistrySyncerInterface
{
    public function __construct(
        private InstalledJsonParser $parser,
        private PackageRegistryRepositoryInterface $registry,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Синхронизирует данные пакета по имени
     *
     * @param  string  $packageName  Composer-style name: vendor/package
     *
     * @throws RuntimeException|DateMalformedStringException Если parser не может прочитать installed.json
     */
    public function sync(string $packageName): void
    {
        try {
            $data = $this->parser->findPackageData($packageName);

            if ($data === null) {
                // Пакет не найден в installed.json — возможно, он ещё не установлен
                // Это не ошибка, просто логируем для отладки
                $this->logger?->debug("Package {$packageName} not found in installed.json — skipping registry sync");

                return;
            }

            // Парсим данные Composer в доменную сущность
            $package = InstalledPackage::fromComposerInstalledJson($data);

            // Сохраняем в реестр (UPSERT)
            $this->registry->save($package);

            $this->logger?->info("Package registry synced: {$packageName}@{$package->version()->toString()}");

        } catch (RuntimeException $e) {
            // Критическая ошибка парсинга — пробрасываем выше для обработки в CLI
            $this->logger?->error("Failed to sync package registry: {$e->getMessage()}", [
                'package' => $packageName,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Полная синхронизация реестра с installed.json
     * Удаляет пакеты, которые есть в реестре, но отсутствуют в installed.json
     */
    public function syncAll(): void
    {
        $installedPackages = $this->parser->findAllPackages();
        $installedNames = array_column($installedPackages, 'name');

        // Получаем все пакеты из реестра
        $registryPackages = $this->registry->findAll();

        // Удаляем пакеты, которых нет в installed.json
        foreach ($registryPackages as $registryPackage) {
            $name = $registryPackage->name()->toString();
            if (! in_array($name, $installedNames, true)) {
                $this->registry->remove($registryPackage->name());
                $this->logger?->info("Removed from registry (not installed): {$name}");
            }
        }

        // Синхронизируем все установленные пакеты
        foreach ($installedPackages as $packageData) {
            $name = $packageData['name'] ?? null;
            if ($name !== null) {
                try {
                    $this->sync($name);
                } catch (RuntimeException $e) {
                    $this->logger?->error("Failed to sync {$name}: {$e->getMessage()}");
                }
            }
        }
    }
}
