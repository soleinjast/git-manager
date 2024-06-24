<?php

namespace Modules\Repository\database\repository;

use Exception;
use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\DTOs\CreateBranchDetails;
use Modules\Repository\src\Models\Branch;

class BranchRepository implements BranchRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function create(CreateBranchDetails $createBranchDetails): BranchDto
    {
        try {
            $branch = Branch::query()->create($createBranchDetails->toArray());
            return BranchDto::fromEloquent($branch);
        }catch (Exception $e){
            throw new Exception($e->getMessage());
        }
    }
}
