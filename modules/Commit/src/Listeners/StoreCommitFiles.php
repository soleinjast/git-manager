<?php

namespace Modules\Commit\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Commit\src\Events\CommitCreated;
use Modules\Commit\src\Jobs\ProcessCommitFile;
use function App\helpers\github_service;

class StoreCommitFiles implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CommitCreated $event) : void
    {
        $commitFiles = github_service($event->repositoryDto->token, $event->repositoryDto->owner, $event->repositoryDto->name)
            ->fetchCommitFiles($event->commit->sha);
        foreach ($commitFiles as $fileData) {
            ProcessCommitFile::dispatch($fileData, $event->commit->id, $event->commit->is_first_commit);
        }
    }
}
