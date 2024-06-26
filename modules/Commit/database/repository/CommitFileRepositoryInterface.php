<?php

namespace Modules\Commit\database\repository;

use Modules\Commit\src\DTOs\CreateCommitFileDetails;

interface CommitFileRepositoryInterface
{
    public function updateOrCreate(CreateCommitFileDetails $commitFileDetails): void;
}
