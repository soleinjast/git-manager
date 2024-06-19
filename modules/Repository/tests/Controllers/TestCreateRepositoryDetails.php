<?php

namespace Modules\Repository\tests\Controllers;

use DateTime;
use Modules\Repository\src\DTOs\CreateRepositoryDetailsInterface;

class TestCreateRepositoryDetails implements CreateRepositoryDetailsInterface
{
    public function __construct(public ?int $github_token_id, public string $owner, public string $name, public string $deadline)
    {

    }

    public function toArray(): array
    {
        return [
            'owner' => $this->owner,
            'name' => $this->name,
            'github_token_id' => $this->github_token_id,
            'deadline' => $this->deadline
        ];
    }
}
