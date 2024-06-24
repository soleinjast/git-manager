<?php

namespace Modules\Commit\src\DTOs;

class CreateCommitDetails
{
    public function __construct(
        public int $repositoryId,
        public string $sha,
        public string $message,
        public string $author,
        public string $date,
        public bool $is_first_commit,
        public ?string $author_git_id,
    ) {}

    public function toArray(): array
    {
        return [
            'repository_id' => $this->repositoryId,
            'sha' => $this->sha,
            'message' => $this->message,
            'author' => $this->author,
            'date' => $this->date,
            'is_first_commit' => $this->is_first_commit,
            'author_git_id' => $this->author_git_id,
        ];
    }
}
