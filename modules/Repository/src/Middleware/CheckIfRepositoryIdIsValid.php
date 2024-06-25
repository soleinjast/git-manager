<?php

namespace Modules\Repository\src\Middleware;

use App\Traits\ApiResponse;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Models\Repository;

class CheckIfRepositoryIdIsValid
{
    use ApiResponse;
    public function handle($request, $next)
    {
        $repositoryId = $request->route('repoId');
        if (!Repository::query()->find($repositoryId)) {
            return $this->errorResponse(RepositoryResponseEnums::REPOSITORY_NOT_FOUND);
        }
        return $next($request);

    }
}
