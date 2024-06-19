<?php

namespace Modules\Repository\src\Middleware;

use App\Enumerations\GithubApiResponses;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Token\src\Models\GithubToken;
use function App\helpers\github_service;

class CheckAccessibility
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        try {
            $githubToken = GithubToken::query()->find($request->get('github_token_id'));

            $githubService = github_service($githubToken->token, $request->get('owner'), $request->get('name'));

            if (!$githubService->checkAccess()) {
                return $this->errorResponse(RepositoryResponseEnums::REPOSITORY_NOT_FOUND);
            }
        } catch (ConnectionException $e) {
            return $this->errorResponse(GithubApiResponses::CONNECTION_ERROR, [], 500);
        }
        return $next($request);
    }
}
