@extends('evoPackageManager::layouts.manager')

@section('page-title', __('evoPackageManager::global.install_new_package'))

@section('actions')
    <div id="actions">
        <a href="{{ route('evoPackageManager::index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> @lang('evoPackageManager::global.cancel')
        </a>
    </div>
@endsection

@section('content')
    <div class="tab-page" id="tab_package_install">
        <h2 class="tab">
            <i class="fa fa-download"></i> @lang('evoPackageManager::global.install_new_package')
        </h2>
        <script type="text/javascript">
            if (typeof tpModule !== 'undefined') {
                tpModule.addTabPage(document.getElementById('tab_package_install'));
            }
        </script>

        <form method="POST" action="{{ route('evoPackageManager::install.store') }}">
            <div class="container-fluid px-4">
                <div class="row g-4 mb-5">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white py-3">
                                <h5 class="mb-0 fs-5">
                                    <i class="fa fa-cube"></i> @lang('evoPackageManager::global.package_details')
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                @csrf

                                {{-- Package Name --}}
                                <div class="mb-4">
                                    <label for="package" class="form-label fw-bold fs-6">
                                        @lang('evoPackageManager::global.package_name') <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="package" name="package"
                                           class="form-control {{ session('errors') && session('errors')->has('package') ? 'is-invalid' : '' }}"
                                           value="{{ old('package') }}"
                                           placeholder="vendor/package-name"
                                           pattern="[a-z0-9\-_]+/[a-z0-9\-_]+"
                                           required>

                                    {{-- Ручное отображение ошибки (без @error) --}}
                                    @if(session('errors') && session('errors')->has('package'))
                                        <div class="invalid-feedback d-block">
                                            {{ session('errors')->first('package') }}
                                        </div>
                                    @endif

                                    <div class="form-text fs-6 mt-2">
                                        <i class="fa fa-info-circle me-1"></i>
                                        @lang('evoPackageManager::global.format'): <code>vendor/package-name</code> (lowercase, letters, numbers, hyphens, underscores)
                                    </div>
                                </div>

                                {{-- Version Constraint --}}
                                <div class="mb-4">
                                    <label for="version" class="form-label fw-bold fs-6">
                                        @lang('evoPackageManager::global.version_constraint') <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="version" name="version"
                                           class="form-control {{ session('errors') && session('errors')->has('version') ? 'is-invalid' : '' }}"
                                           value="{{ old('version', '*') }}"
                                           placeholder="*"
                                           required>

                                    @if(session('errors') && session('errors')->has('version'))
                                        <div class="invalid-feedback d-block">
                                            {{ session('errors')->first('version') }}
                                        </div>
                                    @endif

                                    <div class="form-text fs-6 mt-2">
                                        <i class="fa fa-info-circle me-1"></i>
                                        @lang('evoPackageManager::global.examples'): <code>*</code> (@lang('evoPackageManager::global.any')), <code>^1.0</code> (@lang('evoPackageManager::global.compatible')), <code>~2.3</code> (@lang('evoPackageManager::global.approximate')), <code>dev-main</code> (@lang('evoPackageManager::global.branch'))
                                    </div>
                                </div>

                                {{-- Run Composer Update --}}
                                <div class="mb-4">
                                    <label class="form-label fw-bold fs-6">@lang('evoPackageManager::global.installation_options'):</label>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="run_composer" value="0">
                                        <input type="checkbox" class="form-check-input" id="run_composer"
                                               name="run_composer" value="1"
                                                {{ old('run_composer', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label fs-6" for="run_composer">
                                            <span class="text-success fw-bold">@lang('evoPackageManager::global.installation_options_help_text')</span>
                                        </label>
                                    </div>
                                    <div class="form-text fs-6 mt-2">
                                        <i class="fa fa-info-circle me-1"></i>
                                        @lang('evoPackageManager::global.installation_options_help_text_2')
                                    </div>
                                </div>

                                {{-- Info Alert --}}
                                <div class="alert alert-info small">
                                    <i class="fa fa-info-circle me-1"></i>
                                    @lang('evoPackageManager::global.alert_text')
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Preview / Help --}}
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white py-3">
                                <h5 class="mb-0 fs-5">
                                    <i class="fa fa-lightbulb"></i> @lang('evoPackageManager::global.tips')
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <h6 class="fw-bold fs-6 mb-3">@lang('evoPackageManager::global.finding_packages')</h6>
                                <ul class="list-unstyled small mb-4">
                                    <li class="mb-2">
                                        <i class="fa fa-external-link text-primary me-1"></i>
                                        @lang('evoPackageManager::global.search_on') <a href="https://packagist.org" target="_blank">Packagist.org</a>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fa fa-github text-dark me-1"></i>
                                        @lang('evoPackageManager::global.check_github_for') <code>composer.json</code>
                                    </li>
                                    <li>
                                        <i class="fa fa-book text-info me-1"></i>
                                        @lang('evoPackageManager::global.read_vendor_documentation')
                                    </li>
                                </ul>

                                <h6 class="fw-bold fs-6 mb-3">@lang('evoPackageManager::global.version_examples')</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered small mb-0">
                                        <tbody>
                                        <tr><td><code>*</code></td><td>@lang('evoPackageManager::global.any_version')</td></tr>
                                        <tr><td><code>^1.2</code></td><td>≥1.2.0, &lt;2.0.0</td></tr>
                                        <tr><td><code>~1.2.3</code></td><td>≥1.2.3, &lt;1.3.0</td></tr>
                                        <tr><td><code>1.2.*</code></td><td>@lang('evoPackageManager::global.any_12x_version')</td></tr>
                                        <tr><td><code>dev-main</code></td><td>@lang('evoPackageManager::global.latest_branch')</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white text-end py-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-download"></i> @lang('evoPackageManager::global.action_install_package')
                                </button>
                                <a href="{{ route('evoPackageManager::index') }}" class="btn btn-secondary ms-2">
                                    <i class="fa fa-times"></i> @lang('evoPackageManager::global.cancel')
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            // Live validation for package name format
            document.getElementById('package')?.addEventListener('input', function(e) {
                const value = e.target.value;
                const isValid = /^[a-z0-9\-_]+\/[a-z0-9\-_]+$/i.test(value);

                // Remove existing validation classes
                e.target.classList.remove('is-valid', 'is-invalid');

                // Add visual feedback only if user has typed something
                if (value.length > 0) {
                    e.target.classList.add(isValid ? 'is-valid' : 'is-invalid');
                }
            });

            // Sync checkbox hidden field (for EvolutionCMS form handling)
            document.getElementById('run_composer')?.addEventListener('change', function(e) {
                // No additional logic needed — hidden field + checkbox pattern handles it
            });
        </script>
    @endpush
@endsection
