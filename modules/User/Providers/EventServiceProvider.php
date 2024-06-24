<?php

namespace Modules\User\Providers;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\User\src\Listeners\StoreCollaborators;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        RepositoryCreated::class => [
            StoreCollaborators::class
        ]
    ];
}
