<?php

namespace Modules\Repository\src\DTOs;

class UpdateRepositoryResponse
{
    public function __construct(public int $id, public string $owner, public string $name, public string $deadline, public int $github_token_id)
    {

    }
}
