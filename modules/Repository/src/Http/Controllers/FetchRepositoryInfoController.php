<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Repository\database\repository\RepositoryRepository;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Exceptions\RepositoryInfoFindFailedException;


class FetchRepositoryInfoController
{
    use ApiResponse;
    public function __construct(protected RepositoryRepositoryInterface $repositoryRepository)
    {

    }
    public function __invoke(int $repoId): JsonResponse
    {
        try {
            $repositoryDto = $this->repositoryRepository->findById($repoId);
            return $this->successResponse($repositoryDto);
        }catch (RepositoryInfoFindFailedException $exception){
            report($exception);
            return $this->errorResponse($exception->getMessage());
        }
    }
}
