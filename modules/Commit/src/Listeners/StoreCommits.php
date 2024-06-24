<?php

namespace Modules\Commit\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Jobs\ProcessCommit;
use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\Events\BranchCreated;
use function App\helpers\github_service;

class StoreCommits implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected CommitRepositoryInterface $commitRepository)
    {
    }

    public function handle(BranchCreated $event) : void
    {
        try {
            $commits = github_service($event->repository->token, $event->repository->owner, $event->repository->name)
                ->fetchCommits($event->branch->name);
            foreach ($commits as $commit) {
                $createCommitDetails = new CreateCommitDetails(
                    $event->repository->id,
                    $commit['sha'],
                    $commit['commit']['message'],
                    $commit['commit']['author']['name'],
                    $commit['commit']['author']['date'],
                    !$commit['parents'],
                    $commit['author']['id'] ?? null,
                );
                ProcessCommit::dispatch($createCommitDetails);
            }
        } catch (ConnectionException $exception) {
            report($exception);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
