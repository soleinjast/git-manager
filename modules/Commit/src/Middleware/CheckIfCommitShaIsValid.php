<?php

namespace Modules\Commit\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Modules\Commit\Enumerations\CommitResponseEnums;
use Modules\Commit\src\Models\Commit;


class CheckIfCommitShaIsValid
{
    use ApiResponse;
    public function handle($request, Closure $next)
    {
        $sha = $request->route('sha');
        $repoId = $request->route('repoId');

        // Check if the commit SHA is valid for the given repository
        $commit = Commit::query()->where('sha', $sha)->where('repository_id', $repoId)->first();

        if (!$commit) {
            return $this->errorResponse(CommitResponseEnums::COMMIT_NOT_FOUND);
        }

        return $next($request);
    }
}
