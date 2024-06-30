<?php

namespace Modules\Commit\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commit\database\repository\CommitRepositoryInterface;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\Exceptions\FailedToFetchCommitWithCommitFiles;
use Modules\Repository\src\DTOs\RepositoryDto;

class FetchCommitDetailController
{
    use ApiResponse;
    public function __construct(protected CommitRepositoryInterface $commitRepository)
    {

    }
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $commitWithCommitFiles = $this->commitRepository->getCommitFilesBySha($request->repoId, $request->sha);
            $commit = CommitDto::fromEloquent($commitWithCommitFiles);
            $repository = RepositoryDto::fromEloquent($commitWithCommitFiles->repository);
            $data_arr = [
                'repository' => $repository,
                'commit' => $commit,
                'commitFiles' => $commitWithCommitFiles->commitFiles
            ];
            return $this->successResponse($data_arr);
        }catch (FailedToFetchCommitWithCommitFiles $e) {
            report($e);
            return $this->errorResponse();
        }
    }
}
