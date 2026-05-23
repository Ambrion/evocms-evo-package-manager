<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Presentation;

use EvolutionCMS\EvoPackageManager\Application\ListPackagesUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageName;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageVersion;
use EvolutionCMS\EvoPackageManager\Presentation\Command\ListPackagesCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ListPackagesCommandTest extends TestCase
{
    /**
     * Создаёт мок команды с настроенными методами
     *
     * @param  bool  $mockOutputTable  Если false — outputTable выполняется реально (для тестов пустого результата)
     */
    private function createCommand(
        ListPackagesUseCase $useCase,
        array $optionMap = [],
        bool $mockOutputTable = true
    ): ListPackagesCommand {
        $methodsToMock = ['option', 'line', 'info', 'comment', 'newLine'];

        if ($mockOutputTable) {
            $methodsToMock[] = 'outputTable';
        }

        $command = $this->getMockBuilder(ListPackagesCommand::class)
            ->setConstructorArgs([$useCase])
            ->onlyMethods($methodsToMock)
            ->getMock();

        $defaultOptions = [
            'status' => null,
            'type' => null,
            'source' => null,
            'format' => 'table',
            'limit' => '50',
            'offset' => '0',
            'count' => false,
        ];

        $mergedOptions = array_merge($defaultOptions, $optionMap);

        $command->method('option')
            ->willReturnCallback(fn ($name) => $mergedOptions[$name] ?? null);

        return $command;
    }

    #[Test]
    public function outputs_table_format_by_default(): void
    {
        $useCase = $this->createMock(ListPackagesUseCase::class);

        $expectedPackages = [
            new InstalledPackage(
                name: new PackageName('vendor/pkg'),
                version: new PackageVersion('1.0.0'),
                status: 'active',
                type: 'library',
                source: 'packagist'
            ),
        ];

        $useCase->method('execute')->willReturn($expectedPackages);

        // Мок-аем outputTable, чтобы не создавать Table(null)
        $command = $this->createCommand($useCase, ['format' => 'table'], mockOutputTable: true);

        $command->expects(self::once())
            ->method('outputTable')
            ->with(self::equalTo($expectedPackages));

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }

    #[Test]
    public function outputs_json_format(): void
    {
        $useCase = $this->createMock(ListPackagesUseCase::class);
        $useCase->method('execute')->willReturn([
            new InstalledPackage(
                name: new PackageName('vendor/pkg'),
                version: new PackageVersion('1.0.0')
            ),
        ]);

        $command = $this->createCommand($useCase, ['format' => 'json'], mockOutputTable: true);

        $command->expects(self::once())
            ->method('line')
            ->with(self::stringContains('"name":"vendor/pkg"'));

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }

    #[Test]
    public function outputs_count_only(): void
    {
        $useCase = $this->createMock(ListPackagesUseCase::class);
        $useCase->method('execute')->willReturn([
            new InstalledPackage(new PackageName('vendor/pkg-a'), new PackageVersion('1.0.0')),
            new InstalledPackage(new PackageName('vendor/pkg-b'), new PackageVersion('2.0.0')),
        ]);

        $command = $this->createCommand($useCase, ['count' => true], mockOutputTable: true);

        $command->expects(self::once())
            ->method('line')
            ->with('2');

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }

    #[Test]
    public function handles_empty_result_in_table_mode(): void
    {
        $useCase = $this->createMock(ListPackagesUseCase::class);
        $useCase->method('execute')->willReturn([]);

        // НЕ мок-аем outputTable — пусть выполнится реальная логика проверки empty()
        $command = $this->createCommand($useCase, ['format' => 'table'], mockOutputTable: false);

        $command->expects(self::once())
            ->method('info')
            ->with('No packages found.');

        $resultCode = $command->handle();
        self::assertSame(0, $resultCode);
    }
}
