<?php

namespace Modules\Commit\database\repository;

use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\DTOs\CreateCommitDetails;

interface CommitRepositoryInterface
{
    public function updateOrCreate(CreateCommitDetails $createCommitDetails): CommitDto;
}
