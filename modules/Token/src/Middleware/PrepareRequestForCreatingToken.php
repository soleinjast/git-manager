<?php

namespace Modules\Token\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PrepareRequestForCreatingToken
{
    use ApiResponse;
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
        return $request->only(['token']);
    }
    private function getRules() : array
    {
        return [
            'token' => [
                'required',
                'string',
                Rule::unique('github_tokens', 'token'),
            ],
        ];
    }
}
