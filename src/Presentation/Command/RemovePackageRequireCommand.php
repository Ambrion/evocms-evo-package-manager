<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Presentation\Command;

use EvolutionCMS\EvoPackageManager\Application\RemovePackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use Illuminate\Console\Command;
use RuntimeException;

class RemovePackageRequireCommand extends Command
{
    protected $signature = 'evo:package:remove {package : Package name to remove (vendor/package-name)}';

    protected $description = 'Remove a package from composer.json, vendor/, registry and providers list';

    public function __construct(
        private readonly RemovePackageRequirementUseCase $useCase,
        private readonly ?ComposerRunnerInterface $composerRunner = null,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $packageName = $this->argument('package');

        // Валидация через VO
        try {
            new PackageName($packageName);
        } catch (\InvalidArgumentException $e) {
            $this->error("Invalid package name: {$e->getMessage()}");

            return self::FAILURE;
        }

        try {
            $requirement = PackageRequirement::fromStrings($packageName, '*');

            $this->info("Removing {$packageName}...");
            $this->useCase->execute($requirement);

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

            $this->info("✓ Package {$packageName} removed successfully.");

            return self::SUCCESS;

        } catch (RuntimeException $e) {
            $this->error("Failed to remove package: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
