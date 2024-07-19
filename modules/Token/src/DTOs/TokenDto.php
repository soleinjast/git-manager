<?php

namespace Modules\Token\src\DTOs;

use Modules\Token\src\Models\GithubToken;

class TokenDto
{
    public function __construct(public string $token,
                                public string $login_name,
                                public string $githubId,
                                public int $repositoriesCount = 0)
    {

    }

    public static function fromEloquent(GithubToken $token) : self
    {
        return new self($token->token, $token->login_name, $token->githubId, $token->repositories->count());
    }

    public function toArray() : array
    {
        return [
            'githubId' => $this->githubId,
            'login' => $this->login_name,
            'token' => $this->token,
            'repositoriesCount' => $this->repositoriesCount
        ];
    }
}
