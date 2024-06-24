<?php

namespace Modules\Commit\src\DTOs;

use Modules\Commit\src\Models\Commit;

class CommitDto
{
    public function __construct(
        public int $id,
        public int $repositoryId,
        public string $sha,
        public string $message,
        public string $author,
        public string $date
    ) {}

    public static function fromEloquent(Commit $commit): CommitDto
    {
        return new self(
            $commit->id,
            $commit->repository_id,
            $commit->sha,
            $commit->message,
            $commit->author,
            $commit->date->toDateTimeString(),
        );
    }
}
