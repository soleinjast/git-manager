<?php

namespace Modules\User\database\repository;

use Exception;
use Modules\User\src\DTOs\UserCreateDetails;
use Modules\User\src\Models\User;

class UserRepository implements UserRepositoryInterface
{

    /**
     * @throws Exception
     */
    public function updateOrCreate(UserCreateDetails $userCreateDetails): void
    {
        try {
            User::query()->updateOrCreate([
                'git_id' => $userCreateDetails->git_id,
                'repository_id' => $userCreateDetails->repositoryId,
            ], [
                'login_name' => $userCreateDetails->login_name,
                'name' => $userCreateDetails->name,
                'avatar_url' => $userCreateDetails->avatar_url,
                'university_username' => $userCreateDetails->university_username,
                'status' => $userCreateDetails->status,
            ]);
        }catch (Exception $exception){
            report($exception);
            throw new Exception($exception->getMessage());
        }
    }
}
