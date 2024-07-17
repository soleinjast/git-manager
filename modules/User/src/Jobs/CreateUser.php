<?php

namespace Modules\User\src\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\User\database\repository\UserRepositoryInterface;
use Modules\User\src\DTOs\UserCreateDetails;
use Modules\User\src\Models\User;

class CreateUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public RepositoryDto $repository, public array $userData)
    {

    }

    public function handle(UserRepositoryInterface $userRepository): void
    {
        // Check if the user already exists
        $existingUser = User::query()->where([
            'git_id' => $this->userData['id'],
            'repository_id' => $this->repository->id,
        ])->first();
        // Conditionally update university_username
        $universityUsername = $existingUser && $existingUser->university_username
            ? $existingUser->university_username
            : ($this->userData['university_username'] ?? '');

        $newUserDetails = new UserCreateDetails(repositoryId: $this->repository->id,
            login_name: $this->userData['login'],
            name: $this->userData['name'] ?? '',
            git_id: $this->userData['id'],
            avatar_url: $this->userData['avatar_url'],
            university_username: $universityUsername,
            status: $this->userData['status'] ?? 'approved'
        );
        $userRepository->updateOrCreate($newUserDetails);
    }
}
