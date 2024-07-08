<?php

namespace Modules\Repository\src\Middleware;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Validator;
use Modules\Repository\src\Rules\SufficientMembers;
use Modules\Repository\src\Rules\UniqueArrayValues;
use Modules\Repository\src\Rules\ValidMemberFields;

class PrepareRequestForAutoCreation
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
        $validator = Validator::make($this->getData($request), $this->getRules($request));
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
        return $request->only(['group_count', 'members_per_group', 'members', 'token_id', 'organization',
            'deadline']);
    }
    private function getRules(Request $request) : array
    {
        return [
            'group_count' => 'required|integer|min:1',
            'members_per_group' => 'required|integer|min:1',
            'token_id' => 'required|integer|exists:github_tokens,id',
            'organization' => 'required|string',
            'deadline' => 'required|date|after:today',
            'members' => ['required', 'array', new ValidMemberFields(), new UniqueArrayValues(),
                new SufficientMembers($request->input('group_count', 1),
                    $request->input('members_per_group', 1))]
        ];
    }
}
