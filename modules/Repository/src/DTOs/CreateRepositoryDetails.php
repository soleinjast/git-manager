<?php

namespace Modules\Repository\src\DTOs;

use DateTime;

class CreateRepositoryDetails implements CreateRepositoryDetailsInterface
{
    public function __construct(public int $github_token_id, public string $owner, public string $name, public string $deadline)
    {

    }

    public function toArray() : array
    {
        return [
            'owner' => $this->owner,
            'name' => $this->name,
            'github_token_id' => $this->github_token_id,
            'deadline' => $this->deadline
        ];
    }
}
