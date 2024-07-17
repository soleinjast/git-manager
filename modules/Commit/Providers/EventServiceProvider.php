<?php

namespace Modules\Commit\Providers;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as BaseEventServiceProvider;
use Modules\Commit\src\Events\CommitCreated;
use Modules\Commit\src\Events\CommitDeleted;
use Modules\Commit\src\Listeners\DeleteCommitFiles;
use Modules\Commit\src\Listeners\DeleteCommits;
use Modules\Commit\src\Listeners\StoreCommitFiles;
use Modules\Commit\src\Listeners\StoreCommits;
use Modules\Commit\src\Listeners\UpdateCommits;
use Modules\Repository\src\Events\BranchCreated;
use Modules\Repository\src\Events\BranchUpdated;
use Modules\Repository\src\Events\RepositoryDeleted;

class EventServiceProvider extends BaseEventServiceProvider
{
    protected $listen = [
        BranchCreated::class => [
            StoreCommits::class
        ],
        BranchUpdated::class => [
            UpdateCommits::class
        ],
        CommitCreated::class => [
            StoreCommitFiles::class
        ],
        RepositoryDeleted::class => [
            DeleteCommits::class
        ],
        CommitDeleted::class => [
            DeleteCommitFiles::class
        ]
    ];
}
