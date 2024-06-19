<?php

namespace Modules\Repository\database\repository;

use Modules\Repository\src\DTOs\CreateRepositoryDetails;
use Modules\Repository\src\DTOs\UpdateRepositoryDetails;
use Modules\Repository\src\Exceptions\RepositoryCreationFailedException;
use Modules\Repository\src\Exceptions\RepositoryUpdateFailedException;

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
}
