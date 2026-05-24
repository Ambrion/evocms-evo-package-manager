<?php

declare(strict_types=1);

namespace EvolutionCMS\EvoPackageManager\Presentation\Http\Controllers\Admin;

use EvolutionCMS\EvoPackageManager\Application\ListPackagesUseCase;
use EvolutionCMS\EvoPackageManager\Application\RemovePackageRequirementUseCase;
use EvolutionCMS\EvoPackageManager\Application\SyncPackageRegistryUseCase;
use EvolutionCMS\EvoPackageManager\Domain\ValueObject\PackageRequirement;
use EvolutionCMS\EvoPackageManager\Presentation\Http\DTO\PackageViewDataDTO;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class PackageAdminController extends Controller
{
    public function __construct(
        private readonly ListPackagesUseCase $listPackagesUseCase,
        private readonly RemovePackageRequirementUseCase $removeUseCase,
        private readonly SyncPackageRegistryUseCase $syncUseCase,
    ) {}

    /**
     * Список установленных пакетов с фильтрацией и пагинацией
     */
    public function index(Request $request): View
    {
        // Сбор фильтров
        $filters = array_filter([
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'source' => $request->get('source'),
            'search' => $request->get('search'),
            'sort' => $request->get('sort'),
            'dir' => $request->get('dir'),
            'limit' => $this->sanitizeLimit($request->get('limit', 25)),
            'offset' => max(0, (int) $request->get('offset', 0)),
        ], fn ($v) => $v !== null && $v !== '');

        // Получаем общее количество (для пагинации)
        $totalCount = $this->listPackagesUseCase->count($filters);

        // Получаем пакеты для текущей страницы
        $packages = $this->listPackagesUseCase->execute($filters);

        // Преобразуем в DTO
        $packagesDTO = array_map(
            fn ($package) => PackageViewDataDTO::fromEntity($package),
            $packages
        );

        $filterOptions = $this->listPackagesUseCase->getFilterOptions();

        // Подготовка пагинации с "разрывом"
        $pagination = $this->preparePaginationData(
            routeName: 'evoPackageManager::index',
            totalCount: $totalCount,
            currentPage: max(1, (int) floor($filters['offset'] / ($filters['limit'] ?? 25)) + 1),
            perPage: $filters['limit'] ?? 25,
            filters: array_diff_key($filters, array_flip(['offset', 'limit']))
        );

        // Остальные данные для шаблона
        $sortLinks = $this->buildSortLinks($filters);

        return view('evoPackageManager::admin.packages.index', [
            'packages' => $packagesDTO,
            'title' => 'Installed Packages',
            'managerLang' => $this->getManagerLanguage(),
            'filters' => $filters,
            'pagination' => $pagination,
            'filterOptions' => $filterOptions,
            'sortLinks' => $sortLinks,
        ]);
    }

    /**
     * Подготовка данных для шаблона пагинации с "разрывом"
     */
    private function preparePaginationData(
        string $routeName,
        int $totalCount,
        int $currentPage,
        int $perPage,
        array $filters
    ): array {
        $totalPages = (int) ceil($totalCount / $perPage);
        $hasPrev = $currentPage > 1;
        $hasNext = $currentPage < $totalPages;

        // Фильтруем пустые значения
        $cleanFilters = array_filter($filters, fn ($v) => $v !== null && trim((string) $v) !== '');

        // Генерируем массив страниц с многоточием
        $pages = $this->generatePaginationPages($currentPage, $totalPages);

        $pageUrls = [];
        foreach ($pages as $p) {
            if ($p === '...') {
                $pageUrls[] = '...';
            } else {
                $offset = ($p - 1) * $perPage;
                $pageUrls[] = route($routeName, array_merge($cleanFilters, [
                    'offset' => $offset,
                    'limit' => $perPage,
                ]));
            }
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_prev' => $hasPrev,
            'has_next' => $hasNext,
            'total' => $totalCount,
            'per_page' => $perPage,
            'pages' => $pages,
            'page_urls' => $pageUrls,
            'urls' => [
                'prev' => $hasPrev ? route($routeName, array_merge($cleanFilters, [
                    'offset' => ($currentPage - 2) * $perPage,
                    'limit' => $perPage,
                ])) : null,
                'next' => $hasNext ? route($routeName, array_merge($cleanFilters, [
                    'offset' => $currentPage * $perPage,
                    'limit' => $perPage,
                ])) : null,
                'base' => route($routeName, $cleanFilters),
            ],
        ];
    }

    /**
     * Генерация массива страниц с многоточием (как в feature-flags)
     * Возвращает: [1, 2, '...', 8, 9, 10, '...', 20]
     */
    private function generatePaginationPages(int $currentPage, int $totalPages, int $delta = 1): array
    {
        if ($totalPages <= 1) {
            return [];
        }

        $pages = [];

        // Всегда показываем первую страницу
        $pages[] = 1;

        // Многоточие после первой, если текущая далеко от начала
        if ($currentPage - $delta > 2) {
            $pages[] = '...';
        }

        // Страницы вокруг текущей (исключая первую и последнюю)
        $start = max(2, $currentPage - $delta);
        $end = min($totalPages - 1, $currentPage + $delta);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        // Многоточие перед последней, если текущая далеко от конца
        if ($currentPage + $delta < $totalPages - 1) {
            $pages[] = '...';
        }

        // Всегда показываем последнюю страницу (если страниц больше 1)
        $pages[] = $totalPages;

        // Убираем дубликаты (случай, когда текущая рядом с краем)
        return array_values(array_unique($pages, SORT_REGULAR));
    }

    /**
     * Построение ссылок сортировки для заголовков таблицы
     */
    private function buildSortLinks(array $currentFilters): array
    {
        // Убираем параметры, которые будем переопределять
        $baseParams = array_filter($currentFilters, fn ($v) => $v !== null && $v !== '');
        unset($baseParams['sort'], $baseParams['dir'], $baseParams['offset']);

        $fields = ['name', 'version', 'type', 'source', 'status'];
        $links = [];

        foreach ($fields as $field) {
            $isCurrent = ($currentFilters['sort'] ?? '') === $field;
            $newDir = $isCurrent && ($currentFilters['dir'] ?? 'asc') === 'asc' ? 'desc' : 'asc';

            $params = array_merge($baseParams, [
                'sort' => $field,
                'dir' => $isCurrent ? $newDir : 'asc',
                'offset' => 0, // Сброс пагинации при смене сортировки
            ]);

            $links[$field] = route('evoPackageManager::index', $params);
        }

        return $links;
    }

    /**
     * Санитизация лимита: 1-100
     */
    private function sanitizeLimit(mixed $value): int
    {
        $limit = (int) $value;

        return $limit > 0 ? min($limit, 100) : 25;
    }

    /**
     * Детали пакета
     */
    public function show(string $package): View|RedirectResponse
    {
        $packages = $this->listPackagesUseCase->execute();
        $packagesDTO = array_map(
            fn ($package) => PackageViewDataDTO::fromEntity($package),
            $packages
        );

        $packageData = collect($packagesDTO)->firstWhere('name', $package);

        if (! $packageData) {
            return redirect()->route('evoPackageManager::index')
                ->with('error', "Package {$package} not found");
        }

        return view('evoPackageManager::admin.packages.show', [
            'package' => $packageData,
            'title' => "Package: {$package}",
        ]);
    }

    /**
     * Форма установки нового пакета
     */
    public function create(): View
    {
        return view('evoPackageManager::admin.packages.install', [
            'title' => 'Install New Package',
            'errors' => session('errors') ?? new ViewErrorBag,
        ]);
    }

    /**
     * Обработка установки пакета
     */
    public function store(Request $request): RedirectResponse
    {
        // Увеличиваем таймаут для самого HTTP-запроса
        set_time_limit(360);

        $validator = Validator::make($request->all(), [
            'package' => 'required|regex:/^[a-z0-9\-_]+\/[a-z0-9\-_]+$/i',
            'version' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $package = $request->input('package');
            $version = $request->input('version');

            //  Запускаем консольную команду через отдельный процесс
            $artisanPath = EVO_CORE_PATH . 'artisan';
            $process = new Process([
                'php',
                $artisanPath,
                'evo:package:install',
                $package,
                $version
            ]);

            $process->setTimeout(300); // 5 минут на выполнение
            $process->run();

            //  Обрабатываем результат
            if (! $process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                Log::error("[PackageInstall] Command failed", [
                    'package' => $package,
                    'exitCode' => $process->getExitCode(),
                    'error' => $errorOutput
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', "Installation failed: " . trim($errorOutput));
            }

            //  Парсим вывод для показа пользователю
            $output = $process->getOutput();
            $messages = $this->parseCommandOutput($output);

            Log::info("[PackageInstall] Success: {$package}", ['output' => $messages]);

            return redirect()->route('evoPackageManager::index')
                ->with('success', "Package {$package} installed successfully: " . implode('; ', $messages));

        } catch (\Throwable $e) {
            Log::error("[PackageInstall] Exception: {$e->getMessage()}", [
                'package' => $request->input('package'),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', "Failed to install package: {$e->getMessage()}");
        }
    }

    /**
     * Парсит вывод команды для извлечения ключевых сообщений
     */
    private function parseCommandOutput(string $output): array
    {
        $messages = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $line = trim($line);

            // Извлекаем сообщения с галочками/предупреждениями
            if (preg_match('/[✓✔✔]/i', $line) || preg_match('/[⚠⚡]/i', $line)) {
                // Убираем эмодзи и лишние пробелы для чистого вывода
                $clean = preg_replace('/[✓✔✔⚠⚡•]\s*/', '', $line);
                if (!empty($clean)) {
                    $messages[] = $clean;
                }
            }

            // Альтернативно: можно возвращать весь вывод, если нужно
            // $messages[] = $line;
        }

        return array_filter($messages);
    }

    /**
     * Удаление пакета (через POST-форму)
     *
     * @param  string  $package  Имя пакета в формате vendor/package
     */
    public function destroy(string $package): RedirectResponse
    {
        try {
            // Валидация через VO (бросит исключение при невалидном формате)
            $requirement = PackageRequirement::fromStrings($package, '*');

            $this->removeUseCase->execute($requirement);

            return redirect()
                ->route('evoPackageManager::index')
                ->with('success', "Package {$package} removed successfully");

        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "Failed to remove package: {$e->getMessage()}");
        }
    }

    /**
     * Синхронизация реестра с installed.json
     */
    public function sync(): RedirectResponse
    {
        try {
            $this->syncUseCase->syncAll();

            return redirect()->route('evoPackageManager::index')
                ->with('success', 'Package registry synchronized');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', "Sync failed: {$e->getMessage()}");
        }
    }

    private function getManagerLanguage(): string
    {
        $lang = evo()->getConfig('manager_language')
            ?? evo()->getConfig('cultureKey')
            ?? 'en';

        // Нормализуем: 'ru-UTF8' -> 'ru', 'en-US' -> 'en'
        return preg_replace('/[-_].*$/', '', strtolower($lang));
    }
}
