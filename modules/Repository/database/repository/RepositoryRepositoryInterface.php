<?php

namespace Modules\Repository\database\repository;

use Closure;
use Modules\Commit\src\Exceptions\ChunkAllRepositoriesFailedException;
use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\Repository\src\DTOs\UpdateRepositoryDetails;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Exceptions\RepositoryDeletionFailedException;
use Modules\Repository\src\Exceptions\RepositoryInfoFindFailedException;
use Modules\Repository\src\Exceptions\RepositoryRetrievalFailedException;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;
use Modules\Repository\src\Exceptions\RetrieveRepositoryWithCommitsFailedException;

interface RepositoryRepositoryInterface
{
    /**
     * @throws RepositoryCreationFailedException
     */
    public function create(CreateRepositoryDetails $createRepositoryDetails);

    /**
     * @throws RepositoryUpdateFailedException
     */
    public function update(UpdateRepositoryDetails $updateRepositoryDetails);
    /**
     * @throws RepositoryRetrievalFailedException
     */
    public function fetchAll(?string $searchName = null, ?string $searchOwner = null, ?string $filterDeadline = null): array;
    /**
     * @throws RetrieveRepositoryWithCommitsFailedException
     */
    public function getRepositoryWithCommits(int $repositoryId, int $perPage, ?string $author, ?string $startDate, ?string $endDate);

    /**
     * @throws ChunkAllRepositoriesFailedException
     */
    public function chunkAll(int $chunkSize, Closure $callback): void;

    /**
     * @throws RepositoryInfoFindFailedException
     */
    public function findById(int $repoId): RepositoryDto;

    /**
     * @throws RepositoryDeletionFailedException
     */
    public function delete(int $repoId): bool;

}
