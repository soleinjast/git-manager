<?php

namespace Modules\Repository\database\repository;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Commit\src\Exceptions\ChunkAllRepositoriesFailedException;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\CreateRepositoryDetailsInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\DTOs\RepositoryItemsData;
use Modules\Repository\src\DTOs\UpdateRepositoryDetailsInterface;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Exceptions\RepositoryDeletionFailedException;
use Modules\Repository\src\Exceptions\RepositoryInfoFindFailedException;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;
use Modules\Repository\src\Exceptions\RetrieveRepositoryWithCommitsFailedException;
use Modules\Repository\src\Models\Repository;

class RepositoryRepository implements RepositoryRepositoryInterface
{
    /**
     * @throws RepositoryCreationFailedException
     */
    public function create(CreateRepositoryDetailsInterface $createRepositoryDetails): RepositoryDto
    {
        try {
            $repository = Repository::query()->create($createRepositoryDetails->toArray());
            return RepositoryDto::fromEloquent($repository);
        }catch (\Exception $exception){
            report($exception);
            throw new RepositoryCreationFailedException(RepositoryResponseEnums::REPOSITORY_CREATION_FAILED, 500);
        }
    }

    /**
     * @throws RepositoryUpdateFailedException
     */
    public function update(UpdateRepositoryDetailsInterface $updateRepositoryDetails): RepositoryDto
    {
        try {
            $repository = Repository::query()->find($updateRepositoryDetails->id);
            $repository->update([
                'github_token_id' => $updateRepositoryDetails->github_token_id,
                'deadline' => $updateRepositoryDetails->deadline
            ]);
            return RepositoryDto::fromEloquent($repository);
        }catch (Exception $exception){
            report($exception);
            throw new RepositoryUpdateFailedException(RepositoryResponseEnums::REPOSITORY_UPDATE_FAILED, 500);
        }
    }

    /**
     * @throws RepositoryRetrievalFailedException
     */
    public function fetchAll(?string $searchName = null, ?string $searchOwner = null, ?string $filterDeadline = null, ?string $filterTokenId = null): array
    {
        try {
            $repositories = Repository::searchByName($searchName)
                ->searchByOwner($searchOwner)
                ->filterByDeadline($filterDeadline)
                ->filterByToken($filterTokenId)
                ->orderBy('created_at', 'DESC')
                ->get();
            return RepositoryItemsData::fromRepositoryEloquentCollection($repositories);
        }catch (\Exception $exception){
            report($exception);
            throw new RepositoryRetrievalFailedException(RepositoryResponseEnums::REPOSITORY_RETRIEVAL_FAILED, 500);
        }
    }

    /**
     * @throws RetrieveRepositoryWithCommitsFailedException
     */
    public function getRepositoryWithCommits(int $repositoryId,
                                             int $perPage,
                                             ?string $author,
                                             ?string $startDate,
                                             ?string $endDate): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null
    {
        try {
            $repository = Repository::query()->findOrFail($repositoryId);
            $paginatedCommits = $repository->commits()->orderBy('date', 'DESC')
                ->filterByAuthor($author)
                ->filterByStartDate($startDate)
                ->filterByEndDate($endDate)
                ->paginate($perPage);
            $repository->setRelation('commits', $paginatedCommits);
            return $repository;
        }catch (Exception | ModelNotFoundException $exception){
            report($exception);
            throw new RetrieveRepositoryWithCommitsFailedException(RepositoryResponseEnums::REPOSITORY_RETRIEVAL_WITH_COMMIT_FAILED, 500);
        }
    }

    /**
     * @throws RepositoryInfoFindFailedException
     */
    public function findById(int $repoId): RepositoryDto
    {
        try {
            $repository = Repository::query()->find($repoId);
            return RepositoryDto::fromEloquent($repository);
        }catch (Exception $exception){
            report($exception);
            throw new RepositoryInfoFindFailedException(RepositoryResponseEnums::REPOSITORY_INFO_FIND_FAILED, 500);
        }
    }

    /**
     * @throws ChunkAllRepositoriesFailedException
     */
    public function chunkAll(int $chunkSize, Closure $callback): void
    {
        try {
            Repository::query()->chunk($chunkSize, $callback);
        }catch (Exception $exception){
            report($exception);
            throw new ChunkAllRepositoriesFailedException();
        }
    }

    /**
     * @throws RepositoryDeletionFailedException
     */
    public function delete(int $repoId): bool
    {
        try {
            $repository = Repository::query()->find($repoId);
            return $repository->delete();
        }catch (Exception $exception){
            report($exception);
            throw new RepositoryDeletionFailedException(RepositoryResponseEnums::REPOSITORY_DELETE_FAILED, 500);
        }
    }
}
