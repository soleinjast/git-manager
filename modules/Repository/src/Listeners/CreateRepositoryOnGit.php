<?php

namespace Modules\Repository\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Events\Dispatcher;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubCreated;
use Modules\Repository\src\Events\RepositoriesCreationOnGithubInitiated;
use function App\helpers\github_service;

class CreateRepositoryOnGit implements ShouldQueue
{
    use InteractsWithQueue;
    public function __construct(protected Dispatcher $events)
    {

    }

    public function handle(RepositoriesCreationOnGithubInitiated $event): void
    {
        try {
            github_service($event->githubToken->token, $event->repositoryDetails->owner, $event->repositoryDetails->name)
                ->createRepository();
        }catch (ConnectionException $exception){
            report($exception);
            return;
        }
        foreach ($event->members as $member) {
            try {
                github_service($event->githubToken->token, $event->repositoryDetails->owner, $event->repositoryDetails->name)
                    ->addCollaborator($member);
            } catch (ConnectionException $e) {
                report($e);
                return;
            }
        }
        $this->events->dispatch(new RepositoriesCreationOnGithubCreated(repositoryDetails: $event->repositoryDetails, members: $event->members, githubToken: $event->githubToken));
    }
}
