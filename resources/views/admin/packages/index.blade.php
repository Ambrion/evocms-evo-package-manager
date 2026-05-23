@extends('evoPackageManager::layouts.manager')

@section('page-title', __('evoPackageManager::global.installed_packages_list'))

@section('actions')
    <div id="actions">
        <a href="{{ route('evoPackageManager::sync') }}"
           class="btn btn-secondary"
           onclick="return confirm('Synchronize package registry?')">
            <i class="fa fa-sync"></i> @lang('evoPackageManager::global.action_sync_registry')
        </a>
        <a href="{{ route('evoPackageManager::install.create') }}" class="btn btn-success">
            <i class="fa fa-plus"></i> @lang('evoPackageManager::global.action_install_package')
        </a>
    </div>
@endsection

@section('content')
    <div class="tab-page" id="tabEvoPackageManagerList">
        <h2 class="tab">
            <i class="fa fa-cube"></i> @lang('evoPackageManager::global.installed_package_list')
        </h2>
        <script type="text/javascript">
            tpModule.addTabPage(document.getElementById('tabEvoPackageManagerList'));
        </script>
        @include('evoPackageManager::admin.packages.partials.list')
    </div>

    <div class="tab-page" id="tabEvoPackageManagerAbout">
        <h2 class="tab">
            <i class="fa fa-info"></i> @lang('evoPackageManager::global.about_module')
        </h2>
        <script type="text/javascript">
            tpModule.addTabPage(document.getElementById('tabEvoPackageManagerAbout'));
        </script>
        @include('evoPackageManager::admin.packages.about.index')
    </div>
@endsection
