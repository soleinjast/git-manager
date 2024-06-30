<?php

namespace Modules\Commit\database\repository;

use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Exceptions\FailedToCheckIfCommitExistsException;
use Modules\Commit\src\Exceptions\FailedToFetchCommitWithCommitFiles;

interface CommitRepositoryInterface
{
    public function create(CreateCommitDetails $createCommitDetails): CommitDto;

    /**
     * @throws FailedToCheckIfCommitExistsException
     */
    public function existsByShaAndRepositoryId(string $sha, int $repositoryId): bool;

    /**
     * @throws FailedToFetchCommitWithCommitFiles
     */
    public function getCommitFilesBySha(int $repositoryId, string $sha): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|null;

}
