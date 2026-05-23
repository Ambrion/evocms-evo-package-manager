<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Presentation;

use EvolutionCMS\EvoPackageManager\Application\InstallPackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerRunnerInterface;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use EvolutionCMS\EvoPackageManager\Presentation\Command\InstallPackageRequireCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InstallPackageRequireCommandTest extends TestCase
{
    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function executes_use_case_and_post_install_with_composer_run(): void
    {
        $useCase = $this->createMock(InstallPackageRequirementUseCase::class);
        $useCase->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(PackageRequirement::class), true);

        $installedJsonParser = $this->createMock(InstalledJsonParser::class);
        $composerRunner = $this->createMock(ComposerRunnerInterface::class);

        $command = $this->getMockBuilder(InstallPackageRequireCommand::class)
            ->setConstructorArgs([$useCase, $installedJsonParser, $composerRunner])
            ->onlyMethods(['info', 'error', 'argument', 'option', 'call', 'runPostInstall'])
            ->getMock();

        $command->method('argument')->willReturnCallback(fn ($name) => match ($name) {
            'package' => 'vendor/package',
            'version' => '^1.0',
            default => '',
        });

        $command->method('option')->willReturnCallback(fn ($name) => match ($name) {
            'no-composer' => false,
            'skip-post-install' => false,
            'force-migrate' => false,
            default => false,
        });

        $command->method('call')->willReturn(0);

        $command->expects(self::once())
            ->method('runPostInstall')
            ->with('vendor/package');

        $composerRunner->expects(self::once())
            ->method('runCommand')
            ->with('dump-autoload', ['--optimize' => true, '--no-interaction' => true])
            ->willReturn(['exitCode' => 0, 'output' => '']);

        $command->expects(self::never())->method('error');

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function skips_composer_update_when_option_passed(): void
    {
        $useCase = $this->createMock(InstallPackageRequirementUseCase::class);
        $useCase->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(PackageRequirement::class), false);

        $installedJsonParser = $this->createMock(InstalledJsonParser::class);
        $composerRunner = $this->createMock(ComposerRunnerInterface::class);

        $command = $this->getMockBuilder(InstallPackageRequireCommand::class)
            ->setConstructorArgs([$useCase, $installedJsonParser, $composerRunner])
            ->onlyMethods(['info', 'error', 'argument', 'option', 'call', 'runPostInstall'])
            ->getMock();

        $command->method('argument')->willReturnCallback(fn ($name) => match ($name) {
            'package' => 'vendor/package',
            'version' => 'dev-main',
            default => '',
        });

        $command->method('option')->willReturnCallback(fn ($name) => match ($name) {
            'no-composer' => true,
            'skip-post-install' => false,
            'force-migrate' => false,
            default => false,
        });

        $command->method('call')->willReturn(0);

        $command->expects(self::once())
            ->method('runPostInstall')
            ->with('vendor/package');

        $composerRunner->expects(self::once())
            ->method('runCommand')
            ->willReturn(['exitCode' => 0, 'output' => '']);

        $command->expects(self::never())->method('error');

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function skips_post_install_when_option_passed(): void
    {
        $useCase = $this->createMock(InstallPackageRequirementUseCase::class);
        $useCase->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(PackageRequirement::class), true);

        $installedJsonParser = $this->createMock(InstalledJsonParser::class);
        $composerRunner = $this->createMock(ComposerRunnerInterface::class);

        $command = $this->getMockBuilder(InstallPackageRequireCommand::class)
            ->setConstructorArgs([$useCase, $installedJsonParser, $composerRunner])
            // Мокаем runPostInstall во ВСЕХ тестах для контроля
            ->onlyMethods(['info', 'error', 'argument', 'option', 'call', 'runPostInstall'])
            ->getMock();

        $command->method('argument')->willReturnCallback(fn ($name) => match ($name) {
            'package' => 'vendor/package',
            'version' => '^1.0',
            default => '',
        });

        $command->method('option')->willReturnCallback(fn ($name) => match ($name) {
            'no-composer' => false,
            'skip-post-install' => true,  // ← Пропускаем пост-инсталляцию
            'force-migrate' => false,
            default => false,
        });

        $command->method('call')->willReturn(0);

        // Явно ожидаем, что runPostInstall НЕ будет вызван
        $command->expects(self::never())->method('runPostInstall');

        // composerRunner->runCommand() вызывается НЕЗАВИСИМО от skip-post-install
        // (это отдельный шаг после пост-инсталляции для регенерации автозагрузки)
        $composerRunner->expects(self::once())
            ->method('runCommand')
            ->with('dump-autoload', ['--optimize' => true, '--no-interaction' => true])
            ->willReturn(['exitCode' => 0, 'output' => '']);

        $command->expects(self::never())->method('error');

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function returns_failure_on_use_case_exception(): void
    {
        $useCase = $this->createMock(InstallPackageRequirementUseCase::class);
        $useCase->method('execute')
            ->willThrowException(new RuntimeException('Composer update failed'));

        $installedJsonParser = $this->createMock(InstalledJsonParser::class);
        $composerRunner = $this->createMock(ComposerRunnerInterface::class);

        $command = $this->getMockBuilder(InstallPackageRequireCommand::class)
            ->setConstructorArgs([$useCase, $installedJsonParser, $composerRunner])
            ->onlyMethods(['info', 'error', 'argument', 'option', 'call'])
            ->getMock();

        $command->method('argument')->willReturnCallback(fn ($name) => match ($name) {
            'package' => 'vendor/package',
            'version' => '^1.0',
            default => '',
        });

        $command->method('option')->willReturnCallback(fn ($name) => match ($name) {
            'no-composer' => false,
            'skip-post-install' => false,
            'force-migrate' => false,
            default => false,
        });

        $command->method('call')->willReturn(0);

        // При исключении из UseCase код не доходит до следующих шагов
        $composerRunner->expects(self::never())->method('runCommand');

        $command->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Composer update failed'));

        $resultCode = $command->handle();
        self::assertSame(1, $resultCode);
    }
}
