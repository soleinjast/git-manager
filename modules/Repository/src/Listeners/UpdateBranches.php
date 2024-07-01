<?php

namespace Modules\Repository\src\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Modules\Repository\database\repository\BranchRepositoryInterface;
use Modules\Repository\src\DTOs\CreateBranchDetails;
use Modules\Repository\src\Events\BranchCreated;
use Modules\Repository\src\Events\BranchUpdated;
use Modules\Repository\src\Events\RepositoryUpdate;
use function App\helpers\github_service;

class UpdateBranches implements ShouldQueue
{
    public function __construct(protected Dispatcher $events, protected BranchRepositoryInterface $branchRepository)
    {
    }
    public function handle(RepositoryUpdate $event) : void
    {
        try {

            $branches = github_service($event->repository->token, $event->repository->owner, $event->repository->name)
                ->fetchBranches();
            foreach ($branches as $branch) {
                $createBranchDetails = new CreateBranchDetails($event->repository->id, $branch['name']);
                $branchDto = $this->branchRepository->updateOrCreate($createBranchDetails);
                $this->events->dispatch(new BranchUpdated($branchDto, $event->repository));
            }
        } catch (ConnectionException $exception){
            report($exception);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
