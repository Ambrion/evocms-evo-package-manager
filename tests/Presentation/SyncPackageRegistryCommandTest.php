<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Presentation;

use EvolutionCMS\EvoPackageManager\Application\SyncPackageRegistryUseCase;
use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use EvolutionCMS\EvoPackageManager\Presentation\Command\SyncPackageRegistryCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
class SyncPackageRegistryCommandTest extends TestCase
{
    /**
     * Создаёт мок команды с настроенными методами вывода и аргументов
     */
    private function createMockCommand(
        SyncPackageRegistryUseCase $useCase,
        InstalledJsonParser $parser,
        array $argumentMap = []
    ): SyncPackageRegistryCommand {
        $command = $this->getMockBuilder(SyncPackageRegistryCommand::class)
            ->setConstructorArgs([$useCase, $parser])
            ->onlyMethods(['info', 'error', 'warn', 'line', 'newLine', 'argument'])
            ->getMock();

        if (! empty($argumentMap)) {
            $command->method('argument')->willReturnMap($argumentMap);
        }

        return $command;
    }

    #[Test]
    public function syncs_single_package_successfully(): void
    {
        // Arrange
        $useCase = $this->createMock(SyncPackageRegistryUseCase::class);
        $useCase->expects(self::once())
            ->method('sync')
            ->with('vendor/package');

        $parser = $this->createMock(InstalledJsonParser::class);
        // Parser не должен вызываться при синхронизации одного пакета
        $parser->expects(self::never())->method('findAllPackages');

        $command = $this->createMockCommand($useCase, $parser, [
            ['package', 'vendor/package'],
        ]);

        $command->expects(self::once())
            ->method('info')
            ->with(self::stringContains('✓ Synced: vendor/package'));

        // Act
        $resultCode = $command->handle();

        // Assert
        self::assertSame(0, $resultCode);
    }

    #[Test]
    public function handles_failure_when_syncing_single_package(): void
    {
        $useCase = $this->createMock(SyncPackageRegistryUseCase::class);
        $useCase->expects(self::once())
            ->method('sync')
            ->with('vendor/broken')
            ->willThrowException(new RuntimeException('Connection timeout'));

        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->expects(self::never())->method('findAllPackages');

        $command = $this->createMockCommand($useCase, $parser, [
            ['package', 'vendor/broken'],
        ]);

        $command->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Connection timeout'));

        $resultCode = $command->handle();

        self::assertSame(1, $resultCode);
    }

    #[Test]
    public function syncs_all_packages_from_installed_json(): void
    {
        $useCase = $this->createMock(SyncPackageRegistryUseCase::class);
        // Ожидаем вызов sync для каждого валидного пакета (порядок не гарантирован)
        $useCase->expects(self::exactly(2))
            ->method('sync')
            ->with(self::logicalOr('vendor/pkg-a', 'vendor/pkg-b'));

        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->expects(self::once())
            ->method('findAllPackages')
            ->willReturn([
                ['name' => 'vendor/pkg-a', 'version' => '1.0.0'],
                ['name' => 'vendor/pkg-b', 'version' => '2.0.0'],
                ['name' => null, 'version' => '1.0.0'], // невалидная запись — будет пропущена
            ]);

        $command = $this->createMockCommand($useCase, $parser, [
            ['package', null], // no argument = sync all
        ]);

        // Ожидаем вывод для каждого пакета
        $command->expects(self::atLeastOnce())->method('line')
            ->with(self::logicalOr(
                self::stringContains('✓ vendor/pkg-a@1.0.0'),
                self::stringContains('✓ vendor/pkg-b@2.0.0')
            ));

        $command->expects(self::once())->method('newLine');
        $command->expects(self::once())->method('info')
            ->with(self::stringContains('Sync complete: 2 succeeded'));

        $resultCode = $command->handle();

        self::assertSame(0, $resultCode);
    }

    #[Test]
    public function handles_partial_failures_when_syncing_all_packages(): void
    {
        $useCase = $this->createMock(SyncPackageRegistryUseCase::class);
        $useCase->expects(self::exactly(2))
            ->method('sync')
            ->willReturnCallback(function (string $name) {
                if ($name === 'vendor/ok') {
                    return; // успех
                }
                if ($name === 'vendor/fail') {
                    throw new RuntimeException('Not found in registry');
                }
            });

        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->expects(self::once())
            ->method('findAllPackages')
            ->willReturn([
                ['name' => 'vendor/ok', 'version' => '1.0.0'],
                ['name' => 'vendor/fail', 'version' => '2.0.0'],
            ]);

        $command = $this->createMockCommand($useCase, $parser, [
            ['package', null],
        ]);

        $command->expects(self::atLeastOnce())->method('line')
            ->with(self::logicalOr(
                self::stringContains('✓ vendor/ok@1.0.0'),
                self::stringContains('✗ vendor/fail')
            ));

        $command->expects(self::once())->method('newLine');
        $command->expects(self::once())->method('info')
            ->with(self::stringContains('1 succeeded, 1 failed'));

        $resultCode = $command->handle();

        // Возвращаем FAILURE, если были ошибки
        self::assertSame(1, $resultCode);
    }

    #[Test]
    public function handles_parser_exception_when_syncing_all_packages(): void
    {
        $useCase = $this->createMock(SyncPackageRegistryUseCase::class);
        $useCase->expects(self::never())->method('sync');

        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->expects(self::once())
            ->method('findAllPackages')
            ->willThrowException(new RuntimeException('installed.json corrupted'));

        $command = $this->createMockCommand($useCase, $parser, [
            ['package', null],
        ]);

        $command->expects(self::once())
            ->method('error')
            ->with(self::stringContains('installed.json corrupted'));

        $resultCode = $command->handle();

        self::assertSame(1, $resultCode);
    }

    #[Test]
    public function skips_packages_without_name_in_list_mode(): void
    {
        $useCase = $this->createMock(SyncPackageRegistryUseCase::class);
        // Вызов только для пакета с именем
        $useCase->expects(self::once())
            ->method('sync')
            ->with('vendor/valid');

        $parser = $this->createMock(InstalledJsonParser::class);
        $parser->expects(self::once())
            ->method('findAllPackages')
            ->willReturn([
                ['name' => null],           // пропустится
                ['version' => '1.0.0'],     // пропустится (нет name)
                ['name' => 'vendor/valid', 'version' => '1.0.0'], // обработается
            ]);

        $command = $this->createMockCommand($useCase, $parser, [
            ['package', null],
        ]);

        $command->expects(self::once())->method('line')
            ->with(self::stringContains('✓ vendor/valid@1.0.0'));

        $command->expects(self::once())->method('newLine');
        $command->expects(self::once())->method('info')
            ->with(self::stringContains('Sync complete: 1 succeeded'));

        $resultCode = $command->handle();

        self::assertSame(0, $resultCode);
    }
}
