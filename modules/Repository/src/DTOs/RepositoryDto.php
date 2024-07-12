<?php

namespace Modules\Repository\src\DTOs;

use Illuminate\Database\Eloquent\Collection;
use Modules\Repository\src\Models\Repository;

class RepositoryDto
{
    public function __construct(public int $id,
                                public string $owner,
                                public string $name,
                                public string $created_at,
                                public string $updated_at,
                                public int $github_token_id,
                                public string $deadline,
                                public string $token,
                                public Collection $collabs,
                                public int $commitsCount,
                                public int $commitsFilesCount,
                                public int $meaningfulCommitFilesCount,
                                public int $NotMeaningfulCommitFilesCount,
                                public ?string $firstCommit,
                                public ?string $lastCommit,
                                public string $repositoryUrl,
                                public bool $isCloseToDeadline,
                                public string $commitDashboardUrl
    )
    {

    }

    public static function fromEloquent(Repository $repository): RepositoryDto
    {
        return new self($repository->id,
            $repository->owner,
            $repository->name,
            $repository->created_at,
            $repository->updated_at,
            $repository->github_token_id,
            $repository->deadline,
            $repository->token->token,
            $repository->collaborators,
            $repository->commits->count(),
            $repository->total_commit_files_count,
            $repository->meaningful_commit_files_count,
            $repository->not_meaningful_commit_files_count,
            $repository->first_commit,
            $repository->last_commit,
            $repository->github_url,
            $repository->isCloseToDeadline,
            $repository->getCommitsDashboardUrl(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->owner,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'github_token_id' => $this->github_token_id,
            'deadline' => $this->deadline,
            'token' => $this->token,
            'collabs' => $this->collabs->toArray(),
            'commitsCount' => $this->commitsCount,
            'commitsFilesCount' => $this->commitsFilesCount,
            'meaningfulCommitFilesCount' => $this->meaningfulCommitFilesCount,
            'NotMeaningfulCommitFilesCount' => $this->NotMeaningfulCommitFilesCount,
            'firstCommit' => $this->firstCommit,
            'lastCommit' => $this->lastCommit,
            'githubUrl' => $this->repositoryUrl,
            'isCloseToDeadline' => $this->isCloseToDeadline,
            'commitDashboardUrl' => $this->commitDashboardUrl
        ];
    }
}
