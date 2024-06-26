<?php

namespace Modules\Commit\src\DTOs;

class CreateCommitFileDetails
{
    public function __construct(public int $commit_id,
                                public string $filename,
                                public string $status,
                                public string $changes,
                                public bool $meaningful)
    {

    }
}
