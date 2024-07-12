<?php

namespace Modules\User\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrepareRequestForUpdatingUser
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $validator = Validator::make($this->getData($request), $this->getRules());
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->getMessages() as $field => $message) {
                $errors[] = [
                    'field' => $field,
                    'message' => $message[0]
                ];
            }
            return $this->errorResponse("Operation failed!", $errors, 422);
        }
        return $next($request);
    }
    private function getData(Request $request) : array
    {
        return $request->only(['git_id' , 'repository_id', 'login_name', 'name', 'avatar_url', 'university_username',
            'status']);
    }
    private function getRules() : array
    {
        return [
            'git_id' => 'required|string',
            'repository_id' => 'required|string',
            'login_name' => 'required|string',
            'name' => 'string|nullable',
            'avatar_url' => 'required|string',
            'university_username' => 'string',
            'status' => 'required|string'
        ];
    }
}
