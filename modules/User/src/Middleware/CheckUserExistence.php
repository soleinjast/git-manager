<?php

namespace Modules\User\src\Middleware;

use App\Traits\ApiResponse;
use Modules\User\src\Models\User;

class CheckUserExistence
{
    use ApiResponse;
    public function handle($request, $next)
    {
        try {
            $foundedUser = User::query()->where('git_id', $request->git_id)
                ->where('repository_id', $request->repository_id)->first();
            if ($foundedUser) {
                return $next($request);
            } else {
                return $this->errorResponse("User not found!");
            }
        }Catch (\Exception $exception){
            report($exception);
            return $this->errorResponse('Operation failed!', [], 500);
        }
    }
}
