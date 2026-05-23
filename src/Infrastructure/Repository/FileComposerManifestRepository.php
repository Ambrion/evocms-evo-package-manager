<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Infrastructure\Repository;

use EvolutionCMS\EvoPackageManager\Domain\Port\ComposerManifestRepositoryInterface;
use RuntimeException;

class FileComposerManifestRepository implements ComposerManifestRepositoryInterface
{
    private const array DEFAULT_STRUCTURE = [
        'name' => 'evolutioncms/custom',
        'require' => [],
        'autoload' => ['psr-4' => []],
    ];

    public function __construct(private readonly string $manifestPath) {}

    public function loadRequirements(): array
    {
        $data = $this->decodeExistingManifest();

        return $data['require'] ?? [];
    }

    public function saveRequirements(array $requirements): void
    {
        $data = $this->decodeExistingManifest();
        $data['require'] = $requirements;

        $this->atomicWrite($data);
    }

    private function decodeExistingManifest(): array
    {
        if (! file_exists($this->manifestPath)) {
            return self::DEFAULT_STRUCTURE;
        }

        $content = file_get_contents($this->manifestPath);
        if ($content === false || ! json_validate($content)) {
            throw new RuntimeException("Invalid or unreadable composer manifest at {$this->manifestPath}");
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Composer manifest must be a valid JSON object.');
        }

        return $decoded;
    }

    private function atomicWrite(array $data): void
    {
        $tempFile = tempnam(dirname($this->manifestPath), 'evo_composer_tmp_');
        if ($tempFile === false) {
            throw new RuntimeException('Failed to create temporary file for atomic write.');
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if (file_put_contents($tempFile, $json) === false) {
            throw new RuntimeException('Failed to write composer manifest.');
        }

        if (! rename($tempFile, $this->manifestPath)) {
            @unlink($tempFile);
            throw new RuntimeException('Atomic rename failed. Manifest not saved.');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($this->manifestPath, 0644);
        }
    }
}
