<?php

namespace Modules\Repository\src\DTOs;

use Modules\Repository\src\Models\Repository;

class RepositoryDto
{
    public function __construct(public int $id,
                                public string $owner,
                                public string $name,
                                public string $created_at,
                                public string $updated_at,
                                public int $github_token_id,
                                public string $deadline,
                                public string $token)
    {

    }

    public static function fromEloquent(Repository $repository): RepositoryDto
    {
        return new self($repository->id,
            $repository->owner,
            $repository->name,
            $repository->created_at,
            $repository->updated_at,
            $repository->github_token_id,
            $repository->deadline,
            $repository->token->token
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->owner,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'github_token_id' => $this->github_token_id,
            'deadline' => $this->deadline,
            'token' => $this->token
        ];
    }
}
