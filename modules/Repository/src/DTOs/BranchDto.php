<?php

namespace Modules\Repository\src\DTOs;

use Modules\Repository\src\Models\Branch;

class BranchDto
{
    public function __construct(public int $id, public int $repositoryId, public string $name)
    {

    }

    public static function fromEloquent(Branch $branch): BranchDto
    {
        return new self($branch->id, $branch->repository_id, $branch->name);
    }
}
