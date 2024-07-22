<?php

namespace Modules\Repository\src\Http\Controllers;

use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Repository\src\Models\Repository;

class FetchCommitFlowController
{
    use ApiResponse;
    public function __invoke($repoId): JsonResponse
    {
        try {
            $repository = Repository::with(['commits'])->findOrFail($repoId);
            $commits = $repository->commits;

            $commitData = $commits->groupBy(function ($commit) {
                return Carbon::parse($commit->date)->format('Y-m-d');
            })->map(function ($dayCommits) {
                return $dayCommits->count();
            });
            return $this->successResponse($commitData);
        }catch (\Exception $exception){
            report($exception);
            return $this->errorResponse();
        }
    }
}
