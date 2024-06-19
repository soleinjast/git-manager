<?php

namespace Modules\Repository\src\DTOs;

use Modules\Repository\src\Models\Repository;

class RepositoryDto
{
    public function __construct(public int $id, public string $owner, public string $name, public string $created_at, public string $updated_at, public int $github_token_id, public string $deadline = '')
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
            $repository->deadline
        );
    }
}
