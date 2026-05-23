<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Presentation\Command;

use EvolutionCMS\EvoPackageManager\Application\InstallPackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use Illuminate\Console\Command;
use Throwable;

class InstallPackageRequireCommand extends Command
{
    protected $signature = 'evo:package:install
        {package : Package name (vendor/package)}
        {version : Version constraint}
        {--no-composer : Skip composer update}
        {--skip-post-install : Skip post-install steps}';

    protected $description = 'Install composer package with post-install automation';

    public function __construct(
        private readonly InstallPackageRequirementUseCase $useCase,
        private readonly ?InstalledJsonParser $installedJsonParser = null,
        private readonly ?ComposerRunnerInterface $composerRunner = null,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $package = $this->argument('package');
            $version = $this->argument('version');
            $runComposer = ! $this->option('no-composer');
            $skipPostInstall = (bool) $this->option('skip-post-install');

            // Создаём требование и выполняем установку
            $requirement = PackageRequirement::fromStrings($package, $version);

            $this->useCase->execute($requirement, $runComposer);

            // Выполняем пост-инсталляцию (если не пропущена)
            if (! $skipPostInstall) {
                $this->runPostInstall($package);
            }

            // Запуск dump-autoload через ComposerRunner
            if ($this->composerRunner) {
                $result = $this->composerRunner->runCommand('dump-autoload', [
                    '--optimize' => true,
                    '--no-interaction' => true,
                ]);
                if ($result['exitCode'] === 0) {
                    $this->info('✓ Composer autoload regenerated');
                } else {
                    $this->warn('⚠ composer dump-autoload failed: '.$result['output']);
                }
            }

            // Вывод результата
            $this->outputResult($package, $version, $runComposer, ! $skipPostInstall);

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Failed to install package: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Выполняет пост-инсталляционные шаги для пакета
     */
    protected function runPostInstall(string $packageName): void
    {
        $providerClass = $this->getServiceProviderClass($packageName);
        if (! $providerClass) {
            $this->warn('⚠ No service provider found for post-install, skipping...');

            return;
        }

        $this->info("Running post-install for {$packageName} with provider: {$providerClass}");

        // 1. Регистрируем провайдер, не нашёл как иначе в потоке команды запустить vendor:publish и migrate корректно
        if (class_exists($providerClass) && ! app()->getProvider($providerClass)) {
            $provider = new $providerClass(app());
            $provider->register();
            $provider->boot();
            $this->info('✓ Service provider registered');
        }

        // 2. Публикуем файлы
        $exitCode = $this->call('vendor:publish', [
            '--provider' => $providerClass,
            '--force' => true,
        ]);
        if ($exitCode === 0) {
            $this->info('✓ Assets published');
        } else {
            $this->warn('⚠ vendor:publish failed, continuing...');
        }

        // 3. Запускаем миграции
        $exitCode = $this->call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
        if ($exitCode === 0) {
            $this->info('✓ Migrations executed');
        } else {
            $this->warn('⚠ migrate failed');
        }

        $exitCode = $this->call('cache:clear');
        if ($exitCode === 0) {
            $this->info('✓ Cache clear executed');
        } else {
            $this->warn('⚠ Cache clear failed');
        }
    }

    /**
     * Получает класс сервис-провайдера из установленных данных пакета
     */
    private function getServiceProviderClass(string $packageName): ?string
    {
        if (! $this->installedJsonParser) {
            return null;
        }

        $packageData = $this->installedJsonParser->findPackageData($packageName);
        if ($packageData) {
            $providers = $packageData['extra']['laravel']['providers'] ?? [];

            return $providers[0] ?? null;
        }

        return null;
    }

    /**
     * Выводит итоговое сообщение
     */
    private function outputResult(string $package, string $version, bool $composerRun, bool $postInstallRun): void
    {
        $messages = [];
        if ($composerRun) {
            $messages[] = 'Composer updated';
        } else {
            $messages[] = 'Composer update skipped';
        }
        if ($postInstallRun) {
            $messages[] = 'Post-install steps executed';
        } else {
            $messages[] = 'Post-install steps skipped';
        }
        $this->info("Package requirement '{$package}: {$version}' added. ".implode(', ', $messages).'.');
    }
}
