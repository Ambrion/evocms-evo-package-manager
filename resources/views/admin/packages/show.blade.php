@extends('evoPackageManager::layouts.manager')

@section('page-title', __('evoPackageManager::global.package_item') . ': ' . $package->name)

@section('actions')
    <div id="actions">
        <a href="{{ route('evoPackageManager::index') }}" class="btn btn-secondary">
            <i class="fa fa-list"></i> @lang('evoPackageManager::global.installed_package_list')
        </a>
    </div>
@endsection


@section('content')
    <div class="tab-page" id="tabEvoPackageManagerShow">
        <h2 class="tab">
            <i class="fa fa-eye"></i> @lang('evoPackageManager::global.package_item'): {{ $package->name }}
        </h2>
        <script>tpModule.addTabPage(document.getElementById('tabEvoPackageManagerShow'));</script>

        <div class="container-fluid px-4 py-3">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ $package->name }}</h5>
                            <span class="badge bg-primary text-white">{{ $package->version }}</span>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">@lang('evoPackageManager::global.status')</dt>
                                <dd class="col-sm-9">
                            <span class="badge bg-{{ $package->status === 'active' ? 'success' : 'secondary' }} text-white">
                                {{ $package->status }}
                            </span>
                                </dd>

                                <dt class="col-sm-3">@lang('evoPackageManager::global.type')</dt>
                                <dd class="col-sm-9">{{ $package->type }}</dd>

                                <dt class="col-sm-3">@lang('evoPackageManager::global.source')</dt>
                                <dd class="col-sm-9">{{ $package->source }}</dd>

                                @if($package->metadata)
                                    <dt class="col-sm-3">@lang('evoPackageManager::global.description')</dt>
                                    <dd class="col-sm-9">{{ $package->metadata['description'] ?? 'No description' }}</dd>

                                    @if(!empty($package->metadata['author']))
                                        <dt class="col-sm-3">@lang('evoPackageManager::global.author')</dt>
                                        <dd class="col-sm-9">
                                            {{ $package->metadata['author'] }}
                                            @if(!empty($package->metadata['email']))
                                                <small class="text-muted">(&lt;{{ $package->metadata['email'] }}&gt;)</small>
                                            @endif
                                        </dd>
                                    @endif
                                @endif

                                @if($package->installedAt)
                                    <dt class="col-sm-3">@lang('evoPackageManager::global.installed_at')</dt>
                                    <dd class="col-sm-9">{{ $package->installedAt }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if(!empty($package->requirements))
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">@lang('evoPackageManager::global.dependencies')</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($package->requirements as $dep => $constraint)
                                        <li class="py-1 border-bottom">
                                            <strong>{{ $dep }}</strong>: {{ $constraint }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">@lang('evoPackageManager::global.actions')</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <form action="{{ route('evoPackageManager::destroy', ['package' => $package->name]) }}"
                                      method="POST"
                                      onsubmit="return confirm('@lang('evoPackageManager::global.remove_package') «{{ $package->name }}»?\n\n @lang('evoPackageManager::global.action_cannot_undone').')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fa fa-trash"></i> @lang('evoPackageManager::global.action_remove_package')
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
