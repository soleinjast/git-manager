<?php

namespace Modules\Repository\tests\Controllers;

use Modules\Repository\src\DTOs\UpdateRepositoryDetailsInterface;

class TestUpdateRepositoryDetails implements UpdateRepositoryDetailsInterface
{
    public function __construct(public int $id, public ?int $github_token_id, public string $owner, public string $name, public string $deadline)
    {

    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->owner,
            'name' => $this->name,
            'github_token_id' => $this->github_token_id,
            'deadline' => $this->deadline
        ];
    }
}
{

}
