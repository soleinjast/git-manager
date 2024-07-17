<?php

namespace Modules\User\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Repository\src\Events\RepositoryDeleted;
use Modules\Repository\src\Models\Repository;

class DeleteCollaborators implements ShouldQueue
{
    use InteractsWithQueue;
    public function handle(RepositoryDeleted $event) : void
    {
        try {
            $repository = Repository::query()->find($event->repository->id);
            $repository->collaborators()->delete();
        }catch (\Exception $e) {
            report($e);
        }
    }
}
