<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Domain\Port;

/**
 * Port для абстрагирования запуска Composer.
 * Позволяет изолировать Application слой от конкретного способа вызова (Console\Application vs Process).
 */
interface ComposerRunnerInterface
{
    /**
     * Запускает composer update
     */
    public function update(bool $noScripts = false): void;

    /**
     * Запускает произвольную команду Composer
     *
     * @param  string  $command  Команда: 'dump-autoload', 'require vendor/pkg:*', etc.
     * @param  array<string, bool|string>  $options  Опции: ['--optimize' => true, '--no-interaction' => true]
     * @return array{exitCode: int, output: string} Код выхода и вывод команды
     */
    public function runCommand(string $command, array $options = []): array;
}
