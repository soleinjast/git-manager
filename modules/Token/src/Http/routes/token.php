<?php


use Illuminate\Support\Facades\Route;
use Modules\Token\src\Http\Controllers\CreateGithubTokenController;
use Modules\Token\src\Http\Controllers\DeleteTokenController;
use Modules\Token\src\Http\Controllers\FetchGithubTokenController;
use Modules\Token\src\Middleware\CheckRepositoriesAddedWithThisTokenId;
use Modules\Token\src\Middleware\PrepareRequestForCreatingToken;
use Modules\Token\src\Middleware\ValidateAndPassTokenId;
use Modules\Token\src\Middleware\ValidateGithubToken;

Route::view('', 'TokenApp::index')->name('index');
Route::get('fetch', FetchGithubTokenController::class)
    ->name('fetch');
Route::post('create', CreateGithubTokenController::class)
    ->middleware([PrepareRequestForCreatingToken::class, ValidateGithubToken::class])
    ->name('create');
Route::post('{tokenId}/delete', DeleteTokenController::class)
    ->middleware([ValidateAndPassTokenId::class, CheckRepositoriesAddedWithThisTokenId::class])->name('delete');
