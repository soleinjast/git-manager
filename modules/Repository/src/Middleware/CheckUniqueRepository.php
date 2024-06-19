<?php

namespace Modules\Repository\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Repository\src\Models\Repository;

class CheckUniqueRepository
{
    use ApiResponse;
    public function handle(Request $request, Closure $next)
    {
        try {
            $repository = Repository::query()->where('owner', $request->get('owner'))
                ->where('name', $request->get('name'))
                ->exists();
            if ($repository) {
                return $this->errorResponse("Repository already exist!");
            }
        } catch (Exception $exception) {
            return $this->errorResponse("Create repository failed!");
        }
        return $next($request);
    }
}
