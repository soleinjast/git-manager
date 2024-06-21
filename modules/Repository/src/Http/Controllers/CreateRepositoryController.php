<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\StoreRepositoryResponse;
use Modules\Repository\src\Events\RepositoryCreated;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;

class CreateRepositoryController
{
    use ApiResponse;
    public function __construct(protected RepositoryRepositoryInterface $repositoryRepository, protected Dispatcher $events)
    {

    }

    public function __invoke(): JsonResponse
    {
        try {
            $createRepositoryDetails = new CreateRepositoryDetails(
                request('github_token_id'),
                request('owner'),
                request('name'),
                request('deadline')
            );
            $repositoryDto = $this->repositoryRepository->create($createRepositoryDetails);
            $storeResponseData = new StoreRepositoryResponse($repositoryDto->owner, $repositoryDto->name);
            $this->events->dispatch(new RepositoryCreated($repositoryDto));
            return $this->successResponse($storeResponseData->toArray());
        }catch (RepositoryCreationFailedException $exception) {
            report($exception);
            return $this->errorResponse($exception->getMessage(), [], 500);
        }

    }
}
