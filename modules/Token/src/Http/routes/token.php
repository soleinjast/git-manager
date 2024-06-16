<?php


use Illuminate\Support\Facades\Route;
use Modules\Token\src\Http\Controllers\CreateGithubTokenController;
use Modules\Token\src\Http\Controllers\FetchGithubTokenController;
use Modules\Token\src\Middleware\PrepareRequestForCreatingToken;
use Modules\Token\src\Middleware\ValidateGithubToken;


Route::get('fetch', FetchGithubTokenController::class)
    ->name('fetch');
Route::post('create', CreateGithubTokenController::class)
    ->middleware([PrepareRequestForCreatingToken::class, ValidateGithubToken::class])
    ->name('create');
