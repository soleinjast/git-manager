<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;

class FetchRepositoryController
{
    use ApiResponse;
    public function __construct(protected RepositoryRepositoryInterface $repositoryRepository )
    {

    }

    public function __invoke(): JsonResponse
    {
        try {
            $searchName = request()->query('search_name');
            $searchOwner = request()->query('search_owner');
            $filter_deadline = request()->query('filter_deadline');
            $filter_token_id = request()->query('filter_token');
            $repositories = $this->repositoryRepository->fetchAll($searchName, $searchOwner, $filter_deadline, $filter_token_id);
            return $this->successResponse($repositories);
        }catch (RepositoryRetrievalFailedException $exception){
            report($exception);
            return $this->errorResponse(message: $exception->getMessage() ,status: 500);
        }
    }
}
