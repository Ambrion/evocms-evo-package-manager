<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Presentation\Command;

use EvolutionCMS\EvoPackageManager\Application\SyncPackageRegistryUseCase;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use Illuminate\Console\Command;
use RuntimeException;

class SyncPackageRegistryCommand extends Command
{
    protected $signature = 'evo:package:sync {package? : Package name to sync (all if omitted)}';

    protected $description = 'Sync installed packages from composer/installed.json to registry';

    public function __construct(
        private readonly SyncPackageRegistryUseCase $syncUseCase,
        private readonly InstalledJsonParser $parser,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $packageName = $this->argument('package');

        if ($packageName !== null) {
            // Синхронизация одного пакета
            try {
                $this->syncUseCase->sync($packageName);
                $this->info("✓ Synced: {$packageName}");

                return self::SUCCESS;
            } catch (RuntimeException $e) {
                $this->error("✗ Failed to sync {$packageName}: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        // Синхронизация ВСЕХ пакетов через парсер
        try {
            $allPackages = $this->parser->findAllPackages();
        } catch (RuntimeException $e) {
            $this->error("Failed to read installed.json: {$e->getMessage()}");

            return self::FAILURE;
        }

        $success = 0;
        $failed = 0;

        foreach ($allPackages as $package) {
            $name = $package['name'] ?? null;
            if ($name === null) {
                continue; // Пропускаем невалидные записи
            }

            try {
                $this->syncUseCase->sync($name);
                $version = $package['version'] ?? 'unknown';
                $this->line("✓ {$name}@{$version}");
                $success++;
            } catch (RuntimeException $e) {
                $this->warn("✗ {$name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Sync complete: {$success} succeeded, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
