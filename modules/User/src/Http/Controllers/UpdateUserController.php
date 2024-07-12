<?php

namespace Modules\User\src\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\User\database\repository\UserRepositoryInterface;
use Modules\User\src\DTOs\UserCreateDetails;

class UpdateUserController
{
    use ApiResponse;
    public function __construct(protected UserRepositoryInterface $userRepository)
    {

    }

    public function __invoke(): JsonResponse
    {
        try {
            $userCreateDetails = new UserCreateDetails(
                repositoryId: request()->repository_id,
                login_name: request()->login_name,
                name: request()->name ?? '',
                git_id: request()->git_id,
                avatar_url: request()->avatar_url,
                university_username: request()->university_username,
                status: request()->status,
            );
            $this->userRepository->updateOrCreate($userCreateDetails);
            return $this->successResponse([]);
        } catch (\Exception $exception) {
            report($exception);
            return $this->errorResponse('Operation failed!', [], 500);
        }
    }
}
