<?php

namespace Modules\Repository\src\DTOs;

class UpdateRepositoryDetails implements UpdateRepositoryDetailsInterface
{
    public function __construct(public int $id, public int $github_token_id, public string $deadline)
    {

    }

}
