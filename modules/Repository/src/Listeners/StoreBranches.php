<?php

namespace Modules\Repository\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Models\Branch;
use function App\helpers\github_service;

class StoreBranches implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RepositoryCreated $event) : void
    {
        try {
            $branches = github_service($event->repository->token, $event->repository->owner, $event->repository->name)
                ->fetchBranches();
            foreach ($branches as $branch) {
                Branch::query()->create([
                    'repository_id' => $event->repository->id,
                    'name' => $branch['name'],
                ]);
            }
        } catch (ConnectionException $exception){
            report($exception);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
