<?php

use Illuminate\Support\Facades\Route;
use Modules\User\src\Http\Controllers\UpdateUserController;
use Modules\User\src\Middleware\CheckUserExistence;
use Modules\User\src\Middleware\PrepareRequestForUpdatingUser;

Route::post('update', UpdateUserController::class)
    ->middleware([PrepareRequestForUpdatingUser::class, CheckUserExistence::class])
    ->name('update');
