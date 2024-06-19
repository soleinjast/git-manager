<?php

namespace Modules\Repository\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrepareRequestForUpdatingRepository
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
        return $request->only(['github_token_id', 'deadline']);
    }
    private function getRules() : array
    {
        return [
            'deadline' => 'date|after:today',
            'github_token_id' => 'required|exists:github_tokens,id'
        ];
    }
}
