<?php

namespace Modules\User\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Events\RepositoryUpdate;
use Modules\User\src\Jobs\CreateUser;
use function App\helpers\github_service;

class UpdateCollaborators implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RepositoryUpdate $event) : void
    {
        $collaborators = github_service($event->repository->token,
            $event->repository->owner,
            $event->repository->name)->getCollaborators();
        foreach ($collaborators as $collaborator) {
            CreateUser::dispatch($event->repository, $collaborator);
        }
    }

}
