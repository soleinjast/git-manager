<?php


use Illuminate\Support\Facades\Route;
use Modules\Repository\src\Http\Controllers\AutoRepositoryController;
use Modules\Repository\src\Http\Controllers\CreateRepositoryController;
use Modules\Repository\src\Http\Controllers\FetchRepositoryController;
use Modules\Repository\src\Http\Controllers\FetchRepositoryInfoController;
use Modules\Repository\src\Http\Controllers\UpdateRepositoryController;
use Modules\Repository\src\Middleware\CheckAccessibility;
use Modules\Repository\src\Middleware\CheckUniqueRepository;
use Modules\Repository\src\Middleware\PrepareRequestForAutoCreation;
use Modules\Repository\src\Middleware\PrepareRequestForStoringRepository;
use Modules\Repository\src\Middleware\PrepareRequestForUpdatingRepository;
use Modules\Repository\src\Middleware\ValidateGithubOrganizationAccess;
use Modules\Repository\src\Middleware\ValidateGitHubUsernames;
use Modules\Repository\src\Middleware\ValidateRepositoryId;
use Modules\Repository\src\Middleware\ValidateRepositoryIdForFetchingInfo;

Route::post('create', CreateRepositoryController::class)
    ->middleware([
        PrepareRequestForStoringRepository::class,
        CheckUniqueRepository::class,
        CheckAccessibility::class
    ])->name('create');
Route::view('/', 'RepositoryApp::index')
    ->name('repository-list-view');
Route::post('/{id}/update', UpdateRepositoryController::class)
    ->middleware([
        PrepareRequestForUpdatingRepository::class,
        ValidateRepositoryId::class,
        CheckAccessibility::class
    ])->name('update');
Route::get('fetch', FetchRepositoryController::class)->name('fetch');
Route::get('/{repoId}/repositoryInfo', FetchRepositoryInfoController::class)
    ->middleware([ValidateRepositoryIdForFetchingInfo::class])
    ->name('info');
Route::view('auto-create', 'RepositoryApp::auto-create')
    ->name('auto-create-view');
Route::view('/{repoId}', 'RepositoryApp::show')
    ->name('repository-detail-view');
Route::post('auto-create', AutoRepositoryController::class)
    ->middleware([
        PrepareRequestForAutoCreation::class,
        ValidateGithubOrganizationAccess::class,
        ValidateGitHubUsernames::class
    ])->name('auto-create');

