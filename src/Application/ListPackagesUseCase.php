<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Application;

use EvolutionCMS\EvoPackageManager\Domain\Entity\InstalledPackage;
use EvolutionCMS\EvoPackageManager\Domain\Port\PackageRegistryRepositoryInterface;

readonly class ListPackagesUseCase
{
    public function __construct(
        private PackageRegistryRepositoryInterface $repository
    ) {}

    /**
     * Получает список пакетов с фильтрацией
     *
     * @param array{
     *     status?: string,
     *     type?: string,
     *     source?: string,
     *     search?: string,
     *     limit?: int<1, 100>|null,
     *     offset?: int<0, max>,
     *     sort?: string,
     *     dir?: 'asc'|'desc'
     * } $filters
     * @return list<InstalledPackage>
     */
    public function execute(array $filters = []): array
    {
        return $this->repository->findAll($this->prepareFilters($filters));
    }

    /**
     * Получает общее количество пакетов с учётом фильтров
     */
    public function count(array $filters = []): int
    {
        return $this->repository->count($this->prepareFilters($filters));
    }

    /**
     * Подготовка фильтров для репозитория
     */
    private function prepareFilters(array $filters): array
    {
        $processed = [];

        foreach (['status', 'type', 'source', 'search'] as $key) {
            if (! empty($filters[$key])) {
                $processed[$key] = $filters[$key];
            }
        }

        // Лимит: 1-100, по умолчанию 25
        $processed['limit'] = isset($filters['limit']) && $filters['limit'] !== null
            ? min(max((int) $filters['limit'], 1), 100)
            : 25;

        // Смещение: >= 0
        $processed['offset'] = isset($filters['offset'])
            ? max((int) $filters['offset'], 0)
            : 0;

        // Сортировка
        $processed['sort'] = $filters['sort'] ?? 'name';
        $processed['dir'] = ($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $processed;
    }

    /**
     * Возвращает актуальные значения для фильтров из БД
     */
    public function getFilterOptions(): array
    {
        $statuses = $this->repository->getDistinctValues('status');
        $types = $this->repository->getDistinctValues('type');
        $sources = $this->repository->getDistinctValues('source');

        // Форматируем в структуру: ['' => 'All...', 'value' => 'Value', ...]
        $format = fn (array $values, string $allLabel) => array_merge([$allLabel], array_combine($values, array_map('ucfirst', $values)));

        return [
            'status' => $format($statuses, __('evoPackageManager::global.all_statuses')),
            'type' => $format($types, __('evoPackageManager::global.all_types')),
            'source' => $format($sources, __('evoPackageManager::global.all_sources')),
        ];
    }
}
