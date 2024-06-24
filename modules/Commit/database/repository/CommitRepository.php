<?php

namespace Modules\Commit\database\repository;

use Exception;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Exceptions\CommitUpdateOrCreateFailedException;
use Modules\Commit\src\Models\Commit;

class CommitRepository implements CommitRepositoryInterface
{
    /**
     * @param CreateCommitDetails $createCommitDetails
     * @return CommitDto
     * @throws CommitUpdateOrCreateFailedException
     */
    public function updateOrCreate(CreateCommitDetails $createCommitDetails): CommitDto
    {
        try {
            $commit = Commit::query()->updateOrCreate($createCommitDetails->toArray());
            return CommitDto::fromEloquent($commit);
        } catch (Exception $e) {
            throw new CommitUpdateOrCreateFailedException($e->getMessage());
        }
    }
}
