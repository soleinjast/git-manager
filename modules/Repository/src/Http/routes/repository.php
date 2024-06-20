<?php


use Illuminate\Support\Facades\Route;
use Modules\Repository\src\Http\Controllers\CreateRepositoryController;
use Modules\Repository\src\Http\Controllers\FetchRepositoryController;
use Modules\Repository\src\Http\Controllers\UpdateRepositoryController;
use Modules\Repository\src\Middleware\CheckAccessibility;
use Modules\Repository\src\Middleware\CheckUniqueRepository;
use Modules\Repository\src\Middleware\PrepareRequestForStoringRepository;
use Modules\Repository\src\Middleware\PrepareRequestForUpdatingRepository;
use Modules\Repository\src\Middleware\ValidateRepositoryId;

Route::post('create', CreateRepositoryController::class)
    ->middleware([
        PrepareRequestForStoringRepository::class,
        CheckUniqueRepository::class,
        CheckAccessibility::class
    ])->name('create');


Route::post('/{id}/update', UpdateRepositoryController::class)
    ->middleware([
        PrepareRequestForUpdatingRepository::class,
        ValidateRepositoryId::class,
        CheckAccessibility::class
    ])->name('update');

Route::get('fetch', FetchRepositoryController::class)->name('fetch');