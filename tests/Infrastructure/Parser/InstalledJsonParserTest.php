<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Tests\Infrastructure\Parser;

use EvolutionCMS\EvoPackageManager\Infrastructure\Parser\InstalledJsonParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InstalledJsonParserTest extends TestCase
{
    private string $tempDir;

    private string $installedJsonPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/evo_composer_test_'.uniqid();
        mkdir($this->tempDir.'/vendor/composer', 0755, true);
        $this->installedJsonPath = $this->tempDir.'/vendor/composer/installed.json';
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->tempDir/vendor/composer/*"));
        rmdir($this->tempDir.'/vendor/composer');
        rmdir($this->tempDir.'/vendor');
        rmdir($this->tempDir);
        parent::tearDown();
    }

    #[Test]
    public function finds_package_in_installed_json(): void
    {
        $this->writeInstalledJson([
            'packages' => [
                [
                    'name' => 'evolutioncms/manager',
                    'version' => '3.0.1',
                    'source' => ['type' => 'git'],
                    'require' => ['php' => '^8.1'],
                    'autoload' => ['psr-4' => ['Evo\\Manager\\' => 'src/']],
                    'extra' => ['laravel' => ['providers' => ['...']]],
                    'license' => ['MIT'],
                    'authors' => [['name' => 'Team']],
                    'description' => 'Manager package',
                    'homepage' => 'https://...',
                    'type' => 'library',
                ],
            ],
        ]);

        $parser = new InstalledJsonParser($this->tempDir);
        $data = $parser->findPackageData('evolutioncms/manager');

        self::assertNotNull($data);
        self::assertSame('3.0.1', $data['version']);
        self::assertSame('git', $data['source']['type']);
    }

    #[Test]
    public function returns_null_when_package_not_found(): void
    {
        $this->writeInstalledJson(['packages' => [['name' => 'other/pkg', 'version' => '1.0']]]);

        $parser = new InstalledJsonParser($this->tempDir);
        $data = $parser->findPackageData('non/existent');

        self::assertNull($data);
    }

    #[Test]
    public function throws_when_installed_json_missing(): void
    {
        $parser = new InstalledJsonParser('/non/existent/path');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('installed.json not found');

        $parser->findPackageData('any/pkg');
    }

    private function writeInstalledJson(array $data): void
    {
        file_put_contents($this->installedJsonPath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
