{{--
    Параметры:
    - $pagination: array с current_page, total_pages, has_prev, has_next, pages, page_urls, urls, total, per_page
--}}
@if(($pagination['total_pages'] ?? 1) > 1)
    <nav aria-label="@lang('evoPackageManager::global.pagination_records')" class="mt-3">
        <ul class="pagination pagination-sm justify-content-center mb-0">

            {{-- Кнопка "Назад" --}}
            <li class="page-item {{ !$pagination['has_prev'] ? 'disabled' : '' }}">
                <a class="page-link"
                   href="{{ $pagination['urls']['prev'] ?? '#' }}"
                   aria-label="@lang('evoPackageManager::global.previous')">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            {{-- Номера страниц --}}
            @foreach($pagination['pages'] ?? [] as $index => $page)
                @if($page === '...')
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                @else
                    <li class="page-item {{ $page === $pagination['current_page'] ? 'active' : '' }}">
                        {{-- 🔑 Используем готовую ссылку из page_urls --}}
                        <a class="page-link" href="{{ $pagination['page_urls'][$index] ?? '#' }}">
                            {{ $page }}
                        </a>
                    </li>
                @endif
            @endforeach

            {{-- Кнопка "Вперёд" --}}
            <li class="page-item {{ !$pagination['has_next'] ? 'disabled' : '' }}">
                <a class="page-link"
                   href="{{ $pagination['urls']['next'] ?? '#' }}"
                   aria-label="@lang('evoPackageManager::global.next')">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>

        {{-- Инфо о записях --}}
        <small class="text-muted d-block text-center mt-2">
            @lang('evoPackageManager::global.page') {{ $pagination['current_page'] }} @lang('evoPackageManager::global.page_of') {{ $pagination['total_pages'] }}
            (@lang('evoPackageManager::global.total') {{ $pagination['total'] }} @lang('evoPackageManager::global.records'))
        </small>
    </nav>
@endif
