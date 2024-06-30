<?php

namespace Modules\Commit\database\repository;

use Exception;
use Modules\Commit\src\DTOs\CommitDto;
use Modules\Commit\src\DTOs\CreateCommitDetails;
use Modules\Commit\src\Exceptions\FailedToFetchCommitWithCommitFiles;
use Modules\Commit\src\Exceptions\CommitUpdateOrCreateFailedException;
use Modules\Commit\src\Exceptions\FailedToCheckIfCommitExistsException;
use Modules\Commit\src\Models\Commit;

class CommitRepository implements CommitRepositoryInterface
{
    /**
     * @param CreateCommitDetails $createCommitDetails
     * @return CommitDto
     * @throws CommitUpdateOrCreateFailedException
     */
    public function create(CreateCommitDetails $createCommitDetails): CommitDto
    {
        try {
            $commit = Commit::query()->create($createCommitDetails->toArray());
            return CommitDto::fromEloquent($commit);
        } catch (Exception $e) {
            throw new CommitUpdateOrCreateFailedException($e->getMessage());
        }
    }

    /**
     * @throws FailedToCheckIfCommitExistsException
     */
    public function existsByShaAndRepositoryId(string $sha, int $repositoryId): bool
    {
        try {
            return Commit::query()
                ->where('sha', $sha)
                ->where('repository_id', $repositoryId)
                ->exists();
        }catch (Exception $exception){
            throw new FailedToCheckIfCommitExistsException($exception->getMessage());
        }
    }

    /**
     * @throws FailedToFetchCommitWithCommitFiles
     */
    public function getCommitFilesBySha(int $repositoryId, string $sha): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|null
    {
        try {
            $commit = Commit::query()->where('repository_id', $repositoryId)
                ->where('sha', $sha)
                ->with('commitFiles')
                ->first();
        }catch (\Exception $exception){
            report($exception);
            throw new FailedToFetchCommitWithCommitFiles();
        }
        return $commit;
    }
}
