<?php

declare(strict_types=1);

use EvolutionCMS\EvoPackageManager\Presentation\Http\Controllers\Admin\PackageAdminController;
use Illuminate\Support\Facades\Route;

/**
 * Маршруты для модуля управления пакетами в админке EvolutionCMS CE 3.
 *
 * Поддерживает:
 * - Традиционные формы (POST) для совместимости с админкой
 * - Опциональный JSON API через ?api=1 или заголовок Accept
 */

// Список установленных пакетов (главная страница модуля)
Route::get('', [PackageAdminController::class, 'index'])
    ->name('evoPackageManager::index');

// Форма установки нового пакета
Route::get('install', [PackageAdminController::class, 'create'])
    ->name('evoPackageManager::install.create');

// Обработка установки пакета (форма -> POST)
Route::post('install', [PackageAdminController::class, 'store'])
    ->name('evoPackageManager::install.store');

// Детали пакета: просмотр информации
Route::get('show/{package}', [PackageAdminController::class, 'show'])
    ->where(['package' => '^[a-z0-9\-_]+/[a-z0-9\-_]+$'])
    ->name('evoPackageManager::show');

// Удаление пакета: форма с подтверждением -> POST
Route::post('show/{package}/delete', [PackageAdminController::class, 'destroy'])
    ->where(['package' => '^[a-z0-9\-_]+/[a-z0-9\-_]+$'])
    ->name('evoPackageManager::destroy');

// Синхронизация реестра пакетов с installed.json
Route::get('sync', [PackageAdminController::class, 'sync'])
    ->name('evoPackageManager::sync');
