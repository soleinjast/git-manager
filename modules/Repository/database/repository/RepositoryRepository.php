<?php

namespace Modules\Repository\database\repository;

use Exception;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\CreateRepositoryDetailsInterface;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\DTOs\RepositoryItemsData;
use Modules\Repository\src\DTOs\UpdateRepositoryDetailsInterface;
use Modules\Repository\src\Enumerations\RepositoryResponseEnums;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;
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
    public function fetchAll(?string $searchName = null, ?string $searchOwner = null): array
    {
        try {
            $repositories = Repository::searchByName($searchName)
                ->searchByOwner($searchOwner)
                ->orderBy('created_at', 'DESC')
                ->get();
            return RepositoryItemsData::fromRepositoryEloquentCollection($repositories);
        }catch (\Exception $exception){
            report($exception);
            throw new RepositoryRetrievalFailedException(RepositoryResponseEnums::REPOSITORY_RETRIEVAL_FAILED, 500);
        }
    }
}
