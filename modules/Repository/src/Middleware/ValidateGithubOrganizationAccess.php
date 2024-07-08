<?php

namespace Modules\Repository\src\Middleware;

use App\Enumerations\GithubApiResponses;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Http;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Token\src\Models\GithubToken;

class ValidateGithubOrganizationAccess
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

        $tokenId = $request->input('token_id');
        $organization = $request->input('organization');
        $githubToken = GithubToken::query()->find($tokenId);
        try {
            $response = Http::withToken($githubToken->token)
                ->get("https://api.github.com/orgs/{$organization}/memberships/{$githubToken->login_name}");
            if ($response->failed() || $response->json('state') !== 'active' || $response->json('role') !== 'admin') {
                return $this->errorResponse(RepositoryResponseEnums::ACCESS_FAILED_FOR_CREATING_REPOSITORY_UNDER_ORGANIZATION, [], 403);
            }
        } catch (ConnectionException $e) {
            return $this->errorResponse(GithubApiResponses::CONNECTION_ERROR, [], 500);
        }
        return $next($request);
    }
}
