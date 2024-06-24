<?php

namespace Modules\User\src\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Repository\src\DTOs\RepositoryDto;
use Modules\User\database\repository\UserRepository;
use Modules\User\database\repository\UserRepositoryInterface;
use Modules\User\src\DTOs\UserCreateDetails;

class CreateUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public RepositoryDto $repository, public array $userData)
    {

    }

    public function handle(UserRepositoryInterface $userRepository): void
    {
        $newUserDetails = new UserCreateDetails(repositoryId: $this->repository->id,
            login_name: $this->userData['login'],
            name: $this->userData['name'] ?? '',
            git_id: $this->userData['id'],
            avatar_url: $this->userData['avatar_url']
        );
        $userRepository->updateOrCreate($newUserDetails);
    }
}
