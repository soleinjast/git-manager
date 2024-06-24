<?php

namespace Modules\Repository\src\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Repository\database\repository\BranchRepositoryInterface;
use Modules\Repository\src\DTOs\CreateBranchDetails;
use Modules\Repository\src\Events\BranchCreated;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Models\Branch;
use Illuminate\Contracts\Events\Dispatcher;

use function App\helpers\github_service;

class StoreBranches implements ShouldQueue
{
    use InteractsWithQueue;
    public function __construct(protected Dispatcher $events, protected BranchRepositoryInterface $branchRepository)
    {

    }

    public function handle(RepositoryCreated $event) : void
    {
        try {
            $branches = github_service($event->repository->token, $event->repository->owner, $event->repository->name)
                ->fetchBranches();
            foreach ($branches as $branch) {
                $createBranchDetails = new CreateBranchDetails($event->repository->id, $branch['name']);
                $branchDto = $this->branchRepository->create($createBranchDetails);
                $this->events->dispatch(new BranchCreated($branchDto, $event->repository));
            }
        } catch (ConnectionException $exception){
            report($exception);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
