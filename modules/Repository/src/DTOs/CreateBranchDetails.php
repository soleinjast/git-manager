<?php

namespace Modules\Repository\src\DTOs;

class CreateBranchDetails
{
    public function __construct(public int $repositoryId, public string $branchName)
    {

    }

    public function toArray(): array
    {
        return [
            'repository_id' => $this->repositoryId,
            'name' => $this->branchName,
        ];
    }
}
