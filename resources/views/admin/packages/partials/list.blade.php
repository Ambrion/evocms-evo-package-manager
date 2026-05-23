<div class="card">
    <div class="card-header"><span>@lang('evoPackageManager::global.search')</span></div>
    <div class="card-body">
        <div class="card-body">
            {{-- Форма фильтрации --}}
            <form method="GET" action="{{ route('evoPackageManager::index') }}" class="row g-2 align-items-end">
                {{-- Поиск по имени --}}
                <div class="col-md-3">
                    <label for="search" class="form-label small">@lang('evoPackageManager::global.package_item')</label>
                    <input type="text"
                           id="search"
                           name="search"
                           class="form-control"
                           value="{{ trim($filters['search'] ?? '') }}"
                           placeholder="vendor/package">
                </div>

                {{-- Фильтр по статусу --}}
                <div class="col-auto">
                    <label for="status" class="form-label small">@lang('evoPackageManager::global.status')</label>
                    <select id="status" name="status" class="form-select">
                        @foreach($filterOptions['status'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Фильтр по типу --}}
                <div class="col-auto">
                    <label for="type" class="form-label small">@lang('evoPackageManager::global.type')</label>
                    <select id="type" name="type" class="form-select">
                        @foreach($filterOptions['type'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Фильтр по источнику --}}
                <div class="col-auto">
                    <label for="source" class="form-label small">@lang('evoPackageManager::global.source')</label>
                    <select id="source" name="source" class="form-select">
                        @foreach($filterOptions['source'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['source'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Лимит --}}
                <div class="col-auto">
                    <label for="limit" class="form-label small">@lang('evoPackageManager::global.limit')</label>
                    <select id="limit" name="limit" class="form-select">
                        <option value="10" {{ ($filters['limit'] ?? 25) == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ ($filters['limit'] ?? 25) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($filters['limit'] ?? 25) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['limit'] ?? 25) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                {{-- Кнопки --}}
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-filter"></i> @lang('evoPackageManager::global.filter')
                    </button>
                    <a href="{{ route('evoPackageManager::index') }}" class="btn btn-secondary" title="@lang('evoPackageManager::global.clear_filters')">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<hr>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fa fa-list"></i> @lang('evoPackageManager::global.installed_package_list')
        </div>
    </div>

    <div class="card-block">
            <div class="widget-stage">
                <div class="table-responsive">
                    <table class="table data">
                        <thead class="table-light">
                        <tr>
                            {{-- Package --}}
                            <th>
                                <a href="{{ $sortLinks['name'] ?? route('evoPackageManager::index') }}" class="text-decoration-none">
                                    @lang('evoPackageManager::global.package_item')
                                    @if($currentSort === 'name')
                                        <i class="fa fa-sort-{{ $currentDir === 'desc' ? 'desc' : 'asc' }} ms-1"></i>
                                    @else
                                        <i class="fa fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>

                            {{-- Version --}}
                            <th>
                                <a href="{{ $sortLinks['version'] ?? route('evoPackageManager::index') }}" class="text-decoration-none">
                                    @lang('evoPackageManager::global.version')
                                    @if($currentSort === 'version')
                                        <i class="fa fa-sort-{{ $currentDir === 'desc' ? 'desc' : 'asc' }} ms-1"></i>
                                    @else
                                        <i class="fa fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>

                            {{-- Type --}}
                            <th>
                                <a href="{{ $sortLinks['type'] ?? route('evoPackageManager::index') }}" class="text-decoration-none">
                                    @lang('evoPackageManager::global.type')
                                    @if($currentSort === 'type')
                                        <i class="fa fa-sort-{{ $currentDir === 'desc' ? 'desc' : 'asc' }} ms-1"></i>
                                    @else
                                        <i class="fa fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>

                            {{-- Source --}}
                            <th>
                                <a href="{{ $sortLinks['source'] ?? route('evoPackageManager::index') }}" class="text-decoration-none">
                                    @lang('evoPackageManager::global.source')
                                    @if($currentSort === 'source')
                                        <i class="fa fa-sort-{{ $currentDir === 'desc' ? 'desc' : 'asc' }} ms-1"></i>
                                    @else
                                        <i class="fa fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>

                            {{-- Status --}}
                            <th>
                                <a href="{{ $sortLinks['status'] ?? route('evoPackageManager::index') }}" class="text-decoration-none">
                                    @lang('evoPackageManager::global.status')
                                    @if($currentSort === 'status')
                                        <i class="fa fa-sort-{{ $currentDir === 'desc' ? 'desc' : 'asc' }} ms-1"></i>
                                    @else
                                        <i class="fa fa-sort text-muted ms-1"></i>
                                    @endif
                                </a>
                            </th>

                            {{-- Actions (без сортировки) --}}
                            <th class="text-end">@lang('evoPackageManager::global.actions')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($packages as $package)
                            <tr>
                                <td>
                                    <a href="{{ route('evoPackageManager::show', ['package' => $package->name]) }}">
                                        {{ $package->name }}
                                    </a>
                                </td>
                                <td>
                                    {{ $package->version }}
                                </td>
                                <td>{{ $package->type }}</td>
                                <td class="text-muted small">{{ $package->source }}</td>
                                <td>
                                <span class="badge bg-{{ $package->status === 'active' ? 'success' : 'secondary' }} text-white">
                                    {{ $package->status }}
                                </span>
                                </td>
                                <td class="actions text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('evoPackageManager::show', ['package' => $package->name]) }}"
                                           class="btn border-0" title="@lang('evoPackageManager::global.view_details')">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <form action="{{ route('evoPackageManager::destroy', ['package' => $package->name]) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('@lang('evoPackageManager::global.remove_package') «{{ $package->name }}»?\n\n @lang('evoPackageManager::global.action_cannot_undone').')">
                                            @csrf
                                            <button type="submit" class="btn border-0" title="@lang('evoPackageManager::global.action_remove_package')">
                                                <i class="fa fa-trash fa-fw"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    @lang('evoPackageManager::global.no_packages_installed').
                                    <a href="{{ route('evoPackageManager::install.create') }}">@lang('evoPackageManager::global.install_first_package')</a>.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        @include('evoPackageManager::partials.pagination', [
            'pagination' => $pagination,
            'filters' => $filters ?? []
        ])

    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="GET"]');
            if (!form) return;

            // Селекты и лимит — авто-сабмит
            ['status', 'type', 'source', 'limit'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', () => {
                        // Сбрасываем offset при изменении фильтра
                        const offsetInput = document.createElement('input');
                        offsetInput.type = 'hidden';
                        offsetInput.name = 'offset';
                        offsetInput.value = '0';
                        form.appendChild(offsetInput);
                        form.submit();
                    });
                }
            });

            // Поиск — сабмит по Enter или потере фокуса (задержка)
            const search = document.getElementById('search');
            if (search) {
                let timeout;
                search.addEventListener('input', () => {
                    const trimmed = search.value.trim();
                    if (search.value !== trimmed) {
                        search.value = trimmed;
                    }

                    clearTimeout(timeout);
                    timeout = setTimeout(() => form.submit(), 500); // debounce 500ms
                });
                search.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        form.submit();
                    }
                });
            }
        });
    </script>
@endpush
