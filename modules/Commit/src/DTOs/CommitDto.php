<?php

namespace Modules\Commit\src\DTOs;

use Illuminate\Support\Collection;
use Modules\Commit\src\Models\Commit;

class CommitDto
{
    public function __construct(
        public int $id,
        public int $repositoryId,
        public string $sha,
        public string $message,
        public string $author,
        public string $date,
        public bool $is_first_commit
    ) {}
    public static function fromEloquentCollection(Collection $commits): array
    {
        return $commits->map(function (Commit $commit) {
            return self::fromEloquent($commit)->toArray();
        })->toArray();
    }
    public static function fromEloquent(Commit $commit): CommitDto
    {
        return new self(
            $commit->id,
            $commit->repository_id,
            $commit->sha,
            $commit->message,
            $commit->author,
            $commit->date->toDateTimeString(),
            $commit->is_first_commit,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'repository_id' => $this->repositoryId,
            'sha' => $this->sha,
            'message' => $this->message,
            'author' => $this->author,
            'date' => date('d-m-Y', strtotime($this->date)),
            'is_first_commit' => $this->is_first_commit
        ];
    }
}
