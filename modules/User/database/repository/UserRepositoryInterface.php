<?php

namespace Modules\User\database\repository;

use Exception;
use Modules\User\src\DTOs\UserCreateDetails;

interface UserRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function updateOrCreate(UserCreateDetails $userCreateDetails): void;
}
