<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Infrastructure\Runner;

use Composer\Console\Application;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

readonly class ConsoleComposerRunner implements ComposerRunnerInterface
{
    public function __construct(
        private string $corePath,
        private string $composerHomePath,
    ) {}

    public function update(bool $noScripts = false): void
    {
        $commandString = 'update --no-interaction --no-progress';
        if ($noScripts) {
            $commandString .= ' --no-scripts';
        }

        $result = $this->executeComposerCommand($commandString);

        if ($result['exitCode'] !== 0) {
            throw new RuntimeException("Composer update failed (code: {$result['exitCode']}): {$result['output']}");
        }
    }

    public function runCommand(string $command, array $options = []): array
    {
        $commandString = $this->buildCommandString($command, $options);

        return $this->executeComposerCommand($commandString);
    }

    /**
     * Общая логика запуска команды Composer
     */
    private function executeComposerCommand(string $commandString): array
    {
        $originalDir = getcwd();
        $originalComposerHome = getenv('COMPOSER_HOME');

        try {
            putenv('COMPOSER_HOME='.$this->composerHomePath);
            chdir($this->corePath);

            $input = new StringInput($commandString);
            $output = new BufferedOutput;

            $application = new Application;
            $application->setAutoExit(false);
            $application->setCatchExceptions(false);

            $exitCode = $application->run($input, $output);
            $outputContent = $output->fetch();

            return ['exitCode' => $exitCode, 'output' => $outputContent];

        } finally {
            $this->restoreEnvironment($originalDir, $originalComposerHome);
        }
    }

    /**
     * Строит строку команды из имени и опций
     */
    private function buildCommandString(string $command, array $options): string
    {
        $parts = [$command];

        foreach ($options as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $key;
                }
            } elseif (is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = "{$key}={$v}";
                }
            } else {
                $parts[] = "{$key}={$value}";
            }
        }

        return implode(' ', $parts);
    }

    private function restoreEnvironment(false|string $originalDir, false|string $originalComposerHome): void
    {
        if ($originalDir !== false) {
            chdir($originalDir);
        }
        putenv('COMPOSER_HOME='.($originalComposerHome ?: ''));
    }
}
