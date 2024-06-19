<?php

namespace Modules\Repository\src\Middleware;


use App\Traits\ApiResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Models\Repository;
use Illuminate\Database\QueryException;

class ValidateRepositoryId
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
        $id = $request->route('id');
        try {
            $repository = Repository::query()->find($id);
        } catch (QueryException $exception) {
            return $this->errorResponse(status: 500);
        }

        // Validate if the repository exists
        if (!$repository) {
            return $this->errorResponse(RepositoryResponseEnums::REPOSITORY_NOT_FOUND);
        }

        // Add the repository to the request attributes
        $request->attributes->add(['id' => $id, 'owner' => $repository->owner, 'name' => $repository->name]);

        return $next($request);
    }
}
