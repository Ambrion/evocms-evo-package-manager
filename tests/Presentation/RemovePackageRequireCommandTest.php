<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Presentation;

use EvolutionCMS\EvoPackageManager\Application\RemovePackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Presentation\Command\RemovePackageRequireCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RemovePackageRequireCommandTest extends TestCase
{
    #[Test]
    public function removes_package_successfully(): void
    {
        // Arrange
        $useCase = $this->createMock(RemovePackageRequirementUseCase::class);
        $useCase->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(PackageRequirement::class));

        $command = $this->getMockBuilder(RemovePackageRequireCommand::class)
            ->setConstructorArgs([$useCase])
            ->onlyMethods(['info', 'error', 'argument'])
            ->getMock();

        // ✅ Используем заведомо валидное имя (как в примере установки)
        $command->method('argument')
            ->with('package')
            ->willReturn('vendor/package');

        // ✅ Мокаем info() для обоих вызовов: "Removing..." и "removed successfully"
        $command->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message): void {
                self::assertTrue(
                    str_contains($message, 'Removing vendor/package') ||
                    str_contains($message, 'removed successfully'),
                    "Unexpected message: {$message}"
                );
            });

        // Act — вызываем ОДИН раз!
        $resultCode = $command->handle();

        // Assert
        self::assertSame(0, $resultCode);
    }

    #[Test]
    public function returns_failure_on_invalid_package_name(): void
    {
        $useCase = $this->createMock(RemovePackageRequirementUseCase::class);
        $useCase->expects(self::never())->method('execute');

        $command = $this->getMockBuilder(RemovePackageRequireCommand::class)
            ->setConstructorArgs([$useCase])
            ->onlyMethods(['info', 'error', 'argument'])
            ->getMock();

        // ✅ Неверное имя (нет слэша)
        $command->method('argument')
            ->with('package')
            ->willReturn('invalidname');

        $command->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Invalid package name'));

        $resultCode = $command->handle();
        self::assertSame(1, $resultCode);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function returns_failure_on_use_case_exception(): void
    {
        $useCase = $this->createMock(RemovePackageRequirementUseCase::class);
        $useCase->method('execute')
            ->willThrowException(new \RuntimeException('Composer update failed'));

        $command = $this->getMockBuilder(RemovePackageRequireCommand::class)
            ->setConstructorArgs([$useCase])
            ->onlyMethods(['info', 'error', 'argument'])
            ->getMock();

        $command->method('argument')
            ->with('package')
            ->willReturn('vendor/package');

        $command->expects(self::once())
            ->method('error')
            ->with(self::stringContains('Composer update failed'));

        $resultCode = $command->handle();
        self::assertSame(1, $resultCode);
    }
}
