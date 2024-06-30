<?php

use Illuminate\Support\Facades\Route;
use Modules\Commit\src\Http\Controllers\FetchCommitDetailController;
use Modules\Commit\src\Http\Controllers\FetchRepositoryCommitController;
use Modules\Commit\src\Middleware\CheckIfCommitShaIsValid;
use Modules\Repository\src\Middleware\CheckIfRepositoryIdIsValid;

Route::middleware([CheckIfRepositoryIdIsValid::class])->prefix('{repoId}/commits')->group(function () {
    Route::get('fetch', FetchRepositoryCommitController::class)->name('fetch');
    Route::get('/{sha}/fetch', FetchCommitDetailController::class)->name('fetch-commit-detail')
    ->middleware([CheckIfCommitShaIsValid::class]);
});
