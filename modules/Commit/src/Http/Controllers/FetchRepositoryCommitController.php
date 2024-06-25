<?php

namespace Modules\Commit\src\Http\Controllers;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Repository\database\repository\RepositoryRepositoryInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Exceptions\RetrieveRepositoryWithCommitsFailedException;

class FetchRepositoryCommitController
{
    use ApiResponse;
    public function __construct(protected RepositoryRepositoryInterface $repository)
    {

    }

    /**
     * @throws RetrieveRepositoryWithCommitsFailedException
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $repository = $this->repository->getRepositoryWithCommits(
                repositoryId: $request->repoId,
                perPage: 10,
                author: $request->get('author'),
                startDate: $request->start_date,
                endDate: $request->end_date);

            $commits = CommitDto::fromEloquentCollection($repository->commits->getCollection());
            $data_arr = [
                'repository' => RepositoryDto::fromEloquent($repository),
                'commits' => $commits,
                'current_page' => $repository->commits->currentPage(),
                'last_page' => $repository->commits->lastPage(),
                'next_page_url' => $repository->commits->nextPageUrl(),
                'prev_page_url' => $repository->commits->previousPageUrl(),
            ];
        }catch (RetrieveRepositoryWithCommitsFailedException $e) {
            report($e);
            return $this->errorResponse();
        }
        return $this->successResponse($data_arr);
    }
}
