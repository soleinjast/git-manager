<?php

use Illuminate\Support\Facades\Route;
use Modules\Commit\src\Http\Controllers\FetchRepositoryCommitController;
use Modules\Repository\src\Middleware\CheckIfRepositoryIdIsValid;

Route::middleware([CheckIfRepositoryIdIsValid::class])->prefix('{repoId}/commits')->group(function () {
    Route::get('fetch', FetchRepositoryCommitController::class)->name('fetch');
});
