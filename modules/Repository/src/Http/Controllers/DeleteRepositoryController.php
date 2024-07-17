<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Events\RepositoryDeleted;
use Modules\Repository\src\Exceptions\RepositoryDeletionFailedException;
use Modules\Repository\src\Exceptions\RepositoryInfoFindFailedException;

class DeleteRepositoryController
{
    use ApiResponse;
    public function __construct(protected RepositoryRepositoryInterface $repositoryRepository, protected Dispatcher $events)
    {

    }
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $repositoryDto = $this->repositoryRepository->findById($request->id);
            $this->repositoryRepository->delete($request->id);
            $this->events->dispatch(new RepositoryDeleted($repositoryDto));
        } catch (RepositoryDeletionFailedException | RepositoryInfoFindFailedException $exception) {
            report($exception);
            return $this->errorResponse('operation failed', [], 500);
        }
        return $this->successResponse([]);
    }
}
