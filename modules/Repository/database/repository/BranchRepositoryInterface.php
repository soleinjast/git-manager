<?php

namespace Modules\Repository\database\repository;

use Modules\Repository\src\DTOs\BranchDto;
use Modules\Repository\src\DTOs\CreateBranchDetails;

interface BranchRepositoryInterface
{
    public function create(CreateBranchDetails $createBranchDetails): BranchDto;
    public function updateOrCreate(CreateBranchDetails $createBranchDetails): BranchDto;
}
