<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\UpdateRepositoryDetails;
use Modules\Repository\src\DTOs\UpdateRepositoryResponse;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;

class UpdateRepositoryController
{
    use ApiResponse;
    public function __construct(protected RepositoryRepositoryInterface $repositoryRepository)
    {

    }

    public function __invoke(): JsonResponse
    {
        try {
            $updateRepository = new UpdateRepositoryDetails(
                request('id'),
                request('github_token_id'),
                request('deadline')
            );
            $repositoryDto = $this->repositoryRepository->update($updateRepository);
            $updateRepositoryResponse = new UpdateRepositoryResponse(
                $repositoryDto->id,
                $repositoryDto->name,
                $repositoryDto->owner,
                $repositoryDto->deadline,
                $repositoryDto->github_token_id
            );
            return $this->successResponse($updateRepositoryResponse);
        }catch (RepositoryUpdateFailedException $exception) {
            report($exception);
            return $this->errorResponse($exception->getMessage(), [], 500);
        }
    }
}
