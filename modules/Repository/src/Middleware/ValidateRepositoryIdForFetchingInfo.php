<?php

namespace Modules\Repository\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Models\Repository;

class ValidateRepositoryIdForFetchingInfo
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $id = $request->route('repoId');
        try {
            $repository = Repository::query()->find($id);
        } catch (QueryException $exception) {
            return $this->errorResponse(status: 500);
        }

        // Validate if the repository exists
        if (!$repository) {
            return $this->errorResponse(RepositoryResponseEnums::REPOSITORY_NOT_FOUND);
        }

        return $next($request);
    }
}
