<?php

namespace Modules\Commit\src\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Commit\src\Jobs\ProcessCommit;
use Modules\Repository\src\Events\BranchUpdated;
use function App\helpers\github_service;

class UpdateCommits implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BranchUpdated $event) : void
    {
        try {
            $commits = github_service($event->repository->token, $event->repository->owner, $event->repository->name)
                ->fetchCommits($event->branch->name);
            foreach ($commits as $commit) {
                ProcessCommit::dispatch($event->repository, $commit);
            }
        } catch (ConnectionException $exception) {
            report($exception);
        } catch (Exception $e) {
            report($e);
        }
    }
}
