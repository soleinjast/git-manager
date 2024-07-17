<?php

namespace Modules\Commit\src\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Commit\src\Events\CommitDeleted;
use Modules\Commit\src\Models\Commit;
use Modules\Repository\src\Events\RepositoryDeleted;

class DeleteCommits implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RepositoryDeleted $event) : void
    {
        $repositoryId = $event->repository->id;
        try {
            Commit::query()->where('repository_id', $repositoryId)->chunkById(100, function ($commits) {
                foreach ($commits as $commit) {
                    event(new CommitDeleted($commit));
                    $commit->delete();
                }
            });
        }catch (Exception $e) {
            report($e);
        }
    }
}
