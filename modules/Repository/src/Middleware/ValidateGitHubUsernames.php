<?php

namespace Modules\Repository\src\Middleware;

use App\Traits\ApiResponse;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Token\src\Models\GithubToken;

class ValidateGitHubUsernames
{
    use ApiResponse;

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $members = $request->input('members');
        $token_id = $request->input('token_id');
        $token = GithubToken::query()->find($token_id)->token;
        $usernames = array_column($members, 'github_username');

        $userData = $this->fetchGitHubUserData($usernames, $token);

        foreach ($members as $key => $member) {
            $data = $userData[$member['github_username']] ?? null;
            if ($data === null) {
                return $this->errorResponse("Invalid GitHub username: {$member['github_username']}", [], 400);
            }
            $members[$key]['git_id'] = $data['id'];
            $members[$key]['name'] = $data['name'] ?? null;
            $members[$key]['avatar_url'] = $data['avatar_url'] ?? null;
        }

        $request->merge(['members' => $members]);

        return $next($request);
    }

    /**
     * Fetch GitHub user data concurrently.
     *
     * @param array $usernames
     * @param string $token
     * @return array
     */
    private function fetchGitHubUserData(array $usernames, string $token): array
    {
        $promises = [];

        foreach ($usernames as $username) {
            $promises[$username] = $this->client->getAsync("https://api.github.com/users/{$username}", [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/vnd.github.v3+json',
                ]
            ]);
        }

        $results = Utils::settle($promises)->wait();
        $userData = [];

        foreach ($results as $username => $result) {
            if ($result['state'] === 'fulfilled') {
                $userData[$username] = json_decode($result['value']->getBody(), true);
            } else {
                $userData[$username] = null;
            }
        }

        return $userData;
    }
}
