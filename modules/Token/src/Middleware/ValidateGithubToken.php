<?php

namespace Modules\Token\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Token\src\Enumerations\GithubTokenApiResponses;

class ValidateGithubToken
{
    use ApiResponse;
    public function handle($request, Closure $next)
    {
        try {
            if ($userInfo = $this->getUserGithubInfoBaseToken($request->token)) {
                $request->merge(['login_name' => $userInfo['login'], 'githubId' => $userInfo['id']]);
                return $next($request);
            } else {
                return $this->errorResponse(GithubTokenApiResponses::InvalidToken, [], 401);
            }
        } catch (ConnectionException $e) {
            return $this->errorResponse(GithubTokenApiResponses::ConnectionError, [], 503);
        } catch (\Exception $e) {
            return $this->errorResponse(GithubTokenApiResponses::ServerError, [], 500);
        }
    }

    private function getUserGithubInfoBaseToken($token)
    {
        $response = Http::withToken($token)->get('https://api.github.com/user');
        if ($response->successful()) {
            return $response->json();
        }
        return null;
    }

}
