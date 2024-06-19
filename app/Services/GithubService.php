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
}
