<?php

namespace Modules\Repository\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Listeners\StoreBranches;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        RepositoryCreated::class => [
            StoreBranches::class
        ]
    ];
}
