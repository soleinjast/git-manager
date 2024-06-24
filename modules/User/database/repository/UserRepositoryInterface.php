<?php

namespace Modules\User\database\repository;

use Modules\User\src\DTOs\UserCreateDetails;

interface UserRepositoryInterface
{
    public function updateOrCreate(UserCreateDetails $userCreateDetails): void;
}
