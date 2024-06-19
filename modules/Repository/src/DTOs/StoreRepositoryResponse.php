<?php

namespace Modules\Repository\src\DTOs;

class StoreRepositoryResponse
{
    public function __construct(public string $owner, public string $name)
    {

    }

    public function toArray(): array
    {
        return [
            'owner' => $this->owner,
            'name' => $this->name
        ];
    }
}
