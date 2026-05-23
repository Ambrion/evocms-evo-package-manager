<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager;

use EvolutionCMS\EvoPackageManager\Application\InstallPackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Application\ListPackagesUseCase;
use EvolutionCMS\EvoPackageManager\Application\RemovePackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Application\SyncPackageRegistryUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistrySyncerInterface;
use EvolutionCMS\EvoPackageManager\Domain\Port\ServiceProviderRemoverInterface;
use EvolutionCMS\EvoPackageManager\Infrastructure\Database\Models\EvoPackage;
use EvolutionCMS\EvoPackageManager\Infrastructure\FileSystemServiceProviderRemover;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use EvolutionCMS\EvoPackageManager\Infrastructure\Repository\EloquentPackageRegistryRepository;
use EvolutionCMS\EvoPackageManager\Infrastructure\Repository\FileComposerManifestRepository;
use EvolutionCMS\EvoPackageManager\Infrastructure\Runner\ConsoleComposerRunner;
use EvolutionCMS\EvoPackageManager\Presentation\Command\InstallPackageRequireCommand;
use EvolutionCMS\EvoPackageManager\Presentation\Command\ListPackagesCommand;
use EvolutionCMS\EvoPackageManager\Presentation\Command\RemovePackageRequireCommand;
use EvolutionCMS\EvoPackageManager\Presentation\Command\SyncPackageRegistryCommand;
use EvolutionCMS\EvoPackageManager\Presentation\Http\Controllers\Admin\PackageAdminController;
use EvolutionCMS\ServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Psr\Log\LoggerInterface;

class EvoPackageManagerServiceProvider extends ServiceProvider
{
    protected string $namespace = 'evoPackageManager';

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', $this->namespace);
        $this->loadViewsFrom(__DIR__.'/../resources/views', $this->namespace);
        $this->loadRoutesFrom(__DIR__.'/../routes/module.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPackageRequireCommand::class,
                SyncPackageRegistryCommand::class,
                ListPackagesCommand::class,
                RemovePackageRequireCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $corePath = $this->resolveCorePath();
        $manifestPath = $corePath.'custom/composer.json';
        $composerHome = $corePath.'composer';

        // 1. Composer Manifest Repository
        $this->app->bind(ComposerManifestRepositoryInterface::class, fn () => new FileComposerManifestRepository($manifestPath));

        // 2. Composer Runner
        $this->app->bind(ComposerRunnerInterface::class, fn () => new ConsoleComposerRunner($corePath, $composerHome));

        // 3. EvoPackage Model
        $this->app->bind(EvoPackage::class, fn ($app) => $this->createEvoPackageModel($app));

        // 4. Package Registry Repository
        $this->app->bind(PackageRegistryRepositoryInterface::class, fn ($app) => new EloquentPackageRegistryRepository($app->make(EvoPackage::class)));

        // 5. Installed JSON Parser
        $this->app->bind(InstalledJsonParser::class, fn () => new InstalledJsonParser($corePath));

        // 6. PackageRegistrySyncerInterface → SyncPackageRegistryUseCase
        // КРИТИЧНО: должно быть ДО использования в InstallPackageRequirementUseCase
        $this->app->bind(PackageRegistrySyncerInterface::class, fn ($app) => $app->make(SyncPackageRegistryUseCase::class));

        // 7. ServiceProviderRemover
        $this->app->bind(ServiceProviderRemoverInterface::class, fn () => new FileSystemServiceProviderRemover(
            FileSystemServiceProviderRemover::getDefaultDirectory()
        ));

        // 8. SyncPackageRegistryUseCase
        $this->app->bind(SyncPackageRegistryUseCase::class, fn ($app) => new SyncPackageRegistryUseCase(
            $app->make(InstalledJsonParser::class),
            $app->make(PackageRegistryRepositoryInterface::class),
            $this->resolveLogger($app)
        ));

        // 9. ListPackagesUseCase
        $this->app->bind(ListPackagesUseCase::class, fn ($app) => new ListPackagesUseCase($app->make(PackageRegistryRepositoryInterface::class)));

        // 10. InstallPackageRequirementUseCase
        $this->app->bind(InstallPackageRequirementUseCase::class, fn ($app) => new InstallPackageRequirementUseCase(
            $app->make(ComposerManifestRepositoryInterface::class),
            $app->make(ComposerRunnerInterface::class),
            $app->make(PackageRegistrySyncerInterface::class)
        ));

        // 11. RemovePackageRequirementUseCase
        $this->app->bind(RemovePackageRequirementUseCase::class, fn ($app) => new RemovePackageRequirementUseCase(
            $app->make(ComposerManifestRepositoryInterface::class),
            $app->make(ComposerRunnerInterface::class),
            $app->make(PackageRegistryRepositoryInterface::class),
            $app->make(InstalledJsonParser::class),
            $app->make(ServiceProviderRemoverInterface::class)
        ));

        // 12. Команды
        $this->app->singleton(InstallPackageRequireCommand::class, fn ($app) => new InstallPackageRequireCommand(
            $app->make(InstallPackageRequirementUseCase::class),
            $app->bound(InstalledJsonParser::class) ? $app->make(InstalledJsonParser::class) : null,
            $app->bound(ComposerRunnerInterface::class) ? $app->make(ComposerRunnerInterface::class) : null
        ));
        $this->app->singleton(SyncPackageRegistryCommand::class);
        $this->app->singleton(ListPackagesCommand::class);
        $this->app->singleton(RemovePackageRequireCommand::class, fn ($app) => new RemovePackageRequireCommand(
            $app->make(RemovePackageRequirementUseCase::class),
            $app->bound(ComposerRunnerInterface::class) ? $app->make(ComposerRunnerInterface::class) : null
        ));

        if (method_exists($this->app, 'registerRoutingModule')) {
            $this->app->registerRoutingModule(
                'Evo Package Manager',
                __DIR__.'/../routes/module.php',
                'fa fa-cube'
            );
        }

        // PackageAdminController
        $this->app->bind(PackageAdminController::class, fn ($app) => new PackageAdminController(
            $app->make(ListPackagesUseCase::class),
            $app->make(InstallPackageRequirementUseCase::class),
            $app->make(RemovePackageRequirementUseCase::class),
            $app->make(SyncPackageRegistryUseCase::class)
        ));
    }

    /**
     * Резолвит путь к core/ с многоуровневым фоллбэком
     */
    private function resolveCorePath(): string
    {
        if (defined('EVO_CORE_PATH')) {
            return rtrim(EVO_CORE_PATH, '/\\').'/';
        }

        $config = $this->app->make('config');
        if ($config instanceof ConfigContract) {
            $path = $config->get('evo.core_path');
            if ($path && file_exists($path)) {
                return rtrim($path, '/\\').'/';
            }
        }

        return base_path('core/');
    }

    /**
     * Создаёт модель EvoPackage с учётом префикса таблиц
     */
    private function createEvoPackageModel($app): EvoPackage
    {
        $model = new EvoPackage;
        $config = $app->make('config');
        if ($config instanceof ConfigContract) {
            $prefix = $config->get('database.connections.mysql.prefix', '')
                ?: $config->get('database.prefix', '');
            if ($prefix) {
                $model->setTable($prefix.'evo_packages');
            }
        }

        return $model;
    }

    /**
     * Резолвит логгер, если он доступен в контейнере
     */
    private function resolveLogger($app): ?LoggerInterface
    {
        return $app->bound(LoggerInterface::class)
            ? $app->make(LoggerInterface::class)
            : null;
    }
}
