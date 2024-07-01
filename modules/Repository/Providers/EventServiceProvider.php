<?php

namespace Modules\Repository\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Events\RepositoryUpdate;
use Modules\Repository\src\Listeners\StoreBranches;
use Modules\Repository\src\Listeners\UpdateBranches;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        RepositoryCreated::class => [
            StoreBranches::class
        ],
        RepositoryUpdate::class => [
            UpdateBranches::class
        ]
    ];
}
