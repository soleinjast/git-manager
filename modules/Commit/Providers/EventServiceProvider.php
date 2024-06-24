<?php

namespace Modules\Commit\Providers;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Commit\src\Listeners\StoreCommits;
use Modules\Repository\src\Events\BranchCreated;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        BranchCreated::class => [
            StoreCommits::class
        ]
    ];
}
