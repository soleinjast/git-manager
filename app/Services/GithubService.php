<?php

namespace App\Services;

use App\Enumerations\GithubApiResponses;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Token\src\Enumerations\GithubTokenApiResponses;

class GithubService
{
    private string $token;
    private string $owner;
    private string $name;

    public function setModel(string $token, string $owner, string $name): void
    {
        $this->token = $token;
        $this->owner = $owner;
        $this->name = $name;
    }

    /**
     * @throws ConnectionException
     */
    public function checkAccess(): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->get("https://api.github.com/repos/{$this->owner}/{$this->name}");
            return $response->successful();
        } catch (ConnectionException $e) {
            throw new ConnectionException(GithubApiResponses::CONNECTION_ERROR);
        }
    }

    /**
     * Fetch branches of the repository.
     *
     * @return array
     * @throws ConnectionException
     * @throws Exception
     */
    public function fetchBranches(): array
    {
        try {
            $response = Http::withToken($this->token)
                ->get("https://api.github.com/repos/{$this->owner}/{$this->name}/branches");

            if ($response->successful()) {
                return $response->json();
            }
            throw new Exception(GithubApiResponses::SERVER_ERROR);
        } catch (ConnectionException $e) {
            throw new ConnectionException(GithubApiResponses::CONNECTION_ERROR);
        }
    }
    /**
     * Fetch commits of the repository.
     *
     * @param string $branch
     * @return array
     * @throws ConnectionException
     * @throws Exception
     */
    public function fetchCommits(string $branch): array
    {
        $commits = [];
        $page = 1;
        try {
            do {
                $response = Http::withToken($this->token)
                    ->get("https://api.github.com/repos/{$this->owner}/{$this->name}/commits", [
                        'sha' => $branch,
                        'page' => $page,
                        'per_page' => 100,
                    ]);
                if ($response->successful()) {
                    $pageCommits = $response->json();
                    $commits = array_merge($commits, $pageCommits);
                    $page++;
                } else {
                    throw new Exception(GithubApiResponses::SERVER_ERROR);
                }
            } while (count($pageCommits) === 100);

            return $commits;
        } catch (ConnectionException $e) {
            throw new ConnectionException(GithubApiResponses::CONNECTION_ERROR);
        }
    }
    public function getCollaborators(): array
    {
        try {
            $response = Http::withToken($this->token)
                ->get("https://api.github.com/repos/{$this->owner}/{$this->name}/collaborators");
            if (!$response->successful()) {
                return [];
            }
            $collaborators = $response->json();
            foreach ($collaborators as &$collaborator) {
                $userDetails = $this->getUserDetails($collaborator['login']);
                $collaborator['name'] = $userDetails['name'] ?? null;
            }

            return $collaborators;

        } catch (ConnectionException $e) {
            report($e);
            return [];
        }
    }
    public function getUserDetails(string $username): array
    {
        try {
            $response = Http::withToken($this->token)
                ->get("https://api.github.com/users/{$username}");

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (ConnectionException $e) {
            report($e);
            return [];
        }
    }

}
