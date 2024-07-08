<?php

namespace Modules\Repository\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubCreated;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubInitiated;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Events\RepositoryUpdate;
use Modules\Repository\src\Listeners\CreateRepositoryOnGit;
use Modules\Repository\src\Listeners\StoreBranches;
use Modules\Repository\src\Listeners\StoreRepositoryAndCollaboratorsOnLocal;
use Modules\Repository\src\Listeners\UpdateBranches;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        RepositoryCreated::class => [
            StoreBranches::class
        ],
        RepositoryUpdate::class => [
            UpdateBranches::class
        ],
        RepositoriesCreationOnGithubInitiated::class => [
            CreateRepositoryOnGit::class,
        ],
        RepositoriesCreationOnGithubCreated::class => [
            StoreRepositoryAndCollaboratorsOnLocal::class
        ]
    ];
}
