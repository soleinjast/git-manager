<?php

namespace Modules\Token\database\repository;

use Modules\Token\src\DTOs\CreateTokenDetails;
use Modules\Token\src\DTOs\TokenDto;
use Modules\Token\src\Exceptions\TokenDeletionFailedException;
use Modules\Token\src\Models\GithubToken;

interface TokenRepositoryInterface
{
    public function create(CreateTokenDetails $createTokenDetails) : TokenDto;
    public function fetch() : array;

    /**
     * @throws TokenDeletionFailedException
     */
    public function delete(int $id) : bool;
}
