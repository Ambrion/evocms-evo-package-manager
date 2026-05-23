<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Infrastructure;

use EvolutionCMS\EvoPackageManager\Infrastructure\Repository\FileComposerManifestRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileComposerManifestRepositoryTest extends TestCase
{
    private string $tempDir;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/evo_composer_test_'.uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->manifestPath = $this->tempDir.'/composer.json';
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->tempDir/*"));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    #[Test]
    public function loads_empty_array_when_file_missing(): void
    {
        $repo = new FileComposerManifestRepository($this->manifestPath);
        self::assertSame([], $repo->loadRequirements());
    }

    #[Test]
    public function loads_and_saves_requirements_atomically(): void
    {
        // Инициализируем файл с полной структурой, как ожидает Evo 3
        $initialData = [
            'require' => ['a/b' => '^1'],
            'autoload' => ['psr-4' => ['Evo\\Custom\\' => 'src/']],
            'name' => 'evolutioncms/custom',
        ];
        file_put_contents($this->manifestPath, json_encode($initialData, JSON_PRETTY_PRINT));

        $repo = new FileComposerManifestRepository($this->manifestPath);
        $current = $repo->loadRequirements();
        self::assertSame(['a/b' => '^1'], $current);

        // Сохраняем обновлённый require
        $repo->saveRequirements(['a/b' => '^1', 'c/d' => 'dev-main']);

        $decoded = json_decode(file_get_contents($this->manifestPath), true);
        self::assertArrayHasKey('require', $decoded);
        self::assertSame(['a/b' => '^1', 'c/d' => 'dev-main'], $decoded['require']);

        // Проверяем, что остальные ключи (autoload, name) остались нетронутыми
        self::assertArrayHasKey('autoload', $decoded, 'Existing keys must be preserved');
        self::assertSame($initialData['autoload'], $decoded['autoload']);
        self::assertSame($initialData['name'], $decoded['name']);
    }

    #[Test]
    public function throws_on_invalid_json(): void
    {
        file_put_contents($this->manifestPath, '{ broken json }');
        $repo = new FileComposerManifestRepository($this->manifestPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid or unreadable composer manifest');

        $repo->loadRequirements();
    }
}
