{{-- Авто-переключатель языка для документации --}}
@includeIf("evoPackageManager::admin.packages.about.partials.about-{$managerLang}", ['managerLang' => $managerLang])

{{-- Fallback на английский, если перевод не найден --}}
@unless(view()->exists("evoPackageManager::admin.packages.about.partials.about-{$managerLang}"))
    @include('evoPackageManager::admin.packages.about.partials.about-en', ['managerLang' => $managerLang])
@endunless

<style>
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
        border-color: #0d6efd !important;
        transform: translateY(-2px);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Save checkbox state in localStorage
        document.querySelectorAll('.alert input[type="checkbox"]').forEach(cb => {
            const key = 'evoPackageManager_checklist_' + cb.parentElement.textContent.trim().substring(0, 30);
            cb.checked = localStorage.getItem(key) === '1';
            cb.addEventListener('change', () => {
                localStorage.setItem(key, cb.checked ? '1' : '0');
            });
        });
    });
</script>

