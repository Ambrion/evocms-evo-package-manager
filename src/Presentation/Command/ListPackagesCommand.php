<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Presentation\Command;

use DateTimeInterface;
use EvolutionCMS\EvoPackageManager\Application\ListPackagesUseCase;
use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class ListPackagesCommand extends Command
{
    protected $signature = 'evo:package:list
        {--status= : Filter by status (active|installed|disabled|pending_update|failed)}
        {--type= : Filter by type (library|composer-plugin|evo-module|evo-plugin)}
        {--source= : Filter by source (packagist|vcs|path|local)}
        {--format=table : Output format: table, json, csv}
        {--limit=50 : Maximum packages to display (1-100)}
        {--offset=0 : Offset for pagination}
        {--count : Show only total count}';

    protected $description = 'List installed packages from the registry';

    public function __construct(
        private readonly ListPackagesUseCase $useCase
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('count')) {
            $filters = [];
            if ($this->option('status')) {
                $filters['status'] = $this->option('status');
            }
            if ($this->option('type')) {
                $filters['type'] = $this->option('type');
            }
            if ($this->option('source')) {
                $filters['source'] = $this->option('source');
            }

            $filters['limit'] = null;

            $packages = $this->useCase->execute($filters);
            $this->line((string) count($packages));

            return self::SUCCESS;
        }

        // Обычный режим с пагинацией
        $filters = array_filter([
            'status' => $this->option('status'),
            'type' => $this->option('type'),
            'source' => $this->option('source'),
            'limit' => (int) $this->option('limit'),
            'offset' => (int) $this->option('offset'),
        ]);

        $packages = $this->useCase->execute($filters);

        $format = $this->option('format');

        return match ($format) {
            'json' => $this->outputJson($packages),
            'csv' => $this->outputCsv($packages),
            default => $this->outputTable($packages),
        };
    }

    protected function outputTable(array $packages): int
    {
        if (empty($packages)) {
            $this->info('No packages found.');

            return self::SUCCESS;
        }

        $table = new Table($this->output);
        $table->setHeaders(['Name', 'Version', 'Status', 'Type', 'Source', 'Installed At']);

        foreach ($packages as $pkg) {
            $table->addRow([
                $pkg->name()->toString(),
                $pkg->version()->toString(),
                $pkg->status,
                $pkg->type,
                $pkg->source,
                $pkg->installedAt?->format('Y-m-d H:i') ?? '—',
            ]);
        }

        $table->render();

        // Пагинация
        $this->newLine();
        $this->comment(sprintf(
            'Showing %d package(s)%s',
            count($packages),
            $packages ? ' (use --limit/--offset for pagination)' : ''
        ));

        return self::SUCCESS;
    }

    private function outputJson(array $packages): int
    {
        $data = array_map(fn (InstalledPackage $p) => [
            'name' => $p->name()->toString(),
            'version' => $p->version()->toString(),
            'status' => $p->status,
            'type' => $p->type,
            'source' => $p->source,
            'requirements' => $p->requirements,
            'installed_at' => $p->installedAt?->format(DateTimeInterface::ATOM),
            'updated_at' => $p->updatedAt?->format(DateTimeInterface::ATOM),
        ], $packages);

        $this->line(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function outputCsv(array $packages): int
    {
        if (empty($packages)) {
            $this->info('No packages found.');

            return self::SUCCESS;
        }

        // Заголовки
        $this->line('name,version,status,type,source,installed_at');

        // Данные
        foreach ($packages as $pkg) {
            $this->line(sprintf(
                '%s,%s,%s,%s,%s,%s',
                $this->escapeCsv($pkg->name()->toString()),
                $this->escapeCsv($pkg->version()->toString()),
                $this->escapeCsv($pkg->status),
                $this->escapeCsv($pkg->type),
                $this->escapeCsv($pkg->source),
                $this->escapeCsv($pkg->installedAt?->format('Y-m-d H:i:s') ?? ''),
            ));
        }

        return self::SUCCESS;
    }

    private function escapeCsv(string $value): string
    {
        // Экранирование для CSV: кавычки и запятые
        if (str_contains($value, ',') || str_contains($value, '"')) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
