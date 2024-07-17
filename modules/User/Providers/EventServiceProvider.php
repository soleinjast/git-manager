<?php

namespace Modules\User\Providers;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Events\RepositoryDeleted;
use Modules\Repository\src\Events\RepositoryUpdate;
use Modules\User\src\Listeners\DeleteCollaborators;
use Modules\User\src\Listeners\StoreCollaborators;
use Modules\User\src\Listeners\UpdateCollaborators;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        RepositoryCreated::class => [
            StoreCollaborators::class
        ],
        RepositoryDeleted::class => [
            DeleteCollaborators::class
        ],
        RepositoryUpdate::class => [
            UpdateCollaborators::class
        ],
    ];
}
